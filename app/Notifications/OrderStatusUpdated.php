<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FCMChannel;
use NotificationChannels\Fcm\FCMMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class OrderStatusUpdated extends Notification
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return [FCMChannel::class];
    }

    public function toFCM($notifiable)
    {
        return FCMMessage::create()
            ->setNotification(FcmNotification::create()
                ->setTitle('Order Status Updated')
                ->setBody("Your order for '{$this->order->service->name}' is now {$this->order->status}.")
            )
            ->setData(['order_id' => (string)$this->order->id]);
    }
}