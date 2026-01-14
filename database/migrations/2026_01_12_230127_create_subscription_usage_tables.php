<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Create the balances table (Check if it exists first to prevent crashes)
        if (!Schema::hasTable('user_subscription_balances')) {
            Schema::create('user_subscription_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_subscription_id')->constrained('user_subscriptions')->onDelete('cascade');
                $table->foreignId('service_id')->constrained('services');
                $table->integer('total_qty'); // e.g. 5
                $table->integer('used_qty')->default(0); // e.g. 0, then 1, 2...
                $table->timestamps();
            });
        }

        // 2. Link Orders to Subscriptions (Check if columns exist first)
        Schema::table('orders', function (Blueprint $table) {
            
            // ✅ Only add 'user_subscription_id' if it is NOT there
            if (!Schema::hasColumn('orders', 'user_subscription_id')) {
                $table->foreignId('user_subscription_id')->nullable()->constrained('user_subscriptions');
            }

            // ✅ Only add 'otp' if it is NOT there
            if (!Schema::hasColumn('orders', 'otp')) {
                $table->string('otp')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_subscription_balances');
        
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_subscription_id')) {
                // We typically don't want to drop this if other migrations depend on it, 
                // but strictly speaking, down() reverses up().
                // You can comment these out if you want to preserve the column.
                $table->dropForeign(['user_subscription_id']);
                $table->dropColumn('user_subscription_id');
            }
            
            if (Schema::hasColumn('orders', 'otp')) {
                $table->dropColumn('otp');
            }
        });
    }
};