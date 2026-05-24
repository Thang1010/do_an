<?php

namespace App\Services;

use App\Enums\TableStatus;
use App\Models\BanAn;
use App\Models\DonHang;

class TableStatusService
{
    public static function refreshForTable(?int $tableId): void
    {
        if (!$tableId) {
            return;
        }

        $table = BanAn::query()->find($tableId);
        if (!$table) {
            return;
        }

        $hasConfirmed = DonHang::query()
            ->where('ban_an_id', $tableId)
            ->where('trang_thai_don', 'đã xác nhận')
            ->exists();

        if ($hasConfirmed) {
            if ($table->trang_thai !== TableStatus::DANG_PHUC_VU->value) {
                $table->update(['trang_thai' => TableStatus::DANG_PHUC_VU->value]);
            }
            return;
        }

        $hasPending = DonHang::query()
            ->where('ban_an_id', $tableId)
            ->whereIn('trang_thai_don', ['chờ xác nhận', 'cho_xac_nhan'])
            ->exists();

        if ($hasPending) {
            if ($table->trang_thai !== TableStatus::DANG_CHO_DUYET->value) {
                $table->update(['trang_thai' => TableStatus::DANG_CHO_DUYET->value]);
            }
            return;
        }

        if (in_array($table->trang_thai, [TableStatus::NGUNG_SU_DUNG->value, TableStatus::DA_DAT->value], true)) {
            return;
        }

        if ($table->trang_thai !== TableStatus::TRONG->value) {
            $table->update(['trang_thai' => TableStatus::TRONG->value]);
        }
    }
}
