<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
  public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        // Add these columns after the 'id' column
        $table->string('first_name')->nullable()->after('id');
        $table->string('last_name')->nullable()->after('first_name');
        $table->string('phone')->unique()->nullable()->after('email');
        $table->enum('role', ['admin', 'vendor', 'staff', 'client'])->default('client')->after('phone');

        // Also, let's make the default name column nullable
        $table->string('name')->nullable()->change();
    });
}

/**
 * Reverse the migrations.
 */
public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['first_name', 'last_name', 'phone', 'role']);
        $table->string('name')->nullable(false)->change();
    });
}
};
