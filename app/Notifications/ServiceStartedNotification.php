<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ServiceStartedNotification extends Notification
{
    use Queueable;

    public $otp;
    public $orderId;
    public $serviceName;

    public function __construct($otp, $serviceName, $orderId)
    {
        $this->otp = $otp;
        $this->serviceName = $serviceName;
        $this->orderId = $orderId;
    }

    public function via($notifiable)
    {
        // You can add 'mail' to the array if you want to email them too
        return ['database']; 
    }

    // Data stored in the 'notifications' table
    public function toArray($notifiable)
    {
        return [
            'title' => 'Service Started!',
            'message' => "Your service '{$this->serviceName}' has started. Share OTP {$this->otp} with the staff to complete.",
            'otp' => $this->otp,
            'order_id' => $this->orderId,
            'type' => 'service_started'
        ];
    }

    // Optional: Email template
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Service Started - OTP: ' . $this->otp)
            ->line("The staff member has started your service: {$this->serviceName}.")
            ->line("Please share this OTP with them when the work is done:")
            ->line("**{$this->otp}**") // Bold OTP
            ->action('View Order', url('/client/orders/' . $this->orderId));
    }
}