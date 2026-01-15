<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order;
use Carbon\Carbon;

class BookingConfirmedNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        // Format Date and Time
        $formattedDate = Carbon::parse($this->order->scheduled_time)->format('d M Y, h:i A');
        
        // Construct Detailed Message
        $vendorName = $this->order->vendor->name ?? 'Vendor';
        $serviceName = $this->order->service->name ?? 'Service';
        $price = $this->order->price > 0 ? "₹{$this->order->price}" : "Free (Subscription)";
        
        $message = "Your booking for {$serviceName} with {$vendorName} is confirmed! 🗓️ {$formattedDate} 📍 {$this->order->address}. Payment: {$price}.";

        return [
            'title' => 'Booking Confirmed ✅ order #' . $this->order->id,
            'message' => $message,
            'order_id' => $this->order->id,
            'type' => 'booking_confirmed',
            'data' => [
                'vendor' => $vendorName,
                'service' => $serviceName,
                'time' => $formattedDate,
                'price' => $price,
                'address' => $this->order->address
            ]
        ];
    }
}