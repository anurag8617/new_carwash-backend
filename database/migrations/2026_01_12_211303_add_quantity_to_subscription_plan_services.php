<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('subscription_plan_services', function (Blueprint $table) {
            // Add quantity column (default 1)
            $table->integer('quantity')->default(1)->after('service_id');
        });
    }

    public function down()
    {
        Schema::table('subscription_plan_services', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};