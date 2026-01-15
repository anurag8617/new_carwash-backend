<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->string('razorpay_subscription_id')->nullable()->after('payment_id');
            $table->timestamp('canceled_at')->nullable();
            $table->string('canceled_reason')->nullable();
            $table->string('canceled_by')->nullable();
        });
    }

};
