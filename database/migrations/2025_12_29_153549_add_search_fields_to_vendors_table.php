<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Stores the average rating (e.g., 4.5) for "Rating 4★+" filter
            if (!Schema::hasColumn('vendors', 'average_rating')) {
                $table->decimal('average_rating', 3, 2)->default(0.00)->after('fee_percentage')->index();
            }

            // Stores total count of reviews (e.g., 150 reviews) for credibility display
            if (!Schema::hasColumn('vendors', 'review_count')) {
                $table->unsignedInteger('review_count')->default(0)->after('average_rating');
            }

            // For "Availability" filter - allows vendors to toggle their visibility
            if (!Schema::hasColumn('vendors', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('review_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'review_count', 'is_active']);
        });
    }
};