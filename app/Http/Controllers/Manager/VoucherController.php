<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ChiTietDonHang;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function create()
    {
        return view('manager.vouchers.create');
    }

    public function edit(int $id)
    {
        $voucher = Voucher::withCount('voucherNguoiDung')->findOrFail($id);
        $issuedCount = $voucher->voucher_nguoi_dung_count;
        $isLocked = $issuedCount > 0;

        return view('manager.vouchers.edit', compact('voucher', 'isLocked', 'issuedCount'));
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

                if ($normalizedStatus === 'ngừng phát hành') {
                    $query->where('trang_thai', 'ngừng phát hành');
                    return;
                }

                if ($normalizedStatus === 'hết hạn') {
                    $query->whereNotIn('trang_thai', ['ngưng hoạt động', 'ngừng phát hành'])
                        ->whereNotNull('ngay_ket_thuc')
                        ->where('ngay_ket_thuc', '<', $todayStart);
                    return;
                }

                if ($normalizedStatus === 'chưa phát hành') {
                    $query->whereNotIn('trang_thai', ['ngưng hoạt động', 'ngừng phát hành'])
                        ->whereNotNull('ngay_bat_dau')
                        ->where('ngay_bat_dau', '>', $todayEnd);
                    return;
                }

                $query->whereNotIn('trang_thai', ['ngưng hoạt động', 'ngừng phát hành'])
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
            'ngung_phat_hanh', 'ngừng phát hành' => 'ngừng phát hành',
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
                $q->where('email', 'like', '%' . $keyword . '%');
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
                'voucherNguoiDung',
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
            ->whereNotIn('trang_thai', ['ngưng hoạt động', 'ngừng phát hành'])
            ->where(function ($q) use ($todayEnd) {
                $q->whereNull('ngay_bat_dau')
                    ->orWhere('ngay_bat_dau', '<=', $todayEnd);
            })
            ->where(function ($q) use ($todayStart) {
                $q->whereNull('ngay_ket_thuc')
                    ->orWhere('ngay_ket_thuc', '>=', $todayStart);
            })
            ->count();

        $discountToday = ChiTietDonHang::query()
            ->whereDate('created_at', today())
            ->sum('so_tien_giam');

        $expiringSoon = Voucher::query()
            ->whereNotIn('trang_thai', ['ngưng hoạt động', 'ngừng phát hành'])
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ma_voucher'    => 'required|string|max:50|unique:voucher,ma_voucher',
            'ten_voucher'   => 'nullable|string|max:200',
            'loai_giam'     => 'required|string|max:50',
            'gia_tri_giam'  => 'required|numeric|min:0',
            'giam_toi_da'   => 'required|numeric|min:0',
            'don_toi_thieu' => 'required|numeric|min:0',
            'so_luong'      => 'required|integer|min:0',
            'ngay_bat_dau'  => 'required|date',
            'ngay_ket_thuc' => 'required|date|after_or_equal:ngay_bat_dau',
            'trang_thai'    => ['nullable', 'string', 'max:50', Rule::in(['đang hoạt động', 'ngừng phát hành', 'ngưng hoạt động', 'hoat_dong', 'ngung_phat_hanh', 'vo_hieu'])],
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
        if (! in_array($status, ['đang hoạt động', 'ngừng phát hành', 'ngưng hoạt động'], true)) {
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

            'ngay_bat_dau'  => $validated['ngay_bat_dau'],
            'ngay_ket_thuc' => $validated['ngay_ket_thuc'],
            'trang_thai'    => $status,
        ]);

        return redirect()->route('manager.vouchers.index')->with('success', 'Voucher đã được tạo thành công.');
    }

    public function update(Request $request, int $id)
    {
        $voucher = Voucher::withCount('voucherNguoiDung')->findOrFail($id);
        $issuedCount = $voucher->voucher_nguoi_dung_count;

        // Voucher đã được cấp cho người dùng: chỉ cho sửa các trường an toàn
        // (tên, ngày kết thúc, trạng thái, giới hạn sử dụng). Các điều khoản giảm giá
        // được khóa để không phá vỡ cam kết với những khách đã nhận voucher.
        if ($issuedCount > 0) {
            $validated = $request->validate([
                'ten_voucher'   => 'nullable|string|max:200',
                'ngay_ket_thuc' => ['required', 'date', 'after_or_equal:' . $voucher->ngay_bat_dau->format('Y-m-d\TH:i')],
                'so_luong'      => 'required|integer|min:0',
                'trang_thai'    => ['required', 'string', 'max:50', Rule::in(['đang hoạt động', 'ngừng phát hành', 'ngưng hoạt động', 'hoat_dong', 'ngung_phat_hanh', 'vo_hieu'])],
            ], [
                'ngay_ket_thuc.after_or_equal' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            ]);

            // so_luong = 0 nghĩa là không giới hạn (luôn hợp lệ). Nếu có giới hạn cụ thể
            // thì không được nhỏ hơn số voucher đã phát hành.
            if ((int) $validated['so_luong'] !== 0 && (int) $validated['so_luong'] < $issuedCount) {
                return back()->withInput()->withErrors([
                    'so_luong' => 'Giới hạn sử dụng không được nhỏ hơn số voucher đã phát hành (' . $issuedCount . '). Nhập 0 nếu muốn không giới hạn.',
                ]);
            }

            $status = $this->normalizeVoucherStatus($validated['trang_thai']);
            if (! in_array($status, ['đang hoạt động', 'ngừng phát hành', 'ngưng hoạt động'], true)) {
                $status = 'đang hoạt động';
            }

            $voucher->update([
                'ten_voucher'   => $validated['ten_voucher'] ?? $voucher->ten_voucher,
                'so_luong'      => (int) $validated['so_luong'],
                'ngay_ket_thuc' => $validated['ngay_ket_thuc'],
                'trang_thai'    => $status,
            ]);

            return redirect()->route('manager.vouchers.index')
                ->with('success', 'Đã cập nhật voucher. Lưu ý: các điều khoản giảm giá bị khóa vì voucher đã được cấp cho người dùng.');
        }

        $validated = $request->validate([
            'ma_voucher'    => ['required', 'string', 'max:50', Rule::unique('voucher', 'ma_voucher')->ignore($voucher->id)],
            'ten_voucher'   => 'nullable|string|max:200',
            'loai_giam'     => 'required|string|max:50',
            'gia_tri_giam'  => 'required|numeric|min:0',
            'giam_toi_da'   => 'required|numeric|min:0',
            'don_toi_thieu' => 'required|numeric|min:0',
            'so_luong'      => 'required|integer|min:0',
            'ngay_bat_dau'  => 'required|date',
            'ngay_ket_thuc' => 'required|date|after_or_equal:ngay_bat_dau',
            'trang_thai'    => ['required', 'string', 'max:50', Rule::in(['đang hoạt động', 'ngừng phát hành', 'ngưng hoạt động', 'hoat_dong', 'ngung_phat_hanh', 'vo_hieu'])],
        ]);

        $discountType = $this->normalizeDiscountType($validated['loai_giam']);
        $status = $this->normalizeVoucherStatus($validated['trang_thai']);

        if ($discountType === null || $status === null) {
            return back()->withInput()->with('error', 'Thông tin voucher không hợp lệ.');
        }

        if (! in_array($status, ['đang hoạt động', 'ngừng phát hành', 'ngưng hoạt động'], true)) {
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

        // Voucher đã được cấp: không xóa (giữ lịch sử & khóa ngoại) mà chuyển sang
        // 'ngừng phát hành' để dừng phát thêm; tài khoản đã nhận vẫn dùng được đến khi hết hạn.
        if ($voucher->voucher_nguoi_dung_count > 0) {
            if ($voucher->trang_thai === 'ngừng phát hành') {
                return back()->with('info', 'Voucher đã ở trạng thái Ngừng phát hành.');
            }

            $voucher->update(['trang_thai' => 'ngừng phát hành']);

            return back()->with('success', 'Voucher đã được cấp nên không thể xóa. Đã chuyển sang "Ngừng phát hành": dừng phát thêm, người đã nhận vẫn dùng được đến khi hết hạn.');
        }

        $voucher->delete();
        return redirect()->route('manager.vouchers.index')->with('success', 'Đã xóa voucher thành công.');
    }
}
