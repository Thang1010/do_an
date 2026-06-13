<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class CustomDatabaseChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function send($notifiable, Notification $notification)
    {
        $data = method_exists($notification, 'toDatabase')
            ? $notification->toDatabase($notifiable)
            : $notification->toArray($notifiable);

        return $notifiable->notifications()->create([
            'ma_thong_bao' => $notification->id,
            'loai' => get_class($notification),
            'du_lieu' => is_array($data) ? json_encode($data) : $data,
            'da_doc_luc' => null,
        ]);
    }
}
