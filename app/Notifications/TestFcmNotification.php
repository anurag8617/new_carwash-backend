<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;

class TestFcmNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setData(['type' => 'test'])
            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle('Hello from Laravel 🚀')
                ->setBody('This is a test FCM notification.'));
    }
}
