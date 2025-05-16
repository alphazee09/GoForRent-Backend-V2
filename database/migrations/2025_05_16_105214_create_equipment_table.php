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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_category_id')->constrained('equipment_categories')->onDelete('cascade');
            $table->string('barcode_value')->unique();
            $table->json('images')->nullable();
            $table->unsignedInteger('min_rental_period_hours');
            $table->unsignedInteger('max_rental_period_hours');
            $table->boolean('rewards_points_acceptable')->default(false);
            $table->enum('status', ['available', 'rented', 'in_maintenance', 'unavailable'])->default('available');
            $table->unsignedInteger('rental_counter')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
