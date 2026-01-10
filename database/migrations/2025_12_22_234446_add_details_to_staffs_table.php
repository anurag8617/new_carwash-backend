<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('staffs', function (Blueprint $table) {
            if (!Schema::hasColumn('staffs', 'phone')) $table->string('phone')->nullable()->after('user_id');
            if (!Schema::hasColumn('staffs', 'address')) $table->text('address')->nullable()->after('phone');
            if (!Schema::hasColumn('staffs', 'joining_date')) $table->date('joining_date')->nullable()->after('address');
            if (!Schema::hasColumn('staffs', 'status')) $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active')->after('joining_date');
            if (!Schema::hasColumn('staffs', 'salary')) $table->decimal('salary', 10, 2)->nullable()->after('status');
            if (!Schema::hasColumn('staffs', 'profile_image')) $table->string('profile_image')->nullable()->after('salary');
        });
    }

    public function down()
    {
        Schema::table('staffs', function (Blueprint $table) {
            $cols = ['phone', 'address', 'joining_date', 'status', 'salary', 'profile_image'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('staffs', $col)) $table->dropColumn($col);
            }
        });
    }
};