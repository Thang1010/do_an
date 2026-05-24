<?php

namespace App\Http\Controllers\Manager;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TableStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\BanAn;
use App\Models\ChiTietDonHang;
use App\Models\CuaHang;
use App\Models\DonHang;
use App\Models\KichCo;
use App\Models\LichSuKho;
use App\Models\NguoiDung;
use App\Models\SanPham;
use App\Models\SanPhamKichCo;
use App\Models\ThanhToan;
use App\Notifications\OrderUpdatedNotification;
use App\Http\Requests\Manager\StoreOrderRequest;
use App\Http\Requests\Manager\UpdateOrderRequest;
use App\Services\OrderInventoryService;
use App\Services\PaymentService;
use App\Services\TableStatusService;
use App\Traits\GeneratesOrderCode;
use App\Traits\NormalizesPayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use NormalizesPayment, GeneratesOrderCode;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderInventoryService $inventoryService,
    ) {}
    private function normalizeOrderStatus(?string $status): ?string
    {
        return OrderStatus::normalize($status)?->value;
    }

    // normalizePaymentStatus(), normalizePaymentMethod(), toPaymentRecordStatus()
    // => Đã chuyển sang NormalizesPayment trait

    // syncThanhToanRecord() => Đã chuyển sang PaymentService

    public function index(Request $request)
    {
        $query = DonHang::with(['nguoiDung', 'nhanVien', 'banAn']);

        $today = now()->toDateString();
        if (!$request->filled('tu_ngay') && !$request->filled('den_ngay')) {
            $request->merge([
                'tu_ngay' => $today,
                'den_ngay' => $today,
            ]);
        }

        // Lọc theo trạng thái
        $normalizedStatus = null;
        if ($request->filled('status')) {
            $normalizedStatus = $this->normalizeOrderStatus($request->status);
            if ($normalizedStatus !== null) {
                $query->where('trang_thai_don', $normalizedStatus);
            }
        }

        // Tìm kiếm
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ma_don_hang', 'like', "%$s%")
                    ->orWhere('ten_khach_hang', 'like', "%$s%")
                    ->orWhere('so_dien_thoai_khach', 'like', "%$s%")
                    ->orWhereHas('nguoiDung', fn($u) => $u->where('ho_ten', 'like', "%$s%"));
            });
        }

        // Lọc theo ngày (bao gồm đơn đã thanh toán trong ngày)
        $fromDate = $request->filled('tu_ngay')
            ? Carbon::parse($request->tu_ngay)->startOfDay()
            : null;
        $toDate = $request->filled('den_ngay')
            ? Carbon::parse($request->den_ngay)->endOfDay()
            : null;

        if ($fromDate || $toDate) {
            $query->where(function (Builder $sub) use ($fromDate, $toDate) {
                if ($fromDate && $toDate) {
                    $sub->whereBetween('created_at', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $sub->where('created_at', '>=', $fromDate);
                } else {
                    $sub->where('created_at', '<=', $toDate);
                }

                $sub->orWhereHas('thanhToan', function (Builder $pay) use ($fromDate, $toDate) {
                    $pay->where('trang_thai', 'đã thanh toán');

                    if ($fromDate && $toDate) {
                        $pay->whereBetween('thanh_toan_luc', [$fromDate, $toDate]);
                    } elseif ($fromDate) {
                        $pay->where('thanh_toan_luc', '>=', $fromDate);
                    } else {
                        $pay->where('thanh_toan_luc', '<=', $toDate);
                    }
                });
            });
        }

        // Lọc theo nhân viên
        if ($request->filled('nhan_vien_id')) {
            $query->where('nhan_vien_id', $request->nhan_vien_id);
        }

        if ($normalizedStatus === null) {
            $query->orderByRaw("CASE WHEN trang_thai_don = 'đã hủy' THEN 2 WHEN trang_thai_thanh_toan = 'chưa thanh toán' THEN 0 ELSE 1 END");
        }

        $orders = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Đếm từng trạng thái
        $countAll = DonHang::count();
        $countPending = DonHang::where('trang_thai_don', 'chờ xác nhận')->count();
        $countConfirmed = DonHang::where('trang_thai_don', 'đã xác nhận')->count();
        $countCancelled = DonHang::where('trang_thai_don', 'đã hủy')->count();

        // Danh sách nhân viên cho filter
        $nhanViens = NguoiDung::where('vai_tro', 'nhân viên')
            ->where('trang_thai', 'hoạt động')
            ->orderBy('ho_ten')
            ->get();

        $customers = NguoiDung::query()
            ->where('vai_tro', 'khách hàng')
            ->where('trang_thai', 'hoạt động')
            ->orderBy('ho_ten')
            ->get(['id', 'ho_ten', 'so_dien_thoai']);

        $banAns = BanAn::query()
            ->orderBy('so_ban')
            ->get(['id', 'so_ban', 'trang_thai']);

        $availableProducts = SanPham::query()
            ->where('trang_thai_ban', 'đang bán')
            ->with([
                'sanPhamKichCo' => function ($q) {
                    $q->where('trang_thai', 'đang bán')
                        ->with('kichCo:id,ten_kich_co');
                }
            ])
            ->orderBy('ten_san_pham')
            ->get(['id', 'ten_san_pham', 'gia_goc', 'gia_khuyen_mai']);

        $productSizeMap = [];
        foreach ($availableProducts as $product) {
            $sizes = [];
            foreach ($product->sanPhamKichCo ?? [] as $sizeLink) {
                $sizes[] = [
                    'id' => $sizeLink->kich_co_id,
                    'name' => $sizeLink->kichCo?->ten_kich_co ?? ('Size #' . $sizeLink->kich_co_id),
                ];
            }

            $productSizeMap[$product->id] = [
                'sizes' => $sizes,
            ];
        }

        $totalOrders = $countAll;

        return view('manager.orders.index', compact(
            'orders',
            'totalOrders',
            'nhanViens',
            'customers',
            'banAns',
            'availableProducts',
            'productSizeMap',
            'countAll',
            'countPending',
            'countConfirmed',
            'countCancelled'
        ));
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();

        if ($validated['loai_don'] !== 'đặt online' && empty($validated['ban_an_id'])) {
            return back()->withErrors([
                'ban_an_id' => 'Đơn tại quán hoặc gọi tại bàn bằng QR bắt buộc chọn bàn ăn.',
            ])->withInput();
        }

        $preparedItems = [];
        $tamTinh = 0.0;

        foreach ($validated['items'] as $index => $rawItem) {
            $product = SanPham::query()->findOrFail($rawItem['san_pham_id']);

            $sizeId = $rawItem['kich_co_id'] ?? null;
            $sizeName = null;
            $unitPrice = 0.0;

            if ($sizeId) {
                $sizePrice = SanPhamKichCo::query()
                    ->with('kichCo:id,ten_kich_co')
                    ->where('san_pham_id', $product->id)
                    ->where('kich_co_id', $sizeId)
                    ->first();

                if (!$sizePrice) {
                    throw ValidationException::withMessages([
                        "items.{$index}.kich_co_id" => 'Kích cỡ đã chọn không thuộc sản phẩm tương ứng.',
                    ]);
                }

                $sizeName = $sizePrice->kichCo?->ten_kich_co;
                $unitPrice = (float) ($sizePrice->gia_khuyen_mai ?? $sizePrice->gia_ban ?? 0);
            } else {
                $unitPrice = (float) ($product->gia_khuyen_mai ?? $product->gia_goc ?? 0);
            }

            if ($unitPrice <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.san_pham_id" => "Giá bán của {$product->ten_san_pham} không hợp lệ.",
                ]);
            }

            $soLuong = (int) $rawItem['so_luong'];
            $thanhTien = $unitPrice * $soLuong;
            $tamTinh += $thanhTien;

            $preparedItems[] = [
                'san_pham_id' => $product->id,
                'kich_co_id' => $sizeId,
                'ten_san_pham' => $product->ten_san_pham,
                'ten_kich_co' => $sizeName,
                'don_gia' => $unitPrice,
                'so_luong' => $soLuong,
                'ghi_chu_mon' => $this->normalizeNullable($rawItem['ghi_chu_mon'] ?? null),
                'thanh_tien' => $thanhTien,
            ];
        }

        $selectedCustomer = null;
        if (!empty($validated['nguoi_dung_id'])) {
            $selectedCustomer = NguoiDung::query()->find($validated['nguoi_dung_id']);
        }

        $orderId = null;

        DB::transaction(function () use ($validated, $selectedCustomer, $preparedItems, $tamTinh, &$orderId): void {
            $banAnId = $validated['loai_don'] === 'đặt online'
                ? null
                : ($validated['ban_an_id'] ?? null);

            // Đơn do chủ cửa hàng / quản lý / nhân viên tạo → tự động xác nhận
            // Chỉ đơn khách hàng đặt mới cần chờ xác nhận
            $currentUser = Auth::user();
            $isStaffOrder = $currentUser && in_array($currentUser->vai_tro, UserRole::staffRoleValues(), true);
            $orderStatus = $isStaffOrder ? OrderStatus::DA_XAC_NHAN->value : OrderStatus::CHO_XAC_NHAN->value;

            $order = DonHang::create([
                'ma_don_hang' => $this->generateOrderCode(),
                'nguoi_dung_id' => $validated['nguoi_dung_id'] ?? null,
                'nhan_vien_id' => Auth::id(),
                'ban_an_id' => $banAnId,
                'voucher_nguoi_dung_id' => null,
                'loai_don' => $validated['loai_don'],
                'trang_thai_don' => $orderStatus,
                'trang_thai_thanh_toan' => 'chưa thanh toán',
                'phuong_thuc_thanh_toan' => $validated['phuong_thuc_thanh_toan'] ?? null,
                'tam_tinh' => $tamTinh,
                'so_tien_giam' => 0,
                'tong_tien' => $tamTinh,
                'ghi_chu' => $this->normalizeNullable($validated['ghi_chu'] ?? null),
                'ten_khach_hang' => $this->normalizeNullable($validated['ten_khach_hang'] ?? null)
                    ?? $selectedCustomer?->ho_ten,
                'so_dien_thoai_khach' => $this->normalizeNullable($validated['so_dien_thoai_khach'] ?? null)
                    ?? $selectedCustomer?->so_dien_thoai,
                'dia_chi_giao_hang' => $this->normalizeNullable($validated['dia_chi_giao_hang'] ?? null),
            ]);

            foreach ($preparedItems as $item) {
                ChiTietDonHang::create([
                    'don_hang_id' => $order->id,
                    'ban_an_id' => $order->ban_an_id,
                    'san_pham_id' => $item['san_pham_id'],
                    'kich_co_id' => $item['kich_co_id'],
                    'ten_san_pham' => $item['ten_san_pham'],
                    'ten_kich_co' => $item['ten_kich_co'],
                    'don_gia' => $item['don_gia'],
                    'so_luong' => $item['so_luong'],
                    'ghi_chu_mon' => $item['ghi_chu_mon'],
                    'thanh_tien' => $item['thanh_tien'],
                    'created_at' => now(),
                ]);
            }

            // Tự động xuất kho nếu đơn được xác nhận ngay
            if ($isStaffOrder) {
                $this->inventoryService->exportIngredientsForOrder($order);
            }

            if ($order->ban_an_id) {
                BanAn::query()->whereKey($order->ban_an_id)->update([
                    'trang_thai' => 'đang phục vụ',
                ]);
            }

            $orderId = $order->id;
        });

        $statusMsg = (Auth::user() && in_array(Auth::user()->vai_tro, UserRole::staffRoleValues(), true))
            ? "Đã tạo và xác nhận đơn hàng #{$orderId} thành công."
            : "Đã tạo đơn hàng #{$orderId}, đang chờ xác nhận.";

        return redirect()->route('manager.orders.index')
            ->with('success', $statusMsg);
    }

    public function show(int $id)
    {
        $order = DonHang::with([
            'nguoiDung',
            'nhanVien',
            'banAn',
            'chiTietDonHang.sanPham',
            'chiTietDonHang.kichCo',
            'voucherNguoiDung.voucher',
            'thanhToan',
        ])->findOrFail($id);

        return view('manager.orders.show', compact('order'));
    }

    public function edit(int $id)
    {
        $order = DonHang::with([
            'nguoiDung',
            'nhanVien',
            'banAn',
            'chiTietDonHang.sanPham',
            'chiTietDonHang.kichCo',
        ])->findOrFail($id);

        $this->authorize('update', $order);

        if (!(new \App\Policies\OrderPolicy)->isEditable($order)) {
            return redirect()
                ->route('manager.orders.show', $order->id)
                ->with('error', 'Chỉ có thể chỉnh sửa đơn đã xác nhận và chưa thanh toán.');
        }

        $banAns = BanAn::query()
            ->orderBy('so_ban')
            ->get(['id', 'so_ban', 'trang_thai']);

        $availableProducts = SanPham::query()
            ->where('trang_thai_ban', 'đang bán')
            ->with([
                'sanPhamKichCo' => function ($q) {
                    $q->where('trang_thai', 'đang bán')
                        ->with('kichCo:id,ten_kich_co');
                }
            ])
            ->orderBy('ten_san_pham')
            ->get(['id', 'ten_san_pham', 'gia_goc', 'gia_khuyen_mai']);

        $productSizeMap = [];
        foreach ($availableProducts as $product) {
            $sizes = [];
            foreach ($product->sanPhamKichCo ?? [] as $sizeLink) {
                $sizes[] = [
                    'id' => $sizeLink->kich_co_id,
                    'name' => $sizeLink->kichCo?->ten_kich_co ?? ('Size #' . $sizeLink->kich_co_id),
                ];
            }

            $productSizeMap[$product->id] = [
                'sizes' => $sizes,
            ];
        }

        return view('manager.orders.edit', compact(
            'order',
            'banAns',
            'availableProducts',
            'productSizeMap'
        ));
    }

    public function update(UpdateOrderRequest $request, int $id)
    {
        $order = DonHang::with('chiTietDonHang')->findOrFail($id);
        $this->authorize('update', $order);

        if (!(new \App\Policies\OrderPolicy)->isEditable($order)) {
            return back()->with('error', 'Chỉ có thể chỉnh sửa đơn đã xác nhận và chưa thanh toán.');
        }

        $validated = $request->validated();

        if ($order->loai_don !== 'đặt online' && empty($validated['ban_an_id'])) {
            return back()->withErrors([
                'ban_an_id' => 'Đơn tại quán hoặc gọi tại bàn bằng QR bắt buộc chọn bàn ăn.',
            ])->withInput();
        }

        $preparedItems = [];
        $tamTinh = 0.0;

        foreach ($validated['items'] as $index => $rawItem) {
            $product = SanPham::query()->findOrFail($rawItem['san_pham_id']);

            $sizeId = $rawItem['kich_co_id'] ?? null;
            $sizeName = null;
            $unitPrice = 0.0;

            if ($sizeId) {
                $sizePrice = SanPhamKichCo::query()
                    ->with('kichCo:id,ten_kich_co')
                    ->where('san_pham_id', $product->id)
                    ->where('kich_co_id', $sizeId)
                    ->first();

                if (!$sizePrice) {
                    throw ValidationException::withMessages([
                        "items.{$index}.kich_co_id" => 'Kích cỡ đã chọn không thuộc sản phẩm tương ứng.',
                    ]);
                }

                $sizeName = $sizePrice->kichCo?->ten_kich_co;
                $unitPrice = (float) ($sizePrice->gia_khuyen_mai ?? $sizePrice->gia_ban ?? 0);
            } else {
                $unitPrice = (float) ($product->gia_khuyen_mai ?? $product->gia_goc ?? 0);
            }

            if ($unitPrice <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.san_pham_id" => "Giá bán của {$product->ten_san_pham} không hợp lệ.",
                ]);
            }

            $soLuong = (int) $rawItem['so_luong'];
            $thanhTien = $unitPrice * $soLuong;
            $tamTinh += $thanhTien;

            $preparedItems[] = [
                'san_pham_id' => $product->id,
                'kich_co_id' => $sizeId,
                'ten_san_pham' => $product->ten_san_pham,
                'ten_kich_co' => $sizeName,
                'don_gia' => $unitPrice,
                'so_luong' => $soLuong,
                'ghi_chu_mon' => $this->normalizeNullable($rawItem['ghi_chu_mon'] ?? null),
                'thanh_tien' => $thanhTien,
            ];
        }

        $oldBanAnId = $order->ban_an_id;
        $oldUsage = $this->inventoryService->ingredientUsageForOrder($order->id);
        $selectedBanAnId = $order->loai_don === 'đặt online'
            ? null
            : ($validated['ban_an_id'] ?? null);

        DB::transaction(function () use ($order, $preparedItems, $tamTinh, $oldBanAnId, $oldUsage, $selectedBanAnId): void {
            $order->update([
                'ban_an_id' => $selectedBanAnId,
                'tam_tinh' => $tamTinh,
                'tong_tien' => max($tamTinh - (float) ($order->so_tien_giam ?? 0), 0),
                'nhan_vien_id' => Auth::id() ?? $order->nhan_vien_id,
            ]);

            $order->chiTietDonHang()->delete();

            foreach ($preparedItems as $item) {
                ChiTietDonHang::create([
                    'don_hang_id' => $order->id,
                    'ban_an_id' => $order->ban_an_id,
                    'san_pham_id' => $item['san_pham_id'],
                    'kich_co_id' => $item['kich_co_id'],
                    'ten_san_pham' => $item['ten_san_pham'],
                    'ten_kich_co' => $item['ten_kich_co'],
                    'don_gia' => $item['don_gia'],
                    'so_luong' => $item['so_luong'],
                    'ghi_chu_mon' => $item['ghi_chu_mon'],
                    'thanh_tien' => $item['thanh_tien'],
                    'created_at' => now(),
                ]);
            }

            if ($order->ban_an_id) {
                BanAn::query()->whereKey($order->ban_an_id)->update([
                    'trang_thai' => TableStatus::DANG_PHUC_VU->value,
                ]);
            }

            if ($oldBanAnId && $oldBanAnId !== $order->ban_an_id) {
                $hasActiveOrders = DonHang::query()
                    ->where('ban_an_id', $oldBanAnId)
                    ->where('trang_thai_don', '!=', 'đã hủy')
                    ->where('trang_thai_thanh_toan', 'chưa thanh toán')
                    ->exists();

                if (!$hasActiveOrders) {
                    BanAn::query()->whereKey($oldBanAnId)->update([
                        'trang_thai' => TableStatus::TRONG->value,
                    ]);
                }
            }

            $newUsage = $this->inventoryService->ingredientUsageForOrder($order->id);
            $this->inventoryService->applyIngredientDelta($oldUsage, $newUsage, $order->id);
        });

        if ($order->nguoiDung && Schema::hasTable('notifications')) {
            try {
                $order->nguoiDung->notify(new OrderUpdatedNotification($order->fresh(), Auth::user()));
            } catch (\Throwable $e) {
                // ignore notification failure
            }
        }

        return redirect()
            ->route('manager.orders.show', $order->id)
            ->with('success', "Đã cập nhật đơn hàng #{$order->id}.");
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'trang_thai' => 'required|in:đã xác nhận,đã hủy',
        ]);

        $order = DonHang::findOrFail($id);

        if ($order->trang_thai_don !== 'chờ xác nhận') {
            return back()->with('error', 'Chỉ có thể xác nhận hoặc hủy đơn đang chờ xác nhận.');
        }

        if ($request->trang_thai === 'đã xác nhận' && !$order->chiTietDonHang()->exists()) {
            return back()->with('error', 'Không thể xác nhận đơn khi chưa có món nào trong chi tiết đơn hàng.');
        }

        if ($request->trang_thai === 'đã xác nhận' && !$order->ban_an_id) {
            return back()->with('error', 'Vui lòng chọn bàn trước khi xác nhận đơn hàng.');
        }

        try {
            DB::transaction(function () use ($order, $request): void {
                if ($request->trang_thai === 'đã xác nhận') {
                    $this->inventoryService->exportIngredientsForOrder($order);
                }

                $order->update([
                    'trang_thai_don' => $request->trang_thai,
                    'nhan_vien_id' => Auth::id() ?? $order->nhan_vien_id,
                    'ghi_chu' => $request->ghi_chu ?? $order->ghi_chu,
                ]);
            });
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        TableStatusService::refreshForTable($order->ban_an_id);

        return redirect()->route('manager.orders.index')->with('success', "Đơn hàng #{$order->id} đã được cập nhật trạng thái.");
    }

    public function updatePayment(Request $request, int $id)
    {
        $order = DonHang::findOrFail($id);
        $this->authorize('updatePayment', $order);

        $request->validate([
            'phuong_thuc_thanh_toan' => 'required|string|max:50',
            'trang_thai_thanh_toan' => 'required|string|max:50',
        ]);

        $paymentMethod = $this->normalizePaymentMethod($request->input('phuong_thuc_thanh_toan'));
        $paymentStatus = $this->normalizePaymentStatus($request->input('trang_thai_thanh_toan'));

        if (!$paymentStatus) {
            return back()->with('error', 'Trạng thái thanh toán không hợp lệ.');
        }

        if (!$paymentMethod) {
            return back()->with('error', 'Phương thức thanh toán không hợp lệ.');
        }

        $user = Auth::user();

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus, $user): void {
            $order->update([
                'phuong_thuc_thanh_toan' => $paymentMethod,
                'trang_thai_thanh_toan' => $paymentStatus,
                'nhan_vien_id' => $user?->id,
            ]);

            $this->paymentService->syncThanhToanRecord($order->fresh(), $paymentMethod, $paymentStatus);

            if ($paymentStatus === 'đã thanh toán') {
                $this->paymentService->freeTableIfAllPaid($order->ban_an_id);
            }
        });

        return redirect()->route('manager.orders.index')->with('success', "Đã cập nhật thanh toán cho đơn #{$order->id}.");
    }

    public function generatePaymentQr(Request $request, int $id): JsonResponse
    {
        $order = DonHang::with('banAn')->findOrFail($id);

        if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
            return response()->json([
                'message' => 'Đơn hàng đã được thanh toán.',
            ], 422);
        }

        $storeId = $request->user()?->cua_hang_id;
        $store = $this->paymentService->resolveStoreForPayment($storeId);

        if (!$store || !$store->chu_cua_hang_id || !$store->so_tai_khoan || !$store->ngan_hang) {
            return response()->json([
                'message' => 'Chưa có thông tin tài khoản thanh toán của chủ cửa hàng để tạo QR.',
            ], 422);
        }

        $qrData = $this->paymentService->generateQrDataWithCache($order, $store);

        if (!$qrData) {
            return response()->json([
                'message' => 'Thông tin ngân hàng hoặc số tài khoản chưa hợp lệ hoặc đơn hàng chưa có tổng tiền.',
            ], 422);
        }

        return response()->json($qrData);
    }

    // resolveVietQrBankCode() => Đã chuyển sang PaymentService
    // generateOrderCode() => Đã chuyển sang GeneratesOrderCode trait
    // ingredientUsageForOrder(), applyIngredientDelta(), exportIngredientsForOrder()
    // => Đã chuyển sang OrderInventoryService

    private function normalizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    // canEditOrder() => Đã chuyển sang OrderPolicy::isEditable()
}
