<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Create CLIENTS table
        if (!Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('pincode')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->timestamps();
            });
        }

        // 2. Add Profile Image to USERS table (if missing)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('device_token');
            }
        });

        // 3. Add Extra Details to STAFFS table
        Schema::table('staffs', function (Blueprint $table) {
            if (!Schema::hasColumn('staffs', 'designation')) {
                $table->string('designation')->nullable()->after('salary'); // e.g., 'Senior Washer'
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
        Schema::dropIfExists('clients');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_image')) $table->dropColumn('profile_image');
        });

        Schema::table('staffs', function (Blueprint $table) {
            $table->dropColumn(['designation', 'id_proof_image', 'emergency_contact']);
        });
    }
};