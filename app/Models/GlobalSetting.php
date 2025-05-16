<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    use HasFactory;

    protected $table = "global_settings";

    protected $fillable = [
        "setting_key",
        "setting_value",
        "group",
        "is_translatable", // This suggests translations might be stored elsewhere or handled differently
    ];

    protected $casts = [
        "is_translatable" => "boolean",
    ];

    // If is_translatable is true, the value might be a JSON of translations, or you might have a separate translations table.
    // For simplicity, if it's a JSON, you could cast setting_value to 'array' or 'json'.
    // However, the schema doesn't specify a separate translations table for global_settings, so we assume direct storage or a simple key-value approach.
}
