<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        "image_url_en",
        "image_url_ar",
        "link_url",
        "display_order",
        "is_active",
    ];

    protected $casts = [
        "is_active" => "boolean",
        "display_order" => "integer",
    ];

    // No direct relationships defined in the schema for Banner, but you could add
    // accessors/mutators for image URLs if they need specific logic (e.g., full path construction)
}
