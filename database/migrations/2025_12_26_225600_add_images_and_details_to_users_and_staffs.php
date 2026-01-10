<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Update users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('device_token'); // For user avatar
            }
        });

        // Update staffs table
        Schema::table('staffs', function (Blueprint $table) {
            if (!Schema::hasColumn('staffs', 'designation')) {
                $table->string('designation')->nullable()->after('salary'); // e.g. "Senior Washer"
            }
            if (!Schema::hasColumn('staffs', 'id_proof_image')) {
                $table->string('id_proof_image')->nullable()->after('profile_image'); // For upload ID
            }
            if (!Schema::hasColumn('staffs', 'emergency_contact')) {
                $table->string('emergency_contact')->nullable()->after('phone');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_image');
        });

        Schema::table('staffs', function (Blueprint $table) {
            $table->dropColumn(['designation', 'id_proof_image', 'emergency_contact']);
        });
    }
};