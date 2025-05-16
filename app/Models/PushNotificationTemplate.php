<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PushNotificationTemplate extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ["title", "body"];

    protected $fillable = [
        "template_name",
    ];

    // Relationships
    public function translations()
    {
        return $this->hasMany(PushNotificationTemplateTranslation::class);
    }
}
