<?php

namespace App\Services;

use App\Models\NguoiDung;
use App\Models\Voucher;
use App\Models\VoucherNguoiDung;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VoucherAssignmentService
{
    /**
     * Cap voucher hop le cho khach hang khi dang nhap.
     *
     * @return Collection<int, VoucherNguoiDung>
     */
    public function assignLoginEligibleVouchers(NguoiDung $user): Collection
    {
        if ($user->vai_tro !== 'khách hàng' || $user->trang_thai !== 'hoạt động') {
            return collect();
        }

        $now = Carbon::now();

        $activeVouchers = Voucher::query()
            ->where('trang_thai', 'đang hoạt động')
            ->where(function ($q) use ($now) {
                $q->whereNull('ngay_bat_dau')
                    ->orWhere('ngay_bat_dau', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ngay_ket_thuc')
                    ->orWhere('ngay_ket_thuc', '>=', $now);
            })
            ->get();

        $newAssignments = collect();

        foreach ($activeVouchers as $voucher) {
            $alreadyAssigned = VoucherNguoiDung::query()
                ->where('nguoi_dung_id', $user->id)
                ->where('voucher_id', $voucher->id)
                ->exists();

            if ($alreadyAssigned) {
                continue;
            }

            $issuedCount = VoucherNguoiDung::query()
                ->where('voucher_id', $voucher->id)
                ->count();

            $hasLimit = (int) ($voucher->so_luong ?? 0) > 0;
            if ($hasLimit && $issuedCount >= (int) $voucher->so_luong) {
                continue;
            }

            $newAssignments->push(VoucherNguoiDung::create([
                'nguoi_dung_id' => $user->id,
                'voucher_id' => $voucher->id,
                'trang_thai' => 'chưa dùng',
                'duoc_cap_luc' => $now,
            ]));
        }

        return $newAssignments;
    }
}
