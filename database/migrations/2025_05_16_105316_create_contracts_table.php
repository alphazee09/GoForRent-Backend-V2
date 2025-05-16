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
        Schema::create("contracts", function (Blueprint $table) {
            $table->id();
            $table->foreignId("rental_id")->unique()->constrained("rentals")->onDelete("cascade");
            $table->string("go4rent_signature_image_url", 2048);
            $table->json("user_signature_footprint")->nullable();
            $table->timestamp("signed_by_user_at")->nullable();
            $table->string("contract_pdf_url", 2048)->nullable();
            $table->enum("status", ["company_signed", "user_signed", "active", "completed"])->default("company_signed");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("contracts");
    }
};
