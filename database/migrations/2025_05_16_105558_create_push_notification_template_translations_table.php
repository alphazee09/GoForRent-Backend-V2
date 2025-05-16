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
        Schema::create("push_notification_template_translations", function (Blueprint $table) {
            $table->id();
            $table->foreignId("push_notification_template_id")->constrained("push_notification_templates")->onDelete("cascade")->name("pntt_template_id_fk");
            $table->string("locale", 10)->index();
            $table->string("title");
            $table->text("body");
            $table->timestamps();

            $table->unique(["push_notification_template_id", "locale"], "pntt_template_id_locale_uq");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("push_notification_template_translations");
    }
};
