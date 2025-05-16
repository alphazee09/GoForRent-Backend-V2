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
        Schema::create("email_template_translations", function (Blueprint $table) {
            $table->id();
            $table->foreignId("email_template_id")->constrained("email_templates")->onDelete("cascade");
            $table->string("locale", 10)->index();
            $table->string("subject");
            $table->text("body_html");
            $table->timestamps();

            $table->unique(["email_template_id", "locale"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("email_template_translations");
    }
};
