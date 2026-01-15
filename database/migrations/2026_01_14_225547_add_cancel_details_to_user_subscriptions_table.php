<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Stores the actual Razorpay Sub ID (starts with sub_) if you are using Razorpay's Recurring API
            $table->string('razorpay_subscription_id')->nullable()->after('payment_id');
            
            // Cancellation tracking
            $table->timestamp('canceled_at')->nullable();
            $table->string('canceled_reason')->nullable();
            $table->string('canceled_by')->nullable()->comment('client, admin, system');
        });
    }

    public function down()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['razorpay_subscription_id', 'canceled_at', 'canceled_reason', 'canceled_by']);
        });
    }
};