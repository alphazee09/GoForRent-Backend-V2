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
        Schema::create("equipment_category_translations", function (Blueprint $table) {
            $table->id();
            $table->foreignId("equipment_category_id")->constrained("equipment_categories")->onDelete("cascade");
            $table->string("locale", 10)->index();
            $table->string("name");
            $table->timestamps();

            $table->unique(["equipment_category_id", "locale"], "eq_cat_trans_cat_id_locale_uq");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("equipment_category_translations");
    }
};
