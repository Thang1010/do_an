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
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\NormalizesPayment;
use App\Traits\ResolvesVietQrBank;

class TableController extends Controller
{
    use NormalizesPayment, ResolvesVietQrBank;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}
    // normalizePaymentStatus(), normalizePaymentMethod() => NormalizesPayment trait
    // resolveVietQrBankCode() => ResolvesVietQrBank trait

    private function toDbTrangThai(?string $status): string
    {
        return TableStatus::normalize($status)->value;
    }

    public function index(Request $request)
    {
        $query = BanAn::query()
            ->withCount([
                'donHang as so_don_chua_thanh_toan' => function ($q) {
                    $q->where('trang_thai_thanh_toan', 'chưa thanh toán')
                        ->where('trang_thai_don', '!=', 'đã hủy');
                },
                'donHang as so_don_da_thanh_toan' => function ($q) {
                    $q->where('trang_thai_thanh_toan', 'đã thanh toán')
                        ->where('trang_thai_don', '!=', 'đã hủy');
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

        return view('manager.tables.index', compact('tables'));
    }

    public function show(int $id)
    {
        $table = BanAn::withCount([
            'donHang as so_don_chua_thanh_toan' => function ($q) {
                $q->where('trang_thai_thanh_toan', 'chưa thanh toán')
                    ->where('trang_thai_don', '!=', 'đã hủy');
            },
            'donHang as so_don_da_thanh_toan' => function ($q) {
                $q->where('trang_thai_thanh_toan', 'đã thanh toán')
                    ->where('trang_thai_don', '!=', 'đã hủy');
            },
        ])->findOrFail($id);

        $dishQuery = $table->chiTietDonHang()
            ->with(['donHang', 'kichCo'])
            ->latest('created_at');

        $dishItems = $dishQuery->paginate(20)->withQueryString();

        $summaryOrderQuery = $table->donHang()->where('trang_thai_don', '!=', 'đã hủy');

        $totalDishQty = (clone $dishQuery)->sum('so_luong');
        $totalDiscount = (clone $summaryOrderQuery)->sum('so_tien_giam');
        $totalPayable = (clone $summaryOrderQuery)->sum('tong_tien');

        $voucherSummary = $table->donHang()
            ->where('trang_thai_don', '!=', 'đã hủy')
            ->whereNotNull('voucher_nguoi_dung_id')
            ->with('voucherNguoiDung.voucher')
            ->get()
            ->pluck('voucherNguoiDung.voucher.ma_voucher')
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        if ($voucherSummary === '') {
            $voucherSummary = 'Không dùng voucher';
        }

        $latestOrder = $table->donHang()
            ->with(['nguoiDung', 'nhanVien'])
            ->latest()
            ->first();

        return view('manager.tables.show', compact(
            'table',
            'dishItems',
            'totalDishQty',
            'totalDiscount',
            'totalPayable',
            'voucherSummary',
            'latestOrder',
        ));
    }

    public function generatePaymentQr(Request $request, int $id): JsonResponse
    {
        $table = BanAn::findOrFail($id);

        $order = $table->donHang()
            ->where('trang_thai_don', '!=', 'đã hủy')
            ->where('trang_thai_thanh_toan', 'chưa thanh toán')
            ->latest()
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Bàn này chưa có đơn cần thanh toán.',
            ], 422);
        }

        $storeId = $request->user()?->cua_hang_id;
        $store = $this->paymentService->resolveStoreForPayment($storeId);

        if (! $store || ! $store->chu_cua_hang_id || ! $store->so_tai_khoan || ! $store->ngan_hang) {
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

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus, $user, $table): void {
            $order->update([
                'phuong_thuc_thanh_toan' => $paymentMethod,
                'trang_thai_thanh_toan' => $paymentStatus,
                'nhan_vien_id' => $user->id,
            ]);

            $this->paymentService->syncThanhToanRecord($order->fresh(), $paymentMethod, $paymentStatus);

            if ($paymentStatus === 'đã thanh toán') {
                $this->paymentService->freeTableIfAllPaid($table->id);
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

        $validated = $request->validate([
            'so_ban' => "required|string|max:20|unique:ban_an,so_ban,{$id}",
            'trang_thai' => 'nullable|in:trong,dang_phuc_vu,da_dat,ngung_su_dung,trống,đang phục vụ,đã đặt,ngưng sử dụng',
        ], [
            'so_ban.required' => 'Vui lòng nhập số bàn.',
            'so_ban.unique' => 'Số bàn đã tồn tại.',
            'trang_thai.in' => 'Trạng thái bàn ăn không hợp lệ.',
        ]);

        $table->update([
            'so_ban' => trim($validated['so_ban']),
            'trang_thai' => $this->toDbTrangThai($validated['trang_thai'] ?? null),
        ]);

        return redirect()->route('manager.tables.index')->with('success', "Đã cập nhật bàn {$table->so_ban}.");
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
