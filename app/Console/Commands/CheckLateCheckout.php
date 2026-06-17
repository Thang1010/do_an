<?php

namespace App\Console\Commands;

use App\Mail\AutoCheckoutMail;
use App\Models\ChamCong;
use App\Notifications\LateCheckoutReminderNotification;
use App\Notifications\ShiftCheckoutNotification;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:check-late-checkout')]
#[Description('Nhắc nhở checkout sau 5 phút và tự động chấm công ra sau 15 phút nếu nhân viên quên')]
class CheckLateCheckout extends Command
{
    /**
     * Số phút sau khi kết thúc ca sẽ gửi email nhắc nhở.
     */
    private const REMIND_AFTER_MINUTES = 5;

    /**
     * Số phút sau khi kết thúc ca sẽ tự động chấm công ra.
     */
    private const AUTO_CHECKOUT_AFTER_MINUTES = 15;

    private const AUTO_CHECKOUT_NOTE = 'Nhân viên quên chưa chấm công ra, hệ thống tự chấm công ra lúc kết thúc ca.';

    public function handle()
    {
        $attendances = ChamCong::with(['caLamViec', 'nguoiDung'])
            ->whereNotNull('cham_cong_vao')
            ->whereNull('cham_cong_ra')
            ->get();

        foreach ($attendances as $attendance) {
            $shift = $attendance->caLamViec;
            if (!$shift || !$shift->ngay_lam || !$shift->gio_ket_thuc) {
                continue;
            }

            $shiftDate = $shift->ngay_lam instanceof Carbon
                ? $shift->ngay_lam->format('Y-m-d')
                : (string) $shift->ngay_lam;
            $endTime = Carbon::parse($shiftDate . ' ' . $shift->gio_ket_thuc);

            // Ca qua đêm (giờ kết thúc <= giờ bắt đầu)
            if ($shift->gio_ket_thuc <= $shift->gio_bat_dau) {
                $endTime->addDay();
            }

            $now = now();

            // Đã quá 15 phút sau khi kết thúc ca → tự động chấm công ra
            if ($now->greaterThanOrEqualTo($endTime->copy()->addMinutes(self::AUTO_CHECKOUT_AFTER_MINUTES))) {
                $existingNote = trim((string) ($attendance->ghi_chu ?? ''));
                $existingNote = $this->stripReminderNote($existingNote);

                // Chấm công ra tại thời điểm kết thúc ca (không tính phần quên ra)
                $attendance->cham_cong_ra = $endTime;
                $attendance->ghi_chu = $existingNote !== ''
                    ? $existingNote . ' | ' . self::AUTO_CHECKOUT_NOTE
                    : self::AUTO_CHECKOUT_NOTE;
                $attendance->save();

                $user = $attendance->nguoiDung;
                if ($user) {
                    // Thông báo trong hệ thống
                    $user->notify(new ShiftCheckoutNotification($shift, self::AUTO_CHECKOUT_NOTE));

                    // Gửi email báo đã tự động chấm công ra
                    if (!empty($user->email)) {
                        Mail::to($user->email)->send(new AutoCheckoutMail(
                            $user,
                            $shift,
                            $endTime->format('H:i d/m/Y'),
                            self::AUTO_CHECKOUT_NOTE
                        ));
                    }
                }

                continue;
            }

            // Đã quá 5 phút nhưng chưa tới mốc tự chấm công ra → nhắc nhở 1 lần
            if ($now->greaterThanOrEqualTo($endTime->copy()->addMinutes(self::REMIND_AFTER_MINUTES))) {
                $note = (string) ($attendance->ghi_chu ?? '');
                if (str_contains($note, 'Đã gửi email nhắc checkout')) {
                    continue;
                }

                if ($attendance->nguoiDung) {
                    $attendance->nguoiDung->notify(new LateCheckoutReminderNotification($shift));

                    $oldNote = trim($note) !== '' ? trim($note) . ' | ' : '';
                    $attendance->update(['ghi_chu' => $oldNote . 'Đã gửi email nhắc checkout']);
                }
            }
        }
    }

    /**
     * Bỏ ghi chú "đã gửi email nhắc checkout" khỏi chuỗi ghi chú khi chốt chấm công ra.
     */
    private function stripReminderNote(string $note): string
    {
        $parts = array_filter(
            array_map('trim', explode('|', $note)),
            fn (string $part) => $part !== '' && !str_contains($part, 'Đã gửi email nhắc checkout')
        );

        return implode(' | ', $parts);
    }
}
