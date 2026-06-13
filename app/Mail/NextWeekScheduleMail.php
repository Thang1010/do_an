<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NextWeekScheduleMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $fromDate;
    public $toDate;
    public $excelPath;

    public function __construct($user, $fromDate, $toDate, $excelPath)
    {
        $this->user = $user;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->excelPath = $excelPath;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Lịch làm việc tại XM COFFEE tuần tới ( $this->fromDate tới ngày $this->toDate)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.next_week_schedule',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->excelPath)
                ->as('Lich-Lam-Viec-Tuan-Toi.xlsx')
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
