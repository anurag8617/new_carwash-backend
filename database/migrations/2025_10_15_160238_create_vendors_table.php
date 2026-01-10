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
   // database/migrations/...._create_vendors_table.php

public function up(): void
{
    Schema::create('vendors', function (Blueprint $table) {
        $table->id(); // id (PK)
        $table->foreignId('admin_id')->constrained('users'); // FK -> users
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('address');
        $table->decimal('location_lat', 10, 7);
        $table->decimal('location_lng', 10, 7);
        $table->decimal('fee_percentage', 5, 2);
        $table->timestamps(); // created_at and updated_at
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendors');
    }
};
