<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('address')->nullable()->after('status'); // Full address
            $table->string('city')->nullable()->after('address');
            $table->string('pincode')->nullable()->after('city');
            $table->decimal('latitude', 10, 8)->nullable()->after('pincode');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['address', 'city', 'pincode', 'latitude', 'longitude']);
        });
    }
};
