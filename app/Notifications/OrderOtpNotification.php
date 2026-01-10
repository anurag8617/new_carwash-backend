<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class OrderOtpNotification extends Notification
{
    use Queueable;

    protected $otp;
    protected $serviceName;
    protected $orderId;

    public function __construct($otp, $serviceName, $orderId)
    {
        $this->otp = $otp;
        $this->serviceName = $serviceName;
        $this->orderId = $orderId;
    }

    public function via($notifiable)
    {
        return ['database']; // ✅ Only store in database (No Email/SMS)
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Booking Confirmed',
            'message' => "Your OTP for {$this->serviceName} is {$this->otp}. Order #{$this->orderId}",
            'otp' => $this->otp,
            'order_id' => $this->orderId,
            'type' => 'otp'
        ];
    }
}