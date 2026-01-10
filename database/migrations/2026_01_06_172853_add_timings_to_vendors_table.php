<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimingsToVendorsTable extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Only add if they don't exist
            if (!Schema::hasColumn('vendors', 'opening_time')) {
                $table->time('opening_time')->nullable()->after('website');
            }
            if (!Schema::hasColumn('vendors', 'closing_time')) {
                $table->time('closing_time')->nullable()->after('opening_time');
            }
            if (!Schema::hasColumn('vendors', 'operating_days')) {
                $table->json('operating_days')->nullable()->after('closing_time');
            }
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['opening_time', 'closing_time', 'operating_days']);
        });
    }
}