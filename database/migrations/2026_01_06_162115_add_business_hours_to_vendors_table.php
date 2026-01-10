<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessHoursToVendorsTable extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->time('opening_time')->nullable()->after('website');
            $table->time('closing_time')->nullable()->after('opening_time');
            $table->json('operating_days')->nullable()->after('closing_time'); // Stores days like ["Mon", "Tue", "Wed"]
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['opening_time', 'closing_time', 'operating_days']);
        });
    }
}