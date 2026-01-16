<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SubscriptionCancelled extends Notification
{
    use Queueable;

    public $refundStatus;
    public $amount;

    /**
     * Create a new notification instance.
     *
     * @param string $refundStatus
     * @param float $amount
     */
    public function __construct($refundStatus, $amount)
    {
        $this->refundStatus = $refundStatus;
        $this->amount = $amount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // We use 'database' since you have the notifications table.
        // You can add 'mail' to this array if you want to email them too.
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title'   => 'Subscription Cancelled',
            'message' => 'Your subscription has been cancelled. ' . $this->refundStatus,
            'amount'  => $this->amount,
            'type'    => 'cancellation',
            'date'    => now(),
        ];
    }
}