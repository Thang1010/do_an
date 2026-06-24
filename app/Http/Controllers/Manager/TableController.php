<?php

namespace App\Http\Controllers\Manager;

use App\Enums\PaymentStatus;
use App\Enums\TableStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\BanAn;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\ThanhToan;
use App\Services\PaymentService;
use App\Services\OrderInventoryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Traits\NormalizesPayment;
use App\Traits\ResolvesVietQrBank;
use App\Traits\GeneratesOrderCode;
use App\Models\ChiTietDonHang;

class TableController extends Controller
{
    use NormalizesPayment, ResolvesVietQrBank, GeneratesOrderCode;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderInventoryService $inventoryService,
    ) {}
    // normalizePaymentStatus(), normalizePaymentMethod() => NormalizesPayment trait
    // resolveVietQrBankCode() => ResolvesVietQrBank trait

    private function toDbTrangThai(?string $status): string
    {
        return TableStatus::normalize($status)->value;
    }

    /**
     * Tạo ảnh QR (data URI SVG) trỏ tới trang gọi món của một bàn.
     * Dùng url(route(..., false)) để QR mang đúng host đang truy cập (domain thật)
     * thay vì APP_URL (có thể là IP LAN).
     */
    private function tableQrDataUri(int $tableId): string
    {
        $url = url(route('order.table', ['table' => $tableId], false));

        return 'data:image/svg+xml;base64,' . base64_encode(
            QrCode::format('svg')->size(220)->margin(1)->generate($url)
        );
    }

    public function index(Request $request)
    {
        $query = BanAn::query()
            ->withCount([
                'donHang as so_don_chua_thanh_toan' => function ($q) {
                    $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'chưa thanh toán'));
                },
                'donHang as so_don_da_thanh_toan' => function ($q) {
                    $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'đã thanh toán'));
                },
            ]);

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('so_ban', 'like', "%{$s}%");
            });
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $this->toDbTrangThai($request->trang_thai));
        }

        $tables = $query->orderBy('so_ban')->paginate(20)->withQueryString();

        // Polling: chỉ trả lưới bàn (bỏ qua tạo QR tốn kém) để tự cập nhật trạng thái.
        // Dùng header X-Partial để URL sạch, không rò 'partial' vào link phân trang.
        if ($request->header('X-Partial')) {
            return response()->json([
                'html' => view('manager.tables.partials.grid', compact('tables'))->render(),
            ]);
        }

        // QR gọi món cho toàn bộ bàn (không phân trang) để in ngay trong modal.
        $qrTables = BanAn::where('trang_thai', '!=', 'ngưng sử dụng')
            ->orderBy('so_ban')
            ->get(['id', 'so_ban']);

        $qrcodes = [];
        foreach ($qrTables as $qrTable) {
            $qrcodes[$qrTable->id] = $this->tableQrDataUri($qrTable->id);
        }

        return view('manager.tables.index', compact('tables', 'qrTables', 'qrcodes'));
    }

    /**
     * Trang in QR gọi món tại bàn (mỗi bàn 1 thẻ). QR tĩnh, không hết hạn.
     * Dùng url(route(..., false)) để QR mang đúng host đang truy cập (domain
     * thật) thay vì APP_URL (có thể là IP LAN).
     */
    public function qrPrint()
    {
        $tables = BanAn::where('trang_thai', '!=', 'ngưng sử dụng')
            ->orderBy('so_ban')
            ->get(['id', 'so_ban']);

        $qrcodes = [];
        foreach ($tables as $table) {
            $qrcodes[$table->id] = $this->tableQrDataUri($table->id);
        }

        return view('manager.tables.qr-print', compact('tables', 'qrcodes'));
    }

    public function show(int $id)
    {
        $table = BanAn::withCount([
            'donHang as so_don_chua_thanh_toan' => function ($q) {
                $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'chưa thanh toán'));
            },
            'donHang as so_don_da_thanh_toan' => function ($q) {
                $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'đã thanh toán'));
            },
        ])->findOrFail($id);

        $latestOrder = $table->donHang()
            ->where(function ($query) use ($table) {
                $query->where(function ($q) {
                    $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'chưa thanh toán'));
                })
                ->orWhere(function ($q) use ($table) {
                    if (in_array($table->trang_thai, ['đang phục vụ', 'đã đặt'])) {
                        $q->whereHas('chiTietDonHang', fn($sq) => $sq->where('trang_thai_thanh_toan', 'đã thanh toán'));
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });
            })
            ->with(['nguoiDung', 'nhanVien'])
            ->latest()
            ->first();

        $dishItems = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        $totalDishQty = 0;
        $totalDiscount = 0;
        $totalPayable = 0;
        $voucherSummary = 'Không dùng voucher';
        $tableHasUnpaid = false;

        if ($latestOrder) {
            $dishQuery = $latestOrder->chiTietDonHang()->with('kichCo')->latest('created_at');
            $dishItems = $dishQuery->paginate(20)->withQueryString();
            
            $totalDishQty = (clone $dishQuery)->sum('so_luong');
            $totalDiscount = $latestOrder->so_tien_giam;
            $totalPayable = $latestOrder->tong_tien;

            $tableHasUnpaid = $latestOrder->trang_thai_thanh_toan === 'chưa thanh toán';
            
            if ($latestOrder->voucher_nguoi_dung_id) {
                $vu = \App\Models\VoucherNguoiDung::with('voucher')->find($latestOrder->voucher_nguoi_dung_id);
                if ($vu && $vu->voucher) {
                    $voucherSummary = $vu->voucher->ma_voucher;
                }
            }
        }

        $availableProducts = \App\Models\SanPham::query()
            ->where('trang_thai_ban', 'đang bán')
            ->orderBy('ten_san_pham')
            ->get(['id', 'danh_muc_id', 'ten_san_pham', 'gia_goc', 'gia_khuyen_mai', 'nhiet_do']);

        $categories = \App\Models\DanhMuc::where('trang_thai', 'đang dùng')
            ->orderBy('ten_danh_muc')
            ->get(['id', 'ten_danh_muc']);

        $allSizes = \App\Models\KichCo::orderBy('he_so_gia')->orderBy('ten_kich_co')->get();
        $productSizeMap = [];
        foreach ($availableProducts as $product) {
            $sizes = [];
            foreach ($allSizes as $sizeItem) {
                $sizes[] = [
                    'id' => $sizeItem->id,
                    'name' => $sizeItem->ten_kich_co ?? ('Size #' . $sizeItem->id),
                ];
            }

            $productSizeMap[$product->id] = [
                'danh_muc_id' => $product->danh_muc_id,
                'sizes' => $sizes,
                'temps' => array_values(array_filter(array_map('trim', explode(',', (string) $product->nhiet_do)))),
            ];
        }

        $tableQrCode = $this->tableQrDataUri($table->id);
        $tableQrUrl = url(route('order.table', ['table' => $table->id], false));

        return view('manager.tables.show', compact(
            'table',
            'dishItems',
            'totalDishQty',
            'totalDiscount',
            'totalPayable',
            'voucherSummary',
            'latestOrder',
            'tableHasUnpaid',
            'availableProducts',
            'productSizeMap',
            'categories',
            'tableQrCode',
            'tableQrUrl',
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
            ->route('manager.tables.show', $table->id)
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
            ->route('manager.tables.index')
            ->with('success', "Đã trả bàn {$table->so_ban} về trạng thái trống.");
    }





    public function updateOrderPayment(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->vai_tro, UserRole::staffRoleValues(), true)) {
            abort(403, 'Bạn không có quyền cập nhật thanh toán.');
        }

        $request->validate([
            'order_id' => 'required|integer',
            'phuong_thuc_thanh_toan' => 'required|string|max:50',
            'trang_thai_thanh_toan' => 'required|string|max:50',
            'email_khach_hang' => 'nullable|email|max:255',
        ]);

        $paymentMethod = $this->normalizePaymentMethod($request->input('phuong_thuc_thanh_toan'));
        $paymentStatus = $this->normalizePaymentStatus($request->input('trang_thai_thanh_toan'));

        if (! $paymentStatus) {
            return back()->with('error', 'Trạng thái thanh toán không hợp lệ.');
        }

        if (! $paymentMethod) {
            return back()->with('error', 'Phương thức thanh toán không hợp lệ.');
        }

        $table = BanAn::findOrFail($id);
        $order = DonHang::where('id', (int) $request->order_id)
            ->where('ban_an_id', $table->id)
            ->firstOrFail();

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus, $user, $table, $request): void {
            $order->update([
                'nhan_vien_id' => $user->id,
                'email_khach_hang' => $request->input('email_khach_hang') ?? $order->email_khach_hang,
            ]);
            $order->updatePaymentStatus($paymentStatus, $paymentMethod);

            $this->paymentService->syncThanhToanRecord($order->fresh(), $paymentMethod, $paymentStatus);

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

        return redirect()->route('manager.tables.index')->with('success', "Đã cập nhật thanh toán cho đơn #{$order->id} tại bàn {$table->so_ban}.");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_ban' => 'required|string|max:20|unique:ban_an,so_ban',
            'trang_thai' => 'nullable|in:trong,dang_phuc_vu,da_dat,ngung_su_dung,trống,đang phục vụ,đã đặt,ngưng sử dụng',
        ], [
            'so_ban.required' => 'Vui lòng nhập số bàn.',
            'so_ban.unique' => 'Số bàn đã tồn tại.',
            'trang_thai.in' => 'Trạng thái bàn ăn không hợp lệ.',
        ]);

        $table = BanAn::create([
            'so_ban' => trim($validated['so_ban']),
            'trang_thai' => $this->toDbTrangThai($validated['trang_thai'] ?? null),
        ]);

        return redirect()->route('manager.tables.index')->with('success', "Đã thêm bàn {$table->so_ban}.");
    }

    public function update(Request $request, int $id)
    {
        $table = BanAn::findOrFail($id);

        // Bàn đang có khách ngồi: chặn sửa thông tin để tránh nhầm lẫn khi đang phục vụ.
        if ($table->trang_thai === 'đang phục vụ') {
            return back()->with('error', "Khách đang sử dụng bàn {$table->so_ban} nên không thể sửa thông tin bàn.");
        }

        $validated = $request->validate([
            'so_ban' => "required|string|max:20|unique:ban_an,so_ban,{$id}",
            'trang_thai' => 'nullable|in:trong,dang_phuc_vu,da_dat,ngung_su_dung,trống,đang phục vụ,đã đặt,ngưng sử dụng',
        ], [
            'so_ban.required' => 'Vui lòng nhập số bàn.',
            'so_ban.unique' => 'Số bàn đã tồn tại.',
            'trang_thai.in' => 'Trạng thái bàn ăn không hợp lệ.',
        ]);

        $newTrangThai = $this->toDbTrangThai($validated['trang_thai'] ?? null);

        DB::transaction(function () use ($table, $validated, $newTrangThai) {
            if ($newTrangThai === 'trống' && $table->trang_thai !== 'trống') {
                $unpaidOrders = DonHang::where('ban_an_id', $table->id)
                    ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
                    ->get();

                foreach ($unpaidOrders as $order) {
                    $this->inventoryService->restoreIngredientsForOrder($order);
                    ThanhToan::where('don_hang_id', $order->id)->delete();
                    $order->chiTietDonHang()->delete();
                    $order->delete();
                }
            }

            $table->update([
                'so_ban' => trim($validated['so_ban']),
                'trang_thai' => $newTrangThai,
            ]);
        });

        return redirect()->route('manager.tables.index')->with('success', "Đã cập nhật bàn {$table->so_ban}.");
    }

    public function addItem(Request $request, int $id)
    {
        $table = BanAn::findOrFail($id);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.san_pham_id' => 'required|exists:san_pham,id',
            'items.*.kich_co_id' => 'nullable|exists:kich_co,id',
            'items.*.so_luong' => 'required|integer|min:1',
            'items.*.ghi_chu_mon' => 'nullable|string|max:255',
        ]);

        $order = $table->donHang()
            ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
            ->latest()
            ->first();

        DB::transaction(function () use ($table, &$order, $validated, $request) {
            $oldUsage = $order ? $this->inventoryService->ingredientUsageForOrder($order->id) : [];

            if (!$order) {
                $order = DonHang::create([
                    'ma_don_hang' => $this->generateOrderCode(),
                    'nhan_vien_id' => $request->user()->id,
                    'ban_an_id' => $table->id,
                ]);
            }

            foreach ($validated['items'] as $itemData) {
                $product = \App\Models\SanPham::find($itemData['san_pham_id']);
                $sizeId = $itemData['kich_co_id'] ?? null;
                $sizeName = null;
                $unitPrice = $product->gia_khuyen_mai ?? $product->gia_goc;

                if ($sizeId) {
                    $sizeItem = $product->kichCo()->find($sizeId);
                    if ($sizeItem) {
                        $basePrice = (float) ($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc);
                        $unitPrice = $basePrice * (float) ($sizeItem->he_so_gia ?? 1);
                        $sizeName = $sizeItem->ten_kich_co;
                    }
                }

                $existing = ChiTietDonHang::where('don_hang_id', $order->id)
                    ->where('san_pham_id', $product->id)
                    ->where('kich_co_id', $sizeId)
                    ->where('ghi_chu_mon', $itemData['ghi_chu_mon'] ?? null)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'so_luong' => $existing->so_luong + $itemData['so_luong'],
                        'thanh_tien' => $existing->don_gia * ($existing->so_luong + $itemData['so_luong']),
                    ]);
                } else {
                    ChiTietDonHang::create([
                        'don_hang_id' => $order->id,
                        'san_pham_id' => $product->id,
                        'kich_co_id' => $sizeId,
                        'ten_san_pham' => $product->ten_san_pham,
                        'ten_kich_co' => $sizeName,
                        'don_gia' => $unitPrice,
                        'so_luong' => $itemData['so_luong'],
                        'thanh_tien' => $unitPrice * $itemData['so_luong'],
                        'ghi_chu_mon' => $itemData['ghi_chu_mon'] ?? null,
                    ]);
                }
            }

            $order->update([
                'nhan_vien_id' => $request->user()->id,
            ]);

            $table->update(['trang_thai' => 'đang phục vụ']);

            $newUsage = $this->inventoryService->ingredientUsageForOrder($order->id);
            $this->inventoryService->applyIngredientDelta($oldUsage, $newUsage, $order->id);
        });

        return redirect()->route('manager.tables.show', $table->id)->with('success', 'Đã thêm món vào bàn thành công.');
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

        return redirect()->route('manager.tables.index')
            ->with('success', "Đã xóa thông tin bàn {$table->so_ban}. Bàn đã trở về trạng thái trống.");
    }

    public function destroy(int $id)
    {
        $table = BanAn::withCount(['donHang', 'chiTietDonHang'])->findOrFail($id);

        if ($table->don_hang_count > 0 || $table->chi_tiet_don_hang_count > 0) {
            return back()->with('error', 'Bàn đã có dữ liệu đơn/món, không thể xóa.');
        }

        try {
            $name = $table->so_ban;
            $table->delete();
            return redirect()->route('manager.tables.index')->with('success', "Đã xóa bàn {$name}.");
        } catch (QueryException) {
            return back()->with('error', 'Không thể xóa bàn do dữ liệu liên quan.');
        }
    }
}
