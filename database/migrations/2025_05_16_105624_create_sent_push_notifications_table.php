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
        Schema::create("sent_push_notifications", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->nullable()->constrained("users")->onDelete("set null"); // Nullable for broadcast
            $table->string("title");
            $table->text("body");
            $table->json("data")->nullable();
            $table->timestamp("sent_at")->useCurrent();
            $table->enum("status", ["sent", "failed"])->nullable();
            $table->text("response")->nullable(); // Response from Firebase
            $table->foreignId("created_by_admin_id")->nullable()->constrained("users")->onDelete("set null");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("sent_push_notifications");
    }
};
