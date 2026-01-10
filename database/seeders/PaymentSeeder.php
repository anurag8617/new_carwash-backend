<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ClientSubscription;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Backfill Payments from Paid Orders
        $paidOrders = Order::where('payment_status', 'paid')->get();

        foreach ($paidOrders as $order) {
            // Check if payment record already exists to avoid duplicates
            $exists = Payment::where('payable_type', Order::class)
                ->where('payable_id', $order->id)
                ->exists();

            if (!$exists) {
                Payment::create([
                    'user_id' => $order->client_id,
                    'order_id' => $order->id,
                    'payable_id' => $order->id,
                    'payable_type' => Order::class,
                    'amount' => $order->price,
                    // Use actual razorpay IDs if available, otherwise generate dummy ones for display
                    'razorpay_payment_id' => $order->payment_method ?? 'pay_manual_' . $order->id, 
                    'razorpay_order_id' => $order->razorpay_order_id ?? 'order_manual_' . $order->id,
                    'status' => 'captured',
                    'payment_method' => 'card', // Default placeholder
                    'created_at' => $order->paid_at ?? $order->updated_at,
                    'updated_at' => $order->paid_at ?? $order->updated_at,
                ]);
            }
        }

        // 2. Backfill Payments from Paid Subscriptions
        $paidSubs = ClientSubscription::where('payment_status', 'paid')->get();

        foreach ($paidSubs as $sub) {
            $exists = Payment::where('payable_type', ClientSubscription::class)
                ->where('payable_id', $sub->id)
                ->exists();

            if (!$exists) {
                Payment::create([
                    'user_id' => $sub->client_id,
                    'order_id' => 0, // No order ID for subscriptions
                    'payable_id' => $sub->id,
                    'payable_type' => ClientSubscription::class,
                    'amount' => $sub->plan->price ?? 0,
                    'razorpay_payment_id' => $sub->razorpay_payment_id ?? 'sub_pay_' . $sub->id,
                    'razorpay_order_id' => $sub->razorpay_order_id ?? 'sub_order_' . $sub->id,
                    'status' => 'captured',
                    'payment_method' => 'card',
                    'created_at' => $sub->created_at,
                    'updated_at' => $sub->updated_at,
                ]);
            }
        }

        $this->command->info('Payment records successfully generated from existing orders!');
    }
}