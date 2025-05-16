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
        Schema::create("global_settings", function (Blueprint $table) {
            $table->id();
            $table->string("setting_key")->unique();
            $table->text("setting_value")->nullable();
            $table->string("group", 50)->nullable()->index();
            $table->boolean("is_translatable")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("global_settings");
    }
};
