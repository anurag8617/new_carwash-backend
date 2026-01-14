<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Only add column if it doesn't exist
            if (!Schema::hasColumn('orders', 'user_subscription_id')) {
                $table->foreignId('user_subscription_id')
                      ->nullable()
                      ->after('payment_method') // Places it nicely after payment_method
                      ->constrained('user_subscriptions')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_subscription_id')) {
                // Drop the foreign key first, then the column
                $table->dropForeign(['user_subscription_id']);
                $table->dropColumn('user_subscription_id');
            }
        });
    }
};