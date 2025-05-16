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
        Schema::create("rentals", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users")->onDelete("cascade");
            $table->foreignId("equipment_id")->constrained("equipment")->onDelete("cascade");
            $table->timestamp("rental_start_datetime");
            $table->timestamp("rental_end_datetime");
            $table->decimal("total_amount", 10, 2);
            $table->enum("payment_method", ["thawani", "wire_transfer", "reward_points"]);
            $table->enum("payment_status", ["pending_payment", "pending_approval", "paid", "failed", "rejected", "cancelled"])->default("pending_payment");
            $table->enum("status", ["pending_payment", "confirmed", "pickup_pending_signature", "active", "completed", "cancelled", "damage_reported"])->default("pending_payment");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("rentals");
    }
};
