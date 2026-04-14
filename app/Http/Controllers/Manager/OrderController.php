<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BanAn;
use App\Models\ChiTietDonHang;
use App\Models\DonHang;
use App\Models\KichCo;
use App\Models\NguoiDung;
use App\Models\SanPham;
use App\Models\SanPhamKichCo;
use App\Models\ThanhToan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    private function normalizeOrderStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return match ($status) {
            'cho_xac_nhan', 'chờ xác nhận' => 'chờ xác nhận',
            'đã xác nhận', 'dang_pha_che', 'đang pha chế', 'hoan_thanh', 'hoàn thành', 'da_giao', 'đã giao', 'da_nhan', 'đã nhận' => 'đã xác nhận',
            'huy', 'đã hủy' => 'đã hủy',
            default => null,
        };
    }

    private function normalizePaymentStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return match ($status) {
            'chua_thanh_toan', 'chưa thanh toán' => 'chưa thanh toán',
            'da_thanh_toan', 'đã thanh toán' => 'đã thanh toán',
            'that_bai', 'thất bại', 'thanh toán thất bại' => 'thất bại',
            default => null,
        };
    }

    private function normalizePaymentMethod(?string $method): ?string
    {
        if ($method === null || $method === '') {
            return null;
        }

        return match ($method) {
            'tien_mat', 'tiền mặt' => 'tiền mặt',
            'chuyen_khoan', 'chuyển khoản' => 'chuyển khoản',
            default => null,
        };
    }

    private function toPaymentRecordStatus(string $orderPaymentStatus): string
    {
        return match ($orderPaymentStatus) {
            'đã thanh toán' => 'đã thanh toán',
            'thất bại' => 'thanh toán thất bại',
            default => 'chờ thanh toán',
        };
    }

    private function syncThanhToanRecord(DonHang $order, ?string $paymentMethod, string $paymentStatus): void
    {
        $record = ThanhToan::query()
            ->where('don_hang_id', $order->id)
            ->latest('id')
            ->first();

        $method = $paymentMethod ?: ($record->phuong_thuc ?? $order->phuong_thuc_thanh_toan ?? 'chuyển khoản');
        if (! in_array($method, ['tiền mặt', 'chuyển khoản'], true)) {
            $method = 'chuyển khoản';
        }

        $payload = [
            'phuong_thuc' => $method,
            'so_tien' => (float) ($order->tong_tien ?? 0),
            'trang_thai' => $this->toPaymentRecordStatus($paymentStatus),
            'thanh_toan_luc' => $paymentStatus === 'đã thanh toán' ? now() : null,
            'noi_dung_chuyen_khoan' => $method === 'chuyển khoản'
                ? ('TT ' . ($order->ma_don_hang ?? ('DON' . $order->id)))
                : null,
        ];

        if ($record) {
            $record->update($payload);
            return;
        }

        ThanhToan::create(array_merge([
            'don_hang_id' => $order->id,
        ], $payload));
    }

    public function index(Request $request)
    {
        $query = DonHang::with(['nguoiDung', 'nhanVien', 'banAn'])->latest();

        // Lọc theo trạng thái
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

        // Lọc theo ngày
        if ($request->filled('tu_ngay')) {
            $query->whereDate('created_at', '>=', $request->tu_ngay);
        }
        if ($request->filled('den_ngay')) {
            $query->whereDate('created_at', '<=', $request->den_ngay);
        }

        // Lọc theo nhân viên
        if ($request->filled('nhan_vien_id')) {
            $query->where('nhan_vien_id', $request->nhan_vien_id);
        }

        $orders = $query->paginate(20)->withQueryString();

        // Đếm từng trạng thái
        $countAll       = DonHang::count();
        $countPending   = DonHang::where('trang_thai_don', 'chờ xác nhận')->count();
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
            'orders', 'totalOrders', 'nhanViens', 'customers', 'banAns', 'availableProducts', 'productSizeMap',
            'countAll', 'countPending', 'countConfirmed', 'countCancelled'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nguoi_dung_id' => 'nullable|exists:nguoi_dung,id',
            'ban_an_id' => 'nullable|exists:ban_an,id',
            'loai_don' => 'required|in:đặt online,mua tại quán,gọi tại bàn bằng qr',
            'phuong_thuc_thanh_toan' => 'nullable|in:tiền mặt,chuyển khoản',
            'ten_khach_hang' => 'nullable|string|max:150',
            'so_dien_thoai_khach' => 'nullable|string|max:20',
            'dia_chi_giao_hang' => 'nullable|string',
            'ghi_chu' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.san_pham_id' => 'required|integer|exists:san_pham,id',
            'items.*.kich_co_id' => 'nullable|integer|exists:kich_co,id',
            'items.*.so_luong' => 'required|integer|min:1|max:1000',
            'items.*.ghi_chu_mon' => 'nullable|string|max:255',
        ], [
            'loai_don.required' => 'Vui lòng chọn loại đơn.',
            'loai_don.in' => 'Loại đơn hàng không hợp lệ.',
            'ban_an_id.exists' => 'Bàn ăn không tồn tại.',
            'items.required' => 'Vui lòng thêm ít nhất 1 món vào đơn.',
            'items.*.san_pham_id.required' => 'Vui lòng chọn sản phẩm cho từng dòng món.',
            'items.*.so_luong.min' => 'Số lượng mỗi món phải từ 1 trở lên.',
        ]);

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

                if (! $sizePrice) {
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
        if (! empty($validated['nguoi_dung_id'])) {
            $selectedCustomer = NguoiDung::query()->find($validated['nguoi_dung_id']);
        }

        $orderId = null;

        DB::transaction(function () use ($validated, $selectedCustomer, $preparedItems, $tamTinh, &$orderId): void {
            $banAnId = $validated['loai_don'] === 'đặt online'
                ? null
                : ($validated['ban_an_id'] ?? null);

            $order = DonHang::create([
                'ma_don_hang' => $this->generateOrderCode(),
                'nguoi_dung_id' => $validated['nguoi_dung_id'] ?? null,
                'nhan_vien_id' => Auth::id(),
                'ban_an_id' => $banAnId,
                'voucher_nguoi_dung_id' => null,
                'loai_don' => $validated['loai_don'],
                'trang_thai_don' => 'chờ xác nhận',
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

            if ($order->ban_an_id) {
                BanAn::query()->whereKey($order->ban_an_id)->update([
                    'trang_thai' => 'đang phục vụ',
                ]);
            }

            $orderId = $order->id;
        });

        return redirect()->route('manager.orders.show', $orderId)
            ->with('success', "Đã tạo đơn hàng #{$orderId} thành công.");
    }

    public function show(int $id)
    {
        $order = DonHang::with([
            'nguoiDung', 'nhanVien', 'banAn',
            'chiTietDonHang.sanPham',
            'chiTietDonHang.kichCo',
            'voucherNguoiDung.voucher',
            'thanhToan', 'lichSuDiemThuong',
        ])->findOrFail($id);

        return view('manager.orders.show', compact('order'));
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

        if ($request->trang_thai === 'đã xác nhận' && ! $order->chiTietDonHang()->exists()) {
            return back()->with('error', 'Không thể xác nhận đơn khi chưa có món nào trong chi tiết đơn hàng.');
        }

        $order->update([
            'trang_thai_don' => $request->trang_thai,
            'nhan_vien_id'   => Auth::id() ?? $order->nhan_vien_id,
            'ghi_chu'        => $request->ghi_chu ?? $order->ghi_chu,
        ]);

        return back()->with('success', "Đơn hàng #{$order->id} đã được cập nhật trạng thái.");
    }

    public function updatePayment(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->vai_tro, ['quản lý', 'nhân viên'], true)) {
            abort(403, 'Bạn không có quyền cập nhật thanh toán.');
        }

        $request->validate([
            'phuong_thuc_thanh_toan' => 'nullable|string|max:50',
            'trang_thai_thanh_toan' => 'required|string|max:50',
        ]);

        $paymentMethod = $this->normalizePaymentMethod($request->input('phuong_thuc_thanh_toan'));
        $paymentStatus = $this->normalizePaymentStatus($request->input('trang_thai_thanh_toan'));

        if (! $paymentStatus) {
            return back()->with('error', 'Trạng thái thanh toán không hợp lệ.');
        }

        if ($request->filled('phuong_thuc_thanh_toan') && ! $paymentMethod) {
            return back()->with('error', 'Phương thức thanh toán không hợp lệ.');
        }

        $order = DonHang::findOrFail($id);

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus, $user): void {
            $order->update([
                'phuong_thuc_thanh_toan' => $paymentMethod,
                'trang_thai_thanh_toan' => $paymentStatus,
                'nhan_vien_id' => $user->id,
            ]);

            $this->syncThanhToanRecord($order->fresh(), $paymentMethod, $paymentStatus);
        });

        return back()->with('success', "Đã cập nhật thanh toán cho đơn #{$order->id}.");
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'DH' . now()->format('ymdHis') . random_int(10, 99);
        } while (DonHang::query()->where('ma_don_hang', $code)->exists());

        return $code;
    }

    private function normalizeNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
