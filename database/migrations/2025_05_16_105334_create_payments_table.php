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
        Schema::create("payments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("rental_id")->nullable()->constrained("rentals")->onDelete("set null");
            $table->foreignId("user_id")->constrained("users")->onDelete("cascade");
            $table->decimal("amount", 10, 2);
            $table->enum("payment_method", ["thawani", "wire_transfer", "reward_points"]);
            $table->string("transaction_id")->nullable();
            $table->enum("status", ["pending", "successful", "failed", "pending_approval", "approved", "rejected"])->default("pending");
            $table->string("wire_transfer_receipt_url", 2048)->nullable();
            $table->json("payment_gateway_response")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("payments");
    }
};
