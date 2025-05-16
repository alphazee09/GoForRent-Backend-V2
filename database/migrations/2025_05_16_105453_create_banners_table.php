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
        Schema::create("banners", function (Blueprint $table) {
            $table->id();
            $table->string("image_url_en", 2048);
            $table->string("image_url_ar", 2048)->nullable();
            $table->string("link_url", 2048)->nullable();
            $table->integer("display_order")->default(0);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("banners");
    }
};
