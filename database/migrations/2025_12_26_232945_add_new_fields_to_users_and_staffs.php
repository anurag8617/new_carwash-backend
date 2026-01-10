<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Add profile_image to Users (Admins, Vendors, etc.)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('device_token');
            }
        });

        // 2. Add extra details to Staffs (Designation, ID Proof, Emergency Contact)
        Schema::table('staffs', function (Blueprint $table) {
            if (!Schema::hasColumn('staffs', 'designation')) {
                $table->string('designation')->nullable()->after('salary'); // e.g. "Manager"
            }
            if (!Schema::hasColumn('staffs', 'id_proof_image')) {
                $table->string('id_proof_image')->nullable()->after('profile_image'); 
            }
            if (!Schema::hasColumn('staffs', 'emergency_contact')) {
                $table->string('emergency_contact')->nullable()->after('phone');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_image')) {
                $table->dropColumn('profile_image');
            }
        });

        Schema::table('staffs', function (Blueprint $table) {
            $table->dropColumn(['designation', 'id_proof_image', 'emergency_contact']);
        });
    }
};