<?php

namespace App\Http\Controllers\Staff;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Models\BanAn;
use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\NguyenLieu;
use App\Models\SanPham;
use App\Models\CaLamViec;
use App\Models\ChamCong;
use App\Models\DanhMuc;
use App\Models\ThanhToan;
use App\Models\Voucher;
use App\Services\PaymentService;
use App\Services\OrderInventoryService;
use App\Services\TableStatusService;
use App\Traits\GeneratesOrderCode;
use App\Traits\NormalizesPayment;
use App\Traits\ResolvesVietQrBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableController extends Controller
{
    use NormalizesPayment, GeneratesOrderCode, ResolvesVietQrBank;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderInventoryService $inventoryService,
    ) {}
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedTableId = $request->filled('table') ? (int) $request->table : null;

        $tables = BanAn::query()
            ->with(['donHang' => function ($q) {
                $q->with(['nguoiDung', 'nhanVien']);
            }])
            ->orderBy('so_ban')
            ->get();

        // Current shift & attendance
        [$currentShift, $currentAttendance] = $this->resolveCurrentShift($user);

        // Ingredients for expense form
        $ingredients = NguyenLieu::orderBy('ten_nguyen_lieu')->get();

        // Selected table detail
        $selectedTable = null;
        $selectedOrder = null;
        $assignOrder = null;
        $selectedTableHasUnpaid = null;
        if ($request->filled('assign_order')) {
            $assignOrder = DonHang::with(['chiTietDonHang.sanPham', 'chiTietDonHang.kichCo'])->find($request->input('assign_order'));
        }
        $selectedItems = collect();
        $store = null;
        $menuCategories = collect();
        $menuProducts = collect();
        $selectedCategoryId = $request->input('category');
        $selectedProductId = $request->input('selected_product');
        $selectedVoucherId = $request->input('voucher_id');
        $availableVouchers = collect();

        if ($request->filled('table')) {
            $selectedTable = BanAn::find($request->table);

            if ($selectedTable) {
                $selectedTableHasUnpaid = $selectedTable->donHang()
                    ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
                    ->exists();

                $selectedOrder = $selectedTable->donHang()
                    ->where(function ($query) use ($selectedTable) {
                        $query->where(function ($q) {
                            $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'chưa thanh toán'));
                        })
                        ->orWhere(function ($q) use ($selectedTable) {
                            if (in_array($selectedTable->trang_thai, ['đang phục vụ', 'đã đặt'])) {
                                $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'đã thanh toán'));
                            } else {
                                $q->whereRaw('1 = 0');
                            }
                        });
                    })
                    ->latest()
                    ->first();

                if ($selectedOrder) {
                    $selectedItems = ChiTietDonHang::where('don_hang_id', $selectedOrder->id)
                        ->with(['sanPham', 'kichCo'])
                        ->get();
                }
            }
        } elseif ($assignOrder) {
            $selectedOrder = $assignOrder;
            $selectedItems = $assignOrder->chiTietDonHang;
        }

        // Merge session items
        if ($selectedTable) {
            $sessionCart = session()->get('staff_cart_' . $selectedTable->id, []);
            foreach ($sessionCart as $sItem) {
                $obj = new \stdClass();
                $obj->id = $sItem['session_key'];
                $obj->san_pham_id = $sItem['san_pham_id'];
                $obj->kich_co_id = $sItem['kich_co_id'];
                $obj->ten_san_pham = $sItem['ten_san_pham'];
                $obj->ten_kich_co = $sItem['ten_kich_co'];
                $obj->don_gia = $sItem['don_gia'];
                $obj->so_luong = $sItem['so_luong'];
                $obj->ghi_chu_mon = $sItem['ghi_chu_mon'];
                $obj->kichCo = new \stdClass();
                $obj->kichCo->ma_kich_co = $sItem['ma_kich_co'];
                $obj->kichCo->ten_kich_co = $sItem['ten_kich_co'];
                $obj->is_session = true; // flag to know it's not in DB
                
                $selectedItems->push($obj);
            }
        }

        // Calculate total including session items
        $calcTotal = 0;
        foreach ($selectedItems as $item) {
            $calcTotal += ($item->don_gia ?? 0) * ($item->so_luong ?? 1);
        }
        
        $discountAmount = $selectedOrder ? $selectedOrder->so_tien_giam : 0;
        // If there are session items, the raw subtotal increases, so the final total is raw subtotal - discount
        $displayTotal = max(0, $calcTotal - $discountAmount);

                // Store info for payment
                $storeId = $user->cua_hang_id;
                $store = CuaHang::query()
                    ->with('chuCuaHang')
                    ->when($storeId, fn($q) => $q->where('id', $storeId))
                    ->first();

                if (!$store) {
                    $store = CuaHang::query()
                        ->with('chuCuaHang')
                        ->first();
                }

                $menuCategories = DanhMuc::query()
                    ->where('trang_thai', 'đang dùng')
                    ->orderBy('ten_danh_muc')
                    ->with(['sanPham' => function ($q) {
                        $q->where('trang_thai_ban', 'đang bán')
                            ->orderBy('ten_san_pham');
                    }])
                    ->get();

                $now = now();
                $availableVouchers = Voucher::query()
                    ->where('trang_thai', 'đang hoạt động')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('ngay_bat_dau')->orWhere('ngay_bat_dau', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('ngay_ket_thuc')->orWhere('ngay_ket_thuc', '>=', $now);
                    })
                    ->orderBy('ma_voucher')
                    ->get();

                if (!$selectedCategoryId && $menuCategories->isNotEmpty()) {
                    $selectedCategoryId = $menuCategories->first()->id;
                }

                $menuProducts = $menuCategories
                    ->firstWhere('id', (int) $selectedCategoryId)?->sanPham
                    ?? collect();

        if ($request->ajax() && $request->boolean('partial')) {
            return response()->json([
                'left' => view('staff.tables.partials.left-panel', compact(
                    'tables', 'currentShift', 'currentAttendance', 'ingredients',
                    'selectedTable', 'selectedOrder', 'selectedItems', 'store', 'menuCategories',
                    'menuProducts', 'selectedCategoryId', 'selectedProductId', 'selectedVoucherId',
                    'availableVouchers', 'assignOrder'
                ))->render(),
                'detail' => view('staff.tables.partials.detail-panel', compact(
                    'selectedTable', 'selectedOrder', 'selectedItems', 'store', 'availableVouchers', 'selectedVoucherId',
                    'selectedTableHasUnpaid', 'displayTotal'
                ))->render(),
            ]);
        }

        return view('staff.tables.index', compact(
            'tables', 'currentShift', 'currentAttendance', 'ingredients',
            'selectedTable', 'selectedOrder', 'selectedItems', 'store', 'menuCategories',
            'menuProducts', 'selectedCategoryId', 'selectedProductId', 'selectedVoucherId',
            'availableVouchers', 'assignOrder', 'selectedTableHasUnpaid', 'displayTotal'
        ));
    }

    public function enterTable(int $id)
    {
        $table = BanAn::findOrFail($id);

        if ($table->trang_thai !== 'đã đặt') {
            return back()->with('error', 'Bàn này không ở trạng thái đã đặt.');
        }

        $table->update(['trang_thai' => 'đang phục vụ']);

        return redirect()
            ->route('staff.tables.index', ['table' => $table->id])
            ->with('success', "Đã chuyển bàn {$table->so_ban} sang trạng thái đang phục vụ.");
    }

    public function releaseTable(int $id)
    {
        $table = BanAn::findOrFail($id);

        DB::transaction(function () use ($table): void {
            $unpaidOrders = DonHang::where('ban_an_id', $table->id)
                ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
                ->get();

            foreach ($unpaidOrders as $order) {
                $this->inventoryService->restoreIngredientsForOrder($order);
                ThanhToan::where('don_hang_id', $order->id)->delete();
                $order->chiTietDonHang()->delete();
                $order->delete();
            }

            $table->update(['trang_thai' => 'trống']);
        });

        return redirect()
            ->route('staff.tables.index')
            ->with('success', "Đã trả bàn {$table->so_ban} về trạng thái trống.");
    }



    public function assignOrder(Request $request, int $tableId)
    {
        $request->validate(['order_id' => 'required|exists:don_hang,id']);
        $table = BanAn::findOrFail($tableId);
        
           if (!in_array($table->trang_thai, ['trống'], true)) {
             return back()->with('error', 'Bàn này đã có người ngồi, vui lòng chọn bàn trống.');
        }

        $order = DonHang::findOrFail($request->order_id);
        $order->update([
             'ban_an_id' => $table->id,
        ]);
        $order->chiTietDonHang()->update([
             'loai_don' => 'sử dụng ngay',
        ]);
        
        $table->update(['trang_thai' => 'đang phục vụ']);

        return redirect()->route('staff.tables.index', ['table' => $table->id])
                         ->with('success', 'Đã gán đơn hàng vào bàn thành công.');
    }

    public function show(int $id)
    {
        $table = BanAn::findOrFail($id);

        $dishItems = $table->chiTietDonHang()
            ->whereHas('donHang', function ($q) use ($table) {
                $q->where(function ($q1) {
                    $q1->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'chưa thanh toán'));
                });
                if ($table->trang_thai === 'đang phục vụ') {
                    $q->orWhere(function ($q3) {
                        $q3->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'đã thanh toán'))
                           ->whereDate('created_at', today());
                    });
                }
            })
            ->with(['donHang', 'kichCo'])
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $latestOrder = $table->donHang()
            ->where(function ($query) use ($table) {
                $query->where(function ($q) {
                    $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'chưa thanh toán'));
                })
                ->orWhere(function ($q) use ($table) {
                    if ($table->trang_thai === 'đang phục vụ') {
                        $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'đã thanh toán'))
                          ->whereDate('created_at', today());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });
            })
            ->with(['nguoiDung', 'nhanVien'])
            ->latest()
            ->first();

        $totalPayable = 0;
        if ($latestOrder) {
            $totalPayable = $latestOrder->tong_tien;
        }

        return view('staff.tables.show', compact('table', 'dishItems', 'totalPayable', 'latestOrder'));
    }


    public function updatePayment(Request $request, int $id)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'phuong_thuc_thanh_toan' => 'required|string',
            'trang_thai_thanh_toan' => 'required|string',
            'email_khach_hang' => 'nullable|email|max:255',
        ]);

        $table = BanAn::findOrFail($id);
        $order = DonHang::where('id', (int) $request->order_id)
            ->where('ban_an_id', $table->id)
            ->firstOrFail();

        $paymentMethod = $this->normalizePaymentMethod($request->phuong_thuc_thanh_toan);
        $paymentStatus = $this->normalizePaymentStatus($request->trang_thai_thanh_toan);

        if (!$paymentStatus || !$paymentMethod) {
            return back()->with('error', 'Thông tin thanh toán không hợp lệ.');
        }

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus, $table, $request) {
            $order->update([
                'nhan_vien_id' => Auth::id(),
                'email_khach_hang' => $request->input('email_khach_hang') ?? $order->email_khach_hang,
            ]);
            $order->updatePaymentStatus($paymentStatus, $paymentMethod);

            $this->paymentService->syncThanhToanSimple($order, $paymentMethod, $paymentStatus);

            if ($paymentStatus === 'đã thanh toán') {
                $order = $order->fresh();
                $this->paymentService->applyTableStatusAfterPayment($order);
                
                if ($order->email_khach_hang) {
                    \Illuminate\Support\Facades\Mail::to($order->email_khach_hang)->queue(new \App\Mail\CustomerOrderPaidMail($order));
                } elseif ($order->nguoiDung && $order->nguoiDung->email) {
                    \Illuminate\Support\Facades\Mail::to($order->nguoiDung->email)->queue(new \App\Mail\CustomerOrderPaidMail($order));
                }
            }
        });

        return redirect()
            ->route('staff.tables.index', ['table' => $table->id])
            ->with('success', "Đã cập nhật thanh toán cho bàn {$table->so_ban}.");
    }

    public function addItem(Request $request, int $id)
    {
        $request->validate([
            'san_pham_id' => 'required|exists:san_pham,id',
            'order_id' => 'nullable|exists:don_hang,id',
            'category_id' => 'nullable|integer',
            'size_id' => 'nullable|integer|exists:kich_co,id',
            'nhiet_do' => 'nullable|in:nóng,lạnh',
            'ghi_chu_mon' => 'nullable|string|max:255',
        ]);

        $table = BanAn::findOrFail($id);
        $product = SanPham::findOrFail($request->san_pham_id);
        $categoryId = $request->input('category_id') ?: $product->danh_muc_id;
        $sizeId = $request->input('size_id');
        $nhietDo = $request->input('nhiet_do');
        $ghiChuMon = $request->input('ghi_chu_mon');

        $sessionKey = 'staff_cart_' . $table->id;
        $cart = session()->get($sessionKey, []);

        // Determine price and size
        $price = $product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc;
        $sizeName = 'M';
        if ($sizeId) {
            $sizeItem = $product->kichCo()->find($sizeId);
            if ($sizeItem) {
                $price = $price * (float) ($sizeItem->he_so_gia ?? 1);
                $sizeName = $sizeItem->ten_kich_co ?? 'M';
            }
        }

        // Nhiệt độ là thuộc tính của món → ghép vào tên món (vd "Cà phê (Nóng)"),
        // KHÔNG nhét vào ghi chú. Ghi chú giữ nguyên là ghi chú của nhân viên.
        $tenSanPham = $product->ten_san_pham;
        if ($nhietDo) {
            $tenSanPham .= ' (' . ($nhietDo === 'nóng' ? 'Nóng' : 'Lạnh') . ')';
        }

        $mergeKey = null;
        foreach ($cart as $k => $i) {
            if ($i['san_pham_id'] == $product->id &&
                $i['kich_co_id'] == $sizeId &&
                ($i['nhiet_do'] ?? null) == $nhietDo &&
                $i['ghi_chu_mon'] == $ghiChuMon) {
                $mergeKey = $k;
                break;
            }
        }

        if ($mergeKey !== null) {
            $cart[$mergeKey]['so_luong'] += 1;
        } else {
            $newKey = 's_' . Str::random(6);
            $cart[$newKey] = [
                'session_key' => $newKey,
                'san_pham_id' => $product->id,
                'kich_co_id' => $sizeId,
                'ten_san_pham' => $tenSanPham,
                'ten_kich_co' => $sizeName,
                'nhiet_do' => $nhietDo,
                'don_gia' => $price,
                'so_luong' => 1,
                'ghi_chu_mon' => $ghiChuMon,
                'ma_kich_co' => $sizeId ? \App\Models\KichCo::find($sizeId)?->ma_kich_co : null,
            ];
        }

        session()->put($sessionKey, $cart);



        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('staff.tables.index', array_filter([
                'table' => $table->id,
                'category' => $categoryId,
                'selected_product' => $product->id,
            ]));
    }

    public function updateOrderStatus(Request $request, int $tableId)
    {
        $request->validate([
            'order_id' => 'nullable|exists:don_hang,id',
            'action' => 'required|in:draft,payment',
            'voucher_id' => 'nullable|exists:voucher,id',
            'category' => 'nullable|integer',
        ]);

        $table = BanAn::findOrFail($tableId);
        
        $sessionKey = 'staff_cart_' . $table->id;
        $cart = session()->get($sessionKey, []);

        $order = null;
        if ($request->order_id) {
            $order = DonHang::where('id', (int) $request->order_id)
                ->where('ban_an_id', $table->id)
                ->firstOrFail();
            
            if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
                if (empty($cart)) {
                    return redirect()
                        ->route('staff.tables.index', ['table' => $table->id])
                        ->with('error', 'Đơn hàng này đã được thanh toán. Vui lòng thêm món mới để tạo đơn mới.');
                }
                $order = null; // Create a new order instead of modifying a paid one
            }
        }

        if (empty($cart) && (!$order || $order->chiTietDonHang()->count() === 0)) {
            return redirect()
                ->route('staff.tables.index', ['table' => $table->id])
                ->with('error', 'Đơn hàng chưa có món nào.');
        }

        if (!$order && !empty($cart)) {
            [$currentShift, $currentAttendance] = $this->resolveCurrentShift(Auth::user());
            if ($currentShift) {
                $caIds = \App\Models\CaLamViec::where('ngay_lam', $currentShift->ngay_lam)
                    ->where('ten_ca', $currentShift->ten_ca)
                    ->pluck('id');
                $hasTienDauCa = \App\Models\ChotCa::whereIn('ca_lam_viec_id', $caIds)->exists();

                if (!$hasTienDauCa) {
                    return redirect()
                        ->route('staff.tables.index', ['table' => $table->id])
                        ->with('needs_start_cash_for_shift', $currentShift->id);
                }
            }
        }

        DB::transaction(function () use (&$order, $cart, $table, $sessionKey) {
            $oldUsage = $order ? $this->inventoryService->ingredientUsageForOrder($order->id) : [];

            if (!$order && !empty($cart)) {
                $order = DonHang::create([
                    'ma_don_hang' => $this->generateOrderCode(),
                    'nhan_vien_id' => Auth::id(),
                    'ban_an_id' => $table->id,
                ]);
            }

            if ($order && !empty($cart)) {
                foreach ($cart as $item) {
                    $existing = ChiTietDonHang::where('don_hang_id', $order->id)
                        ->where('san_pham_id', $item['san_pham_id'])
                        ->where('kich_co_id', $item['kich_co_id'])
                        ->where('ten_san_pham', $item['ten_san_pham'])
                        ->where('ghi_chu_mon', $item['ghi_chu_mon'])
                        ->first();
                    
                    if ($existing) {
                        $existing->update([
                            'so_luong' => $existing->so_luong + $item['so_luong'],
                            'thanh_tien' => $existing->don_gia * ($existing->so_luong + $item['so_luong']),
                        ]);
                    } else {
                        ChiTietDonHang::create([
                            'don_hang_id' => $order->id,
                            'san_pham_id' => $item['san_pham_id'],
                            'kich_co_id' => $item['kich_co_id'],
                            'ten_san_pham' => $item['ten_san_pham'],
                            'ten_kich_co' => $item['ten_kich_co'],
                            'don_gia' => $item['don_gia'],
                            'so_luong' => $item['so_luong'],
                            'thanh_tien' => $item['don_gia'] * $item['so_luong'],
                            'ghi_chu_mon' => $item['ghi_chu_mon'],
                            'created_at' => now(),
                        ]);
                    }
                }
                session()->forget($sessionKey);
            }

            if (!empty($cart) && $order) {
                $newUsage = $this->inventoryService->ingredientUsageForOrder($order->id);
                $this->inventoryService->applyIngredientDelta($oldUsage, $newUsage, $order->id);
            }
        });

        $voucherId = $request->input('voucher_id');
        $subtotal = $this->calculateSubtotal($order);
        $discount = 0;

        $voucher = null;
        if ($voucherId) {
            $voucher = Voucher::query()->whereKey($voucherId)->first();
        } elseif ($order && $order->voucher_nguoi_dung_id) {
            $vu = \App\Models\VoucherNguoiDung::with('voucher')->find($order->voucher_nguoi_dung_id);
            if ($vu) $voucher = $vu->voucher;
        }

        if ($voucher) {
            if ($voucher->trang_thai !== 'đang hoạt động') {
                return back()->with('error', 'Voucher không hợp lệ.');
            }

            $now = now();
            if ($voucher->ngay_bat_dau && $voucher->ngay_bat_dau->gt($now)) {
                return back()->with('error', 'Voucher chưa đến thời gian sử dụng.');
            }

            if ($voucher->ngay_ket_thuc && $voucher->ngay_ket_thuc->lt($now)) {
                return back()->with('error', 'Voucher đã hết hạn.');
            }

            $minTotal = (float) ($voucher->don_toi_thieu ?? 0);
            if ($minTotal > 0 && $subtotal < $minTotal) {
                return back()->with('error', 'Đơn hàng chưa đủ điều kiện áp dụng voucher.');
            }

            if ($voucher->loai_giam === 'phần trăm') {
                $discount = $subtotal * ((float) $voucher->gia_tri_giam / 100);
                $maxDiscount = (float) ($voucher->giam_toi_da ?? 0);
                if ($maxDiscount > 0) {
                    $discount = min($discount, $maxDiscount);
                }
            } else {
                $discount = (float) $voucher->gia_tri_giam;
            }

            $discount = min($discount, $subtotal);
        }

        // Distribute discount proportionally across chi_tiet_don_hang items
        $items = $order->chiTietDonHang()->get();
        $totalSubtotal = $items->sum('thanh_tien');
        foreach ($items as $item) {
            $itemDiscount = $totalSubtotal > 0 ? round($discount * $item->thanh_tien / $totalSubtotal, 2) : 0;
            $item->update([
                'so_tien_giam' => $itemDiscount,
                'tong_tien' => max(0, $item->thanh_tien - $itemDiscount),
            ]);
        }

        $order->update(['nhan_vien_id' => Auth::id()]);
        $table->update(['trang_thai' => 'đang phục vụ']);

        $paymentMethod = $order->phuong_thuc_thanh_toan ?? 'tiền mặt';
        $this->paymentService->syncThanhToanSimple($order, $paymentMethod, 'chưa thanh toán');

        TableStatusService::refreshForTable($table->id);

        if ($request->action === 'payment') {
            return redirect()
                ->route('staff.tables.index', array_filter([
                    'table' => $table->id,
                    'category' => $request->input('category'),
                    'voucher_id' => $voucherId,
                    'payment' => 1,
                ]));
        }

        $message = 'Đã cập nhật tạm tính.';

        $redirect = redirect()->route('staff.tables.index', array_filter([
            'table' => $table->id,
            'category' => $request->input('category'),
            'voucher_id' => $voucherId,
        ]));

        if ($request->boolean('auto_voucher')) {
            return $redirect;
        }

        return $redirect->with('success', $message);
    }

    public function updateItemQuantity(Request $request, int $tableId, string $itemId)
    {
        $table = BanAn::findOrFail($tableId);
        $action = $request->input('action', 'increase');

        // Cập nhật ghi chú món (không đổi số lượng / giá / kho)
        if ($action === 'note') {
            $note = trim((string) $request->input('ghi_chu_mon'));
            $note = $note === '' ? null : mb_substr($note, 0, 255);

            if (Str::startsWith($itemId, 's_')) {
                $sessionKey = 'staff_cart_' . $table->id;
                $cart = session()->get($sessionKey, []);
                if (isset($cart[$itemId])) {
                    $cart[$itemId]['ghi_chu_mon'] = $note;
                    session()->put($sessionKey, $cart);
                }
            } else {
                ChiTietDonHang::where('id', (int) $itemId)->update(['ghi_chu_mon' => $note]);
            }

            TableStatusService::refreshForTable($table->id);

            return redirect()->route('staff.tables.index', ['table' => $table->id]);
        }

        if (Str::startsWith($itemId, 's_')) {
            $sessionKey = 'staff_cart_' . $table->id;
            $cart = session()->get($sessionKey, []);
            if (isset($cart[$itemId])) {
                if ($action === 'decrease') {
                    if ($cart[$itemId]['so_luong'] <= 1) {
                        unset($cart[$itemId]);
                    } else {
                        $cart[$itemId]['so_luong']--;
                    }
                } else {
                    $cart[$itemId]['so_luong']++;
                }
                session()->put($sessionKey, $cart);
            }
        } else {
            $item = ChiTietDonHang::findOrFail((int) $itemId);
            $order = DonHang::find($item->don_hang_id);

            DB::transaction(function () use ($item, $action, $order) {
                $oldUsage = $order ? $this->inventoryService->ingredientUsageForOrder($order->id) : [];

                if ($action === 'decrease') {
                    if ($item->so_luong <= 1) {
                        $item->delete();
                    } else {
                        $item->update([
                            'so_luong' => $item->so_luong - 1,
                            'thanh_tien' => $item->don_gia * ($item->so_luong - 1),
                        ]);
                    }
                } else {
                    $item->update([
                        'so_luong' => $item->so_luong + 1,
                        'thanh_tien' => $item->don_gia * ($item->so_luong + 1),
                    ]);
                }

                if ($order) {
                    $newUsage = $this->inventoryService->ingredientUsageForOrder($order->id);
                    $this->inventoryService->applyIngredientDelta($oldUsage, $newUsage, $order->id);
                    
                    if ($order->chiTietDonHang()->count() === 0) {
                        \App\Models\ThanhToan::where('don_hang_id', $order->id)->delete();
                        $order->delete();
                    } else {
                        $this->recalculateOrder($order);
                    }
                }
            });
        }

        TableStatusService::refreshForTable($table->id);

        return redirect()
            ->route('staff.tables.index', ['table' => $table->id]);
    }

    // ─── Helpers ───

    private function resetDailyTableOrders($user): void
    {
        $today = now()->toDateString();

        $staleOrders = DonHang::query()
            ->whereNotNull('ban_an_id')
            ->where('nhan_vien_id', $user->id)
            ->whereHas('chiTietDonHang', fn($q) => $q->where('loai_don', 'sử dụng ngay')->where('trang_thai_thanh_toan', 'chưa thanh toán'))
            ->whereDate('created_at', '<', $today)
            ->with('banAn')
            ->get();

        foreach ($staleOrders as $order) {
            $hasDraft = ThanhToan::where('don_hang_id', $order->id)->exists();

            if ($hasDraft) {
                // Still has a payment draft, keep the order as-is
                continue;
            }

            $table = $order->banAn;
            $order->delete();

            if ($table && $table->trang_thai === 'đang phục vụ') {
                $hasUnpaid = DonHang::where('ban_an_id', $table->id)
                    ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
                    ->exists();

                if (!$hasUnpaid) {
                    $table->update(['trang_thai' => 'trống']);
                }
            }
        }
    }

    private function recalculateOrder(DonHang $order): void
    {
        // Recalculate tong_tien for each item preserving existing so_tien_giam distribution
        $items = $order->chiTietDonHang()->get();
        $totalSubtotal = $items->sum('thanh_tien');
        $totalDiscount = $items->sum('so_tien_giam');

        if ($totalSubtotal > 0) {
            foreach ($items as $item) {
                $ratio = $item->thanh_tien / $totalSubtotal;
                $itemDiscount = round($totalDiscount * $ratio, 2);
                $item->update([
                    'so_tien_giam' => $itemDiscount,
                    'tong_tien' => max(0, $item->thanh_tien - $itemDiscount),
                ]);
            }
        }
    }

    private function calculateSubtotal(DonHang $order): float
    {
        return (float) (ChiTietDonHang::where('don_hang_id', $order->id)
            ->selectRaw('SUM(don_gia * so_luong) as total')
            ->value('total') ?? 0);
    }

    private function resolveCurrentShift($user): array
    {
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        $shift = CaLamViec::where('nguoi_dung_id', $user->id)
            ->whereDate('ngay_lam', $today)
            ->where('gio_bat_dau', '<=', $currentTime)
            ->where('gio_ket_thuc', '>=', $currentTime)
            ->first();

        if (!$shift) {
            // Try any shift today
            $shift = CaLamViec::where('nguoi_dung_id', $user->id)
                ->whereDate('ngay_lam', $today)
                ->orderBy('gio_bat_dau')
                ->first();
        }

        $attendance = null;
        if ($shift) {
            $attendance = ChamCong::where('nguoi_dung_id', $user->id)
                ->where('ca_lam_viec_id', $shift->id)
                ->latest()
                ->first();
        }

        return [$shift, $attendance];
    }

    public function clearTable(int $id)
    {
        $table = BanAn::findOrFail($id);

        if ($table->trang_thai !== 'đang phục vụ') {
            return back()->with('error', 'Chỉ có thể xóa thông tin bàn đang ở trạng thái đang phục vụ.');
        }

        DB::transaction(function () use ($table): void {
            $unpaidOrders = DonHang::where('ban_an_id', $table->id)
                ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
                ->get();

            foreach ($unpaidOrders as $order) {
                $this->inventoryService->restoreIngredientsForOrder($order);
                ThanhToan::where('don_hang_id', $order->id)->delete();
                $order->chiTietDonHang()->delete();
                $order->delete();
            }

            $hasRemaining = DonHang::where('ban_an_id', $table->id)
                ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
                ->exists();

            if (! $hasRemaining) {
                $table->update(['trang_thai' => 'trống']);
            }
        });

        return redirect()->route('staff.tables.index')
            ->with('success', "Đã xóa thông tin bàn {$table->so_ban}. Bàn đã trở về trạng thái trống.");
    }

    // resolveVietQrBankCode() => ResolvesVietQrBank trait
    // normalizePaymentMethod() => NormalizesPayment trait
    // normalizePaymentStatus() => NormalizesPayment trait
    // generateOrderCode() => GeneratesOrderCode trait
}
