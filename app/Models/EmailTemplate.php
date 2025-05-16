<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class EmailTemplate extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ["subject", "body_html"];

    protected $fillable = [
        "template_name",
    ];

    // Relationships
    public function translations()
    {
        return $this->hasMany(EmailTemplateTranslation::class);
    }
}
