<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'image')) {
                $table->string('image')->nullable()->after('name');
            }
            if (!Schema::hasColumn('vendors', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }
            if (!Schema::hasColumn('vendors', 'website')) {
                $table->string('website')->nullable()->after('phone');
            }
        });
    }

    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Safely drop columns
            $cols = ['image', 'phone', 'website'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('vendors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};