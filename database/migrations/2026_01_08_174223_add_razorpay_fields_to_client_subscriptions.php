<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('client_subscriptions', function (Blueprint $table) {
            // We check if columns exist first to avoid errors if you run this twice
            if (!Schema::hasColumn('client_subscriptions', 'razorpay_order_id')) {
                $table->string('razorpay_order_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('client_subscriptions', 'razorpay_payment_id')) {
                $table->string('razorpay_payment_id')->nullable()->after('razorpay_order_id');
            }
            if (!Schema::hasColumn('client_subscriptions', 'razorpay_signature')) {
                $table->string('razorpay_signature')->nullable()->after('razorpay_payment_id');
            }
            if (!Schema::hasColumn('client_subscriptions', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('razorpay_signature');
            }
        });
    }

    public function down()
    {
        Schema::table('client_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature', 'payment_status']);
        });
    }
};