<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BanAn;
use App\Models\DonHang;
use App\Models\HoSoQuanLy;
use App\Models\ThanhToan;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableController extends Controller
{
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

    private function resolveVietQrBankCode(?string $bankName): string
    {
        $raw = trim((string) $bankName);
        if ($raw === '') {
            return '';
        }

        $normalized = Str::of($raw)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->value();

        $map = [
            'VCB' => 'VCB',
            'VIETCOMBANK' => 'VCB',
            'BIDV' => 'BIDV',
            'VIETINBANK' => 'ICB',
            'ICB' => 'ICB',
            'AGRIBANK' => 'VBA',
            'VBA' => 'VBA',
            'TECHCOMBANK' => 'TCB',
            'TCB' => 'TCB',
            'MB' => 'MB',
            'MBBANK' => 'MB',
            'ACB' => 'ACB',
            'SACOMBANK' => 'STB',
            'STB' => 'STB',
            'VPBANK' => 'VPB',
            'VPB' => 'VPB',
            'PVCOMBANK' => 'PVCB',
            'PVCBANK' => 'PVCB',
            'PVCB' => 'PVCB',
            'PVC' => 'PVCB',
            'TPBANK' => 'TPB',
            'TPB' => 'TPB',
        ];

        return $map[$normalized] ?? $normalized;
    }

    private function toDbTrangThai(?string $status): string
    {
        return match ($status) {
            'dang_phuc_vu', 'đang phục vụ' => 'đang phục vụ',
            'da_dat', 'đã đặt' => 'đã đặt',
            'ngung_su_dung', 'ngưng sử dụng' => 'ngưng sử dụng',
            default => 'trống',
        };
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

        $managerProfile = $request->user()?->hoSoQuanLy;

        if (! $managerProfile || ! $managerProfile->so_tai_khoan || ! $managerProfile->ngan_hang) {
            $managerProfile = HoSoQuanLy::query()
                ->with('nguoiDung')
                ->whereNotNull('so_tai_khoan')
                ->whereNotNull('ngan_hang')
                ->whereHas('nguoiDung', function ($q) {
                    $q->where('vai_tro', 'quản lý')
                        ->where('trang_thai', 'hoạt động');
                })
                ->latest('id')
                ->first();
        }

        if (! $managerProfile || ! $managerProfile->so_tai_khoan || ! $managerProfile->ngan_hang) {
            return response()->json([
                'message' => 'Chưa có hồ sơ quản lý có số tài khoản/ngân hàng để tạo QR thanh toán.',
            ], 422);
        }

        $bankCode = $this->resolveVietQrBankCode($managerProfile->ngan_hang);
        $accountNo = preg_replace('/\s+/', '', (string) $managerProfile->so_tai_khoan);
        $amount = (int) round((float) ($order->tong_tien ?? 0));

        if ($bankCode === '' || $accountNo === '') {
            return response()->json([
                'message' => 'Thông tin ngân hàng hoặc số tài khoản của quản lý chưa hợp lệ.',
            ], 422);
        }

        if ($amount <= 0) {
            return response()->json([
                'message' => 'Đơn hàng chưa có tổng tiền hợp lệ để tạo QR.',
            ], 422);
        }

        $transferContent = 'TT ' . ($order->ma_don_hang ?? ('DON' . $order->id));
        $accountName = $managerProfile->nguoiDung?->ho_ten ?? 'Chu cua hang';
        $expiresAt = now()->addSeconds(60);

        $params = http_build_query([
            'amount' => $amount,
            'addInfo' => $transferContent,
            'accountName' => $accountName,
        ], '', '&', PHP_QUERY_RFC3986);

        $qrUrl = "https://img.vietqr.io/image/{$bankCode}-{$accountNo}-compact2.png?{$params}";

        $token = (string) Str::uuid();
        Cache::put("table_payment_qr:{$token}", [
            'table_id' => $table->id,
            'order_id' => $order->id,
            'amount' => $amount,
            'transfer_content' => $transferContent,
            'expires_at' => $expiresAt->toIso8601String(),
        ], $expiresAt);

        return response()->json([
            'message' => 'Đã tạo QR thanh toán. Mã sẽ hết hiệu lực sau 60 giây.',
            'token' => $token,
            'order_id' => $order->id,
            'order_code' => $order->ma_don_hang,
            'amount' => $amount,
            'bank_code' => $bankCode,
            'bank_name' => $managerProfile->ngan_hang,
            'account_no' => $accountNo,
            'account_name' => $accountName,
            'transfer_content' => $transferContent,
            'qr_url' => $qrUrl,
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in' => 60,
        ]);
    }

    public function updateOrderPayment(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->vai_tro, ['quản lý', 'nhân viên'], true)) {
            abort(403, 'Bạn không có quyền cập nhật thanh toán.');
        }

        $request->validate([
            'order_id' => 'required|integer',
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

        $table = BanAn::findOrFail($id);
        $order = DonHang::where('id', (int) $request->order_id)
            ->where('ban_an_id', $table->id)
            ->firstOrFail();

        DB::transaction(function () use ($order, $paymentMethod, $paymentStatus, $user): void {
            $order->update([
                'phuong_thuc_thanh_toan' => $paymentMethod,
                'trang_thai_thanh_toan' => $paymentStatus,
                'nhan_vien_id' => $user->id,
            ]);

            $this->syncThanhToanRecord($order->fresh(), $paymentMethod, $paymentStatus);
        });

        return back()->with('success', "Đã cập nhật thanh toán cho đơn #{$order->id} tại bàn {$table->so_ban}.");
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

        return back()->with('success', "Đã thêm bàn {$table->so_ban}.");
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

        return back()->with('success', "Đã cập nhật bàn {$table->so_ban}.");
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
            return back()->with('success', "Đã xóa bàn {$name}.");
        } catch (QueryException) {
            return back()->with('error', 'Không thể xóa bàn do dữ liệu liên quan.');
        }
    }
}
