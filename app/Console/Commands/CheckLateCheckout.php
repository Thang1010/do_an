<?php

namespace App\Console\Commands;

use App\Models\ChamCong;
use App\Notifications\LateCheckoutReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-late-checkout')]
#[Description('Gửi email nhắc nhở nếu nhân viên chưa checkout sau 5 phút kết thúc ca')]
class CheckLateCheckout extends Command
{
    public function handle()
    {
        $attendances = ChamCong::with(['caLamViec', 'nguoiDung'])
            ->whereNotNull('cham_cong_vao')
            ->whereNull('cham_cong_ra')
            ->where(function($q) {
                $q->whereNull('ghi_chu')
                  ->orWhere('ghi_chu', 'not like', '%Đã gửi email nhắc checkout%');
            })
            ->get();

        foreach ($attendances as $attendance) {
            $shift = $attendance->caLamViec;
            if (!$shift || !$shift->ngay_lam || !$shift->gio_ket_thuc) continue;

            $shiftDate = $shift->ngay_lam instanceof Carbon ? $shift->ngay_lam->format('Y-m-d') : (string)$shift->ngay_lam;
            $endTime = Carbon::parse($shiftDate . ' ' . $shift->gio_ket_thuc);
            
            // Nếu ca kết thúc qua đêm (giờ kết thúc < giờ bắt đầu)
            if ($endTime->format('H:i:s') < $shift->gio_bat_dau) {
                $endTime->addDay();
            }

            // Đã quá 5 phút
            if (now()->greaterThanOrEqualTo($endTime->copy()->addMinutes(5))) {
                if ($attendance->nguoiDung) {
                    $attendance->nguoiDung->notify(new LateCheckoutReminderNotification($shift));
                    
                    $oldNote = $attendance->ghi_chu ? $attendance->ghi_chu . ' | ' : '';
                    $attendance->update(['ghi_chu' => $oldNote . 'Đã gửi email nhắc checkout']);
                }
            }
        }
    }
}
