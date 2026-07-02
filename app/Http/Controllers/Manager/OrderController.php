<?php

namespace App\Http\Controllers\Manager;

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
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    use NormalizesPayment, GeneratesOrderCode;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderInventoryService $inventoryService,
    ) {
    }
    // normalizePaymentStatus(), normalizePaymentMethod(), toPaymentRecordStatus()
    // => Đã chuyển sang NormalizesPayment trait

    // syncThanhToanRecord() => Đã chuyển sang PaymentService

    public function index(Request $request)
    {
        // Giới hạn khoảng lọc tối đa 6 tháng (chỉ khi người dùng tự chọn cả 2 mốc).
        if ($request->filled('tu_ngay') && $request->filled('den_ngay') && ! $request->header('X-Partial')) {
            $from = Carbon::parse($request->tu_ngay)->startOfDay();
            $to = Carbon::parse($request->den_ngay)->endOfDay();
            if ($to->lt($from)) {
                return redirect()->route('manager.orders.index')
                    ->with('error', 'Ngày kết thúc không được nhỏ hơn ngày bắt đầu.');
            }
            if ($from->copy()->addMonths(6)->lt($to)) {
                return redirect()->route('manager.orders.index')
                    ->with('error', 'Khoảng thời gian lọc không được vượt quá 6 tháng.');
            }
        }

        $query = DonHang::with(['nguoiDung', 'nhanVien', 'banAn', 'chiTietDonHang']);

        if (!$request->filled('tu_ngay') && !$request->filled('den_ngay')) {
            $now = now();
            $defaultStart = $now->copy()->startOfMonth()->toDateString();
            $defaultEnd = $now->copy()->endOfMonth()->toDateString();
            $request->merge([
                'tu_ngay' => $defaultStart,
                'den_ngay' => $defaultEnd,
            ]);
        }

        // Tìm kiếm
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ma_don_hang', 'like', "%$s%")
                    ->orWhereHas('nguoiDung', fn($u) => $u->where('email', 'like', "%$s%"));
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

        // Lọc theo trạng thái thanh toán (tab) - now on chi_tiet_don_hang
        if ($request->filled('pay_status')) {
            $query->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', $request->pay_status));
        }

        $orders = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        // Polling: chỉ trả bảng đơn (bỏ qua toàn bộ dữ liệu cho modal tạo đơn) để tự cập nhật.
        // Dùng header X-Partial để URL sạch, không rò vào link phân trang.
        if ($request->header('X-Partial')) {
            return response()->json([
                'html' => view('manager.orders.partials.list', compact('orders'))->render(),
            ]);
        }

        // Đếm tổng - use chi_tiet_don_hang for payment status
        $countAll = DonHang::count();
        $countUnpaid = DonHang::whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))->count();
        $countPaid = DonHang::whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'đã thanh toán'))->count();

        // Danh sách nhân viên cho filter
        $nhanViens = NguoiDung::where('vai_tro', 'nhân viên')
            ->where('trang_thai', 'hoạt động')
            ->orderBy('email')
            ->get();

        $customers = NguoiDung::query()
            ->where('vai_tro', 'khách hàng')
            ->where('trang_thai', 'hoạt động')
            ->orderBy('email')
            ->get(['id', 'email']);

        $banAns = BanAn::query()
            ->orderBy('so_ban')
            ->get(['id', 'so_ban', 'trang_thai']);

        $availableProducts = SanPham::query()
            ->where('trang_thai_ban', 'đang bán')
            ->with(['kichCo'])
            ->orderBy('ten_san_pham')
            ->get(['id', 'danh_muc_id', 'ten_san_pham', 'gia_goc', 'gia_khuyen_mai', 'nhiet_do']);

        $productSizeMap = [];
        foreach ($availableProducts as $product) {
            $basePrice = $product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc;
            $sizes = [];
            foreach ($product->kichCo ?? [] as $sizeItem) {
                $sizes[] = [
                    'id' => $sizeItem->id,
                    'name' => $sizeItem->ten_kich_co ?? ('Size #' . $sizeItem->id),
                    'price' => (float) ($basePrice * ($sizeItem->he_so_gia ?? 1)),
                ];
            }

            $productSizeMap[$product->id] = [
                'danh_muc_id' => $product->danh_muc_id,
                'base_price' => (float) $basePrice,
                'sizes' => $sizes,
                'temps' => array_values(array_filter(array_map('trim', explode(',', (string) $product->nhiet_do)))),
            ];
        }

        $categories = \App\Models\DanhMuc::where('trang_thai', 'đang dùng')
            ->orderBy('ten_danh_muc')
            ->get(['id', 'ten_danh_muc']);

        $totalOrders = $countAll;

        return view('manager.orders.index', compact(
            'orders',
            'totalOrders',
            'nhanViens',
            'customers',
            'banAns',
            'availableProducts',
            'productSizeMap',
            'categories',
            'countAll',
            'countUnpaid',
            'countPaid'
        ));
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        $loaiDon = $validated['loai_don'];
        // Mang về không gắn bàn; các loại còn lại lấy bàn đã chọn.
        $banAnId = $loaiDon === 'mang về' ? null : ($validated['ban_an_id'] ?? null);

        // Đặt trước = ngồi tại quán theo giờ hẹn: bắt buộc bàn TRỐNG + giờ hẹn ở tương lai
        // (giống luồng "đặt bàn" của khách thành viên).
        $thoiGianDen = null;
        if ($loaiDon === 'đặt hàng trước') {
            $ban = BanAn::find($banAnId);
            if (! $ban || $ban->trang_thai !== 'trống') {
                return back()->withErrors(['ban_an_id' => 'Đơn đặt trước phải chọn bàn đang trống.'])->withInput();
            }
            $den = Carbon::createFromFormat('H:i', $validated['thoi_gian_den'])->setDateFrom(now());
            if ($den->isPast()) {
                return back()->withErrors(['thoi_gian_den' => 'Giờ hẹn đến phải lớn hơn thời gian hiện tại.'])->withInput();
            }
            $thoiGianDen = $den->format('Y-m-d H:i:s');
        }

        $preparedItems = [];
        $tamTinh = 0.0;

        foreach ($validated['items'] as $index => $rawItem) {
            $product = SanPham::query()->findOrFail($rawItem['san_pham_id']);

            $sizeId = $rawItem['kich_co_id'] ?? null;
            $sizeName = null;
            $unitPrice = 0.0;

            if ($sizeId) {
                $sizeItem = $product->kichCo()->find($sizeId);

                if (!$sizeItem) {
                    throw ValidationException::withMessages([
                        "items.{$index}.kich_co_id" => 'Kích cỡ đã chọn không tồn tại.',
                    ]);
                }

                $sizeName = $sizeItem->ten_kich_co;
                $basePrice = (float) ($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc);
                $unitPrice = $basePrice * (float) ($sizeItem->he_so_gia ?? 1);
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

        $orderId = null;
        $merged = false;
        $paidImmediately = false;

        DB::transaction(function () use ($validated, $preparedItems, $loaiDon, $banAnId, $thoiGianDen, &$orderId, &$merged, &$paidImmediately): void {
            $phuongThuc = $validated['phuong_thuc_thanh_toan'] ?? null;
            $paidImmediately = !empty($phuongThuc);

            // BỔ SUNG món vào bàn đang phục vụ: nếu bàn đã có đơn (nhân viên/quản lý) còn
            // dòng chưa thanh toán và chưa đóng phiên → gộp món vào đơn đó thay vì tạo đơn rời.
            $targetOrder = null;
            if ($loaiDon === 'sử dụng ngay' && $banAnId) {
                $targetOrder = DonHang::where('ban_an_id', $banAnId)
                    ->whereNull('da_giao_luc')
                    ->whereNotNull('nhan_vien_id')
                    ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
                    ->latest()
                    ->first();
            }

            if ($targetOrder) {
                // Gộp món & chỉ trừ kho phần chênh lệch.
                $oldUsage = $this->inventoryService->ingredientUsageForOrder($targetOrder->id);
                $this->appendOrderItems($targetOrder, $preparedItems, $loaiDon, $phuongThuc, $thoiGianDen);
                $newUsage = $this->inventoryService->ingredientUsageForOrder($targetOrder->id);
                $this->inventoryService->applyIngredientDelta($oldUsage, $newUsage, $targetOrder->id);

                TableStatusService::refreshForTable($targetOrder->ban_an_id);
                // Gộp món KHÔNG tạo đơn mới → không có sự kiện created → chủ động báo nhân viên (để có chuông).
                $this->notifyStaffNewOrder($targetOrder);

                if ($paidImmediately) {
                    $this->paymentService->syncThanhToanRecord($targetOrder->fresh(), $phuongThuc, 'đã thanh toán');
                    $this->paymentService->applyTableStatusAfterPayment($targetOrder);
                }

                $orderId = $targetOrder->id;
                $merged = true;
                return;
            }

            $emailKhachHang = ($validated['nguoi_dung_id'] ?? null)
                ? NguoiDung::whereKey($validated['nguoi_dung_id'])->value('email')
                : null;

            $order = DonHang::create([
                'ma_don_hang' => $this->generateOrderCode(),
                'nguoi_dung_id' => $validated['nguoi_dung_id'] ?? null,
                'email_khach_hang' => $emailKhachHang,
                'nhan_vien_id' => Auth::id(),
                'ban_an_id' => $banAnId,
                'voucher_nguoi_dung_id' => null,
            ]);

            $this->appendOrderItems($order, $preparedItems, $loaiDon, $phuongThuc, $thoiGianDen);

            // Đơn đã xác nhận ngay -> xuất kho
            $this->inventoryService->exportIngredientsForOrder($order);
            TableStatusService::refreshForTable($order->ban_an_id);

            if ($paidImmediately) {
                $this->paymentService->syncThanhToanRecord($order->fresh(), $phuongThuc, 'đã thanh toán');
                $this->paymentService->applyTableStatusAfterPayment($order);
            }

            $orderId = $order->id;
        });

        if ($paidImmediately) {
            $freshOrder = DonHang::find($orderId);
            if ($freshOrder) {
                try {
                    if ($freshOrder->email_khach_hang) {
                        \Illuminate\Support\Facades\Mail::to($freshOrder->email_khach_hang)->queue(new \App\Mail\CustomerOrderPaidMail($freshOrder));
                    } elseif ($freshOrder->nguoiDung && $freshOrder->nguoiDung->email) {
                        \Illuminate\Support\Facades\Mail::to($freshOrder->nguoiDung->email)->queue(new \App\Mail\CustomerOrderPaidMail($freshOrder));
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Không thể gửi mail thanh toán khi tạo đơn:', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $statusMsg = $merged
            ? "Đã bổ sung món vào đơn #{$orderId} của bàn."
            : "Đã tạo đơn hàng #{$orderId} thành công.";

        return redirect()->route('manager.orders.index')
            ->with('success', $statusMsg);
    }

    /**
     * Ghi các dòng món đã chuẩn bị vào một đơn (đơn mới hoặc đơn đang bổ sung).
     */
    private function appendOrderItems(DonHang $order, array $preparedItems, string $loaiDon, ?string $phuongThuc, ?string $thoiGianDen): void
    {
        $trangThaiThanhToan = $phuongThuc ? 'đã thanh toán' : 'chưa thanh toán';

        foreach ($preparedItems as $item) {
            ChiTietDonHang::create([
                'don_hang_id' => $order->id,
                'san_pham_id' => $item['san_pham_id'],
                'kich_co_id' => $item['kich_co_id'],
                'ten_san_pham' => $item['ten_san_pham'],
                'ten_kich_co' => $item['ten_kich_co'],
                'don_gia' => $item['don_gia'],
                'so_luong' => $item['so_luong'],
                'ghi_chu_mon' => $item['ghi_chu_mon'],
                'thanh_tien' => $item['thanh_tien'],
                'loai_don' => $loaiDon,
                'trang_thai_thanh_toan' => $trangThaiThanhToan,
                'phuong_thuc_thanh_toan' => $phuongThuc,
                'so_tien_giam' => 0,
                'tong_tien' => $item['thanh_tien'],
                'thoi_gian_den' => $thoiGianDen,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Báo cho nhân viên/quản lý/chủ đang hoạt động khi có đơn/việc mới (để kêu chuông).
     * Dùng cho trường hợp GỘP món vào đơn sẵn có — vốn không kích hoạt sự kiện created.
     */
    private function notifyStaffNewOrder(DonHang $order): void
    {
        if (! Schema::hasTable('thong_bao')) {
            return;
        }

        try {
            // Chỉ báo NHÂN VIÊN (người pha chế) — quản lý không cần kêu chuông cho đơn tại quán.
            NguoiDung::query()
                ->where('vai_tro', 'nhân viên')
                ->where('trang_thai', 'hoạt động')
                ->get()
                ->each(fn($user) => $user->notify(new \App\Notifications\QrOrderPendingNotification($order)));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Không thể gửi thông báo bổ sung món.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
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

        // Đơn đã thanh toán không thể sửa: chuyển hướng "mềm" về trang chi tiết thay vì
        // ném 403. (Quyền vai trò đã được middleware role:quản lý,chủ cửa hàng đảm bảo.)
        if (!(new \App\Policies\OrderPolicy)->isEditable($order)) {
            return redirect()
                ->route('manager.orders.show', $order->id)
                ->with('warning', 'Đơn này đã thanh toán nên không thể chỉnh sửa.');
        }

        $banAns = BanAn::query()
            ->orderBy('so_ban')
            ->get(['id', 'so_ban', 'trang_thai']);

        $availableProducts = SanPham::query()
            ->where('trang_thai_ban', 'đang bán')
            ->with(['kichCo'])
            ->orderBy('ten_san_pham')
            ->get(['id', 'danh_muc_id', 'ten_san_pham', 'gia_goc', 'gia_khuyen_mai', 'nhiet_do']);

        $productSizeMap = [];
        foreach ($availableProducts as $product) {
            $basePrice = $product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc;
            $sizes = [];
            foreach ($product->kichCo ?? [] as $sizeItem) {
                $sizes[] = [
                    'id' => $sizeItem->id,
                    'name' => $sizeItem->ten_kich_co ?? ('Size #' . $sizeItem->id),
                    'price' => (float) ($basePrice * ($sizeItem->he_so_gia ?? 1)),
                ];
            }

            $productSizeMap[$product->id] = [
                'danh_muc_id' => $product->danh_muc_id,
                'base_price' => (float) $basePrice,
                'sizes' => $sizes,
                'temps' => array_values(array_filter(array_map('trim', explode(',', (string) $product->nhiet_do)))),
            ];
        }

        $categories = \App\Models\DanhMuc::where('trang_thai', 'đang dùng')
            ->orderBy('ten_danh_muc')
            ->get(['id', 'ten_danh_muc']);

        return view('manager.orders.edit', compact(
            'order',
            'banAns',
            'availableProducts',
            'productSizeMap',
            'categories'
        ));
    }

    public function update(UpdateOrderRequest $request, int $id)
    {
        $order = DonHang::with('chiTietDonHang')->findOrFail($id);
        Gate::authorize('update', $order);

        if (!(new \App\Policies\OrderPolicy)->isEditable($order)) {
            return back()->with('error', 'Chỉ có thể chỉnh sửa đơn đã xác nhận và chưa thanh toán.');
        }

        $validated = $request->validated();

        if ($order->loai_don !== 'đặt hàng trước' && empty($validated['ban_an_id'])) {
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
                $sizeItem = $product->kichCo()->find($sizeId);

                if (!$sizeItem) {
                    throw ValidationException::withMessages([
                        "items.{$index}.kich_co_id" => 'Kích cỡ đã chọn không tồn tại.',
                    ]);
                }

                $sizeName = $sizeItem->ten_kich_co;
                $basePrice = (float) ($product->gia_khuyen_mai > 0 ? $product->gia_khuyen_mai : $product->gia_goc);
                $unitPrice = $basePrice * (float) ($sizeItem->he_so_gia ?? 1);
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
        // Đặt trước & mang về: giữ nguyên bàn hiện tại của đơn (đặt trước có bàn trống đã chọn,
        // mang về không có bàn). Các đơn tại quán lấy bàn từ form.
        $selectedBanAnId = in_array($order->loai_don, ['đặt hàng trước', 'mang về'], true)
            ? $order->ban_an_id
            : ($validated['ban_an_id'] ?? null);

        DB::transaction(function () use ($order, $preparedItems, $tamTinh, $oldBanAnId, $oldUsage, $selectedBanAnId): void {
            $order->update([
                'ban_an_id' => $selectedBanAnId,
                'nhan_vien_id' => Auth::id() ?? $order->nhan_vien_id,
            ]);

            $order->chiTietDonHang()->delete();

            foreach ($preparedItems as $item) {
                ChiTietDonHang::create([
                    'don_hang_id' => $order->id,
                    'san_pham_id' => $item['san_pham_id'],
                    'kich_co_id' => $item['kich_co_id'],
                    'ten_san_pham' => $item['ten_san_pham'],
                    'ten_kich_co' => $item['ten_kich_co'],
                    'don_gia' => $item['don_gia'],
                    'so_luong' => $item['so_luong'],
                    'ghi_chu_mon' => $item['ghi_chu_mon'],
                    'thanh_tien' => $item['thanh_tien'],
                    'tong_tien' => $item['thanh_tien'],
                    'created_at' => now(),
                ]);
            }

            TableStatusService::refreshForTable($order->ban_an_id);

            if ($oldBanAnId && $oldBanAnId !== $order->ban_an_id) {
                $hasActiveOrders = DonHang::query()
                    ->where('ban_an_id', $oldBanAnId)
                    ->whereHas('chiTietDonHang', fn($q) => $q->where('trang_thai_thanh_toan', 'chưa thanh toán'))
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

        if ($order->nguoiDung && Schema::hasTable('thong_bao')) {
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

    public function updatePayment(Request $request, int $id)
    {
        $order = DonHang::findOrFail($id);
        Gate::authorize('updatePayment', $order);

        $request->validate([
            'phuong_thuc_thanh_toan' => 'required|string|max:50',
            'trang_thai_thanh_toan' => 'required|string|max:50',
            'email_khach_hang' => 'nullable|email|max:255',
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

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus, $user, $request): void {
            // Update payment on chi_tiet_don_hang
            $order->chiTietDonHang()->update([
                'phuong_thuc_thanh_toan' => $paymentMethod,
                'trang_thai_thanh_toan' => $paymentStatus,
            ]);

            $emailKhachHang = $request->input('email_khach_hang') ?? $order->email_khach_hang;

            $updateData = [
                'nhan_vien_id' => $user?->id,
                'email_khach_hang' => $emailKhachHang,
            ];

            // Đơn order tại quầy chưa gắn tài khoản: nếu email khớp một tài khoản
            // khách hàng đã đăng ký thì tự liên kết để khách vẫn có quyền đánh giá
            // khi đăng nhập lại (dùng lại logic đánh giá theo nguoi_dung_id).
            if (!$order->nguoi_dung_id) {
                $khachHang = NguoiDung::khachHangByEmail($emailKhachHang);
                if ($khachHang) {
                    $updateData['nguoi_dung_id'] = $khachHang->id;
                }
            }

            $order->update($updateData);

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

        return redirect()->route('manager.orders.index')->with('success', "Đã cập nhật thanh toán cho đơn #{$order->id}.");
    }



    public function destroy(int $id)
    {
        $order = DonHang::findOrFail($id);

        $banAnId = $order->ban_an_id;

        DB::transaction(function () use ($order) {
            $this->inventoryService->restoreIngredientsForOrder($order);

            $order->chiTietDonHang()->delete();
            $order->thanhToan()->delete();
            $order->delete();
        });

        if ($banAnId) {
            TableStatusService::refreshForTable($banAnId);
        }

        return redirect()->route('manager.orders.index')->with('success', "Đã xóa đơn hàng #{$order->id} và cập nhật lại kho, bàn.");
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
