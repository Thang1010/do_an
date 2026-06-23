<?php

namespace App\Observers;

use App\Models\LichSuKho;
use App\Models\NguoiDung;
use App\Enums\UserRole;
use App\Mail\LowStockMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Traits\CalculatesStock;

class LichSuKhoObserver
{
    use CalculatesStock;

    /**
     * Handle the LichSuKho "created" event.
     */
    public function created(LichSuKho $lichSuKho): void
    {
        // Only trigger on export
        if (strtolower($lichSuKho->loai_giao_dich) !== 'xuất kho') {
            return;
        }

        $ingredient = $lichSuKho->nguyenLieu;
        if (!$ingredient || !$ingredient->isDangSuDung()) {
            return;
        }

        $currentStock = $this->currentStock($ingredient->id);
        
        $maxTieuHao = DB::table('cong_thuc_san_pham')
            ->where('nguyen_lieu_id', $ingredient->id)
            ->max('so_luong_can') ?: 1;
            
        $soCupCurrent = (int) floor($currentStock / $maxTieuHao);
        
        $delta = (float) $lichSuKho->so_luong;
        $previousStock = $currentStock + $delta;
        $soCupPrev = (int) floor($previousStock / $maxTieuHao);
        
        $statusCurrent = $soCupCurrent <= 0 ? 'het' : ($soCupCurrent <= 3 ? 'sap_het' : 'ok');
        $statusPrev = $soCupPrev <= 0 ? 'het' : ($soCupPrev <= 3 ? 'sap_het' : 'ok');
        
        if ($statusCurrent !== $statusPrev && in_array($statusCurrent, ['het', 'sap_het'])) {
            $this->notifyManagers($ingredient, $currentStock, $statusCurrent);
        }
    }

    private function notifyManagers($ingredient, $currentStock, $status)
    {
        $managers = NguoiDung::whereIn('vai_tro', [UserRole::CHU_CUA_HANG->value, UserRole::QUAN_LY->value])
            ->where('trang_thai', 'hoạt động')
            ->get();
            
        if ($managers->isEmpty()) {
            return;
        }

        Mail::to($managers)->queue(new LowStockMail($ingredient, $currentStock, $status));
    }
}
