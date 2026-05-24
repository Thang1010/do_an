<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherController extends Controller
{
    public function create()
    {
        return view('manager.vouchers.create');
    }

    public function edit(int $id)
    {
        $voucher = Voucher::findOrFail($id);

        return view('manager.vouchers.edit', compact('voucher'));
    }

    private function applyFilters($query, Request $request): void
    {
        $todayStart = Carbon::now()->startOfDay();
        $todayEnd = Carbon::now()->endOfDay();

        if ($request->filled('search')) {
            $keyword = trim((string) $request->search);
            $query->where(function ($q) use ($keyword) {
                $q->where('ma_voucher', 'like', '%' . strtoupper($keyword) . '%')
                    ->orWhere('ten_voucher', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('trang_thai')) {
            $normalizedStatus = $this->normalizeVoucherStatus($request->trang_thai);
            if ($normalizedStatus !== null) {
                if ($normalizedStatus === 'ngưng hoạt động') {
                    $query->where('trang_thai', 'ngưng hoạt động');
                    return;
                }

                if ($normalizedStatus === 'hết hạn') {
                    $query->where('trang_thai', '!=', 'ngưng hoạt động')
                        ->whereNotNull('ngay_ket_thuc')
                        ->where('ngay_ket_thuc', '<', $todayStart);
                    return;
                }

                if ($normalizedStatus === 'chưa phát hành') {
                    $query->where('trang_thai', '!=', 'ngưng hoạt động')
                        ->whereNotNull('ngay_bat_dau')
                        ->where('ngay_bat_dau', '>', $todayEnd);
                    return;
                }

                $query->where('trang_thai', '!=', 'ngưng hoạt động')
                    ->where(function ($q) use ($todayEnd) {
                        $q->whereNull('ngay_bat_dau')
                            ->orWhere('ngay_bat_dau', '<=', $todayEnd);
                    })
                    ->where(function ($q) use ($todayStart) {
                        $q->whereNull('ngay_ket_thuc')
                            ->orWhere('ngay_ket_thuc', '>=', $todayStart);
                    });
            }
        }
    }

    private function normalizeDiscountType(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($value) {
            'phan_tram', 'phần trăm' => 'phần trăm',
            'co_dinh', 'tiền mặt' => 'tiền mặt',
            default => null,
        };
    }

    private function normalizeVoucherStatus(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($value) {
            'hoat_dong', 'đang hoạt động' => 'đang hoạt động',
            'vo_hieu', 'ngưng hoạt động' => 'ngưng hoạt động',
            'het_han', 'hết hạn' => 'hết hạn',
            'chua_phat_hanh', 'chưa phát hành' => 'chưa phát hành',
            default => null,
        };
    }

    private function applyUserVoucherFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $keyword = trim((string) $request->search);

            $query->whereHas('nguoiDung', function (Builder $q) use ($keyword) {
                $q->where('ho_ten', 'like', '%' . $keyword . '%')
                    ->orWhere('so_dien_thoai', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('trang_thai_su_dung')) {
            $status = trim((string) $request->trang_thai_su_dung);
            if (in_array($status, ['chưa dùng', 'đã dùng', 'đã hết hạn'], true)) {
                $query->where('trang_thai', $status);
            }
        }
    }

    public function index(Request $request)
    {
        $query = Voucher::query()
            ->withCount([
                'voucherNguoiDung as so_da_dung_thuc' => function ($q) {
                    $q->where('trang_thai', 'đã dùng');
                },
            ]);

        $this->applyFilters($query, $request);

        $vouchers = $query->latest()->paginate(15)->withQueryString();

        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $activeVouchers = Voucher::query()
            ->where('trang_thai', '!=', 'ngưng hoạt động')
            ->where(function ($q) use ($todayEnd) {
                $q->whereNull('ngay_bat_dau')
                    ->orWhere('ngay_bat_dau', '<=', $todayEnd);
            })
            ->where(function ($q) use ($todayStart) {
                $q->whereNull('ngay_ket_thuc')
                    ->orWhere('ngay_ket_thuc', '>=', $todayStart);
            })
            ->count();

        $discountToday = DonHang::query()
            ->whereDate('created_at', today())
            ->whereNotIn('trang_thai_don', ['huy', 'đã hủy'])
            ->sum('so_tien_giam');

        $expiringSoon = Voucher::query()
            ->where('trang_thai', '!=', 'ngưng hoạt động')
            ->where(function ($q) use ($todayEnd) {
                $q->whereNull('ngay_bat_dau')
                    ->orWhere('ngay_bat_dau', '<=', $todayEnd);
            })
            ->whereNotNull('ngay_ket_thuc')
            ->whereBetween('ngay_ket_thuc', [$todayStart, $todayEnd->copy()->addDays(3)])
            ->count();

        return view('manager.vouchers.index', compact(
            'vouchers', 'activeVouchers', 'discountToday', 'expiringSoon'
        ));
    }

    public function show(Request $request, int $id)
    {
        $voucher = Voucher::findOrFail($id);

        $userVoucherQuery = $voucher->voucherNguoiDung()
            ->with('nguoiDung')
            ->orderByDesc('duoc_cap_luc');

        $this->applyUserVoucherFilters($userVoucherQuery, $request);

        $danhSachNguoiNhan = $userVoucherQuery->paginate(20)->withQueryString();

        $tongNguoiNhan = $voucher->voucherNguoiDung()->count();
        $tongDaDung = $voucher->voucherNguoiDung()->where('trang_thai', 'đã dùng')->count();
        $tongChuaDung = $voucher->voucherNguoiDung()->where('trang_thai', 'chưa dùng')->count();
        $tongDaHetHan = $voucher->voucherNguoiDung()->where('trang_thai', 'đã hết hạn')->count();
        $tiLeDaDung = $tongNguoiNhan > 0 ? round(($tongDaDung / $tongNguoiNhan) * 100, 1) : 0;

        return view('manager.vouchers.show', compact(
            'voucher',
            'danhSachNguoiNhan',
            'tongNguoiNhan',
            'tongDaDung',
            'tongChuaDung',
            'tongDaHetHan',
            'tiLeDaDung'
        ));
    }

    public function exportUsers(Request $request, int $id): StreamedResponse
    {
        $voucher = Voucher::findOrFail($id);

        $userVoucherQuery = $voucher->voucherNguoiDung()
            ->with('nguoiDung')
            ->orderByDesc('duoc_cap_luc');

        $this->applyUserVoucherFilters($userVoucherQuery, $request);

        $records = $userVoucherQuery->get();
        $fileVoucherCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $voucher->ma_voucher) ?: ('voucher-' . $voucher->id);
        $fileName = 'danh-sach-nguoi-nhan-' . $fileVoucherCode . '-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($records, $voucher) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'STT',
                'Ma voucher',
                'Ten voucher',
                'Nguoi dung',
                'So dien thoai',
                'Email',
                'Trang thai su dung',
                'Da dung?',
                'Duoc cap luc',
                'Da dung luc',
            ]);

            foreach ($records as $index => $item) {
                $isUsed = $item->trang_thai === 'đã dùng';

                fputcsv($output, [
                    $index + 1,
                    $voucher->ma_voucher,
                    $voucher->ten_voucher,
                    $item->nguoiDung->ho_ten ?? 'Khong xac dinh',
                    $item->nguoiDung->so_dien_thoai ?? '',
                    $item->nguoiDung->email ?? '',
                    $item->trang_thai,
                    $isUsed ? 'Da dung' : 'Chua dung',
                    $item->duoc_cap_luc ? Carbon::parse($item->duoc_cap_luc)->format('d/m/Y H:i:s') : '',
                    $item->da_dung_luc ? Carbon::parse($item->da_dung_luc)->format('d/m/Y H:i:s') : '',
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ma_voucher'    => 'required|string|max:50|unique:voucher,ma_voucher',
            'ten_voucher'   => 'nullable|string|max:200',
            'loai_giam'     => 'required|string|max:50',
            'gia_tri_giam'  => 'required|numeric|min:0',
            'giam_toi_da'   => 'nullable|numeric|min:0',
            'don_toi_thieu' => 'nullable|numeric|min:0',
            'so_luong'      => 'nullable|integer|min:1',
            'ngay_bat_dau'  => 'required|date',
            'ngay_ket_thuc' => 'required|date|after_or_equal:ngay_bat_dau',
            'trang_thai'    => ['nullable', 'string', 'max:50', Rule::in(['đang hoạt động', 'ngưng hoạt động', 'hoat_dong', 'vo_hieu'])],
        ], [
            'ma_voucher.unique'           => 'Mã voucher này đã tồn tại.',
            'ngay_ket_thuc.after_or_equal' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'gia_tri_giam.required'       => 'Vui lòng nhập giá trị giảm.',
        ]);

        $discountType = $this->normalizeDiscountType($validated['loai_giam']);
        if ($discountType === null) {
            return back()->withInput()->with('error', 'Loại giảm giá không hợp lệ.');
        }

        if ($discountType === 'phần trăm' && (float) $validated['gia_tri_giam'] > 100) {
            return back()->withInput()->with('error', 'Voucher phần trăm không được lớn hơn 100%.');
        }

        $status = $this->normalizeVoucherStatus($validated['trang_thai'] ?? null) ?? 'đang hoạt động';
        if (! in_array($status, ['đang hoạt động', 'ngưng hoạt động'], true)) {
            $status = 'đang hoạt động';
        }

        $usageLimit = array_key_exists('so_luong', $validated) && $validated['so_luong'] !== null
            ? (int) $validated['so_luong']
            : 0;

        Voucher::create([
            'ma_voucher'    => strtoupper($validated['ma_voucher']),
            'ten_voucher'   => $validated['ten_voucher'] ?? strtoupper($validated['ma_voucher']),
            'loai_giam'     => $discountType,
            'gia_tri_giam'  => $validated['gia_tri_giam'],
            'giam_toi_da'   => $validated['giam_toi_da'] ?? null,
            'don_toi_thieu' => $validated['don_toi_thieu'] ?? 0,
            'so_luong'      => $usageLimit,
            'da_su_dung'    => 0,
            'ngay_bat_dau'  => $validated['ngay_bat_dau'],
            'ngay_ket_thuc' => $validated['ngay_ket_thuc'],
            'trang_thai'    => $status,
        ]);

        return redirect()->route('manager.vouchers.index')->with('success', 'Voucher đã được tạo thành công.');
    }

    public function update(Request $request, int $id)
    {
        $voucher = Voucher::findOrFail($id);

        $validated = $request->validate([
            'ma_voucher'    => ['required', 'string', 'max:50', Rule::unique('voucher', 'ma_voucher')->ignore($voucher->id)],
            'ten_voucher'   => 'nullable|string|max:200',
            'loai_giam'     => 'required|string|max:50',
            'gia_tri_giam'  => 'required|numeric|min:0',
            'giam_toi_da'   => 'nullable|numeric|min:0',
            'don_toi_thieu' => 'nullable|numeric|min:0',
            'so_luong'      => 'nullable|integer|min:1',
            'ngay_bat_dau'  => 'required|date',
            'ngay_ket_thuc' => 'required|date|after_or_equal:ngay_bat_dau',
            'trang_thai'    => ['required', 'string', 'max:50', Rule::in(['đang hoạt động', 'ngưng hoạt động', 'hoat_dong', 'vo_hieu'])],
        ]);

        $discountType = $this->normalizeDiscountType($validated['loai_giam']);
        $status = $this->normalizeVoucherStatus($validated['trang_thai']);

        if ($discountType === null || $status === null) {
            return back()->withInput()->with('error', 'Thông tin voucher không hợp lệ.');
        }

        if (! in_array($status, ['đang hoạt động', 'ngưng hoạt động'], true)) {
            $status = 'đang hoạt động';
        }

        if ($discountType === 'phần trăm' && (float) $validated['gia_tri_giam'] > 100) {
            return back()->withInput()->with('error', 'Voucher phần trăm không được lớn hơn 100%.');
        }

        $usageLimit = array_key_exists('so_luong', $validated) && $validated['so_luong'] !== null
            ? (int) $validated['so_luong']
            : 0;

        $voucher->update([
            'ma_voucher'    => strtoupper($validated['ma_voucher']),
            'ten_voucher'   => $validated['ten_voucher'] ?? strtoupper($validated['ma_voucher']),
            'loai_giam'     => $discountType,
            'gia_tri_giam'  => $validated['gia_tri_giam'],
            'giam_toi_da'   => $validated['giam_toi_da'] ?? null,
            'don_toi_thieu' => $validated['don_toi_thieu'] ?? 0,
            'so_luong'      => $usageLimit,
            'ngay_bat_dau'  => $validated['ngay_bat_dau'],
            'ngay_ket_thuc' => $validated['ngay_ket_thuc'],
            'trang_thai'    => $status,
        ]);

        return redirect()->route('manager.vouchers.index')->with('success', 'Đã cập nhật voucher.');
    }

    public function destroy(int $id)
    {
        $voucher = Voucher::withCount('voucherNguoiDung')->findOrFail($id);

        if ($voucher->voucher_nguoi_dung_count > 0 || (int) $voucher->da_su_dung > 0) {
            return back()->with('error', 'Không thể xóa voucher đã được cấp hoặc đã sử dụng.');
        }

        $voucher->delete();
        return redirect()->route('manager.vouchers.index')->with('success', 'Đã xóa voucher thành công.');
    }
}
