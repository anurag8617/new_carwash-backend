<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Plans defined by Vendors
        Schema::create('vendor_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->string('name'); // e.g., "Gold Wash Package"
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('duration_days'); // e.g., 30 for Monthly, 90 for Quarterly
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // For history tracking
            
            $table->index(['vendor_id', 'is_active']);
        });

        // 2. Pivot: Link Plans to specific Services (Many-to-Many)
        Schema::create('subscription_plan_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('vendor_subscription_plans')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            // Prevent duplicate services in one plan
            $table->unique(['plan_id', 'service_id']); 
        });

        // 3. Payment Records (Razorpay Log)
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('plan_id')->constrained('vendor_subscription_plans');
            $table->string('razorpay_order_id')->index();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('INR');
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamps();
        });

        // 4. Active User Subscriptions
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('vendor_subscription_plans');
            $table->foreignId('payment_id')->constrained('subscription_payments');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_plan_services');
        Schema::dropIfExists('vendor_subscription_plans');
    }
};