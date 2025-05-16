<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Equipment extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ["name", "description"];

    protected $fillable = [
        "equipment_category_id",
        "barcode_value",
        "images", // Stored as JSON
        "min_rental_period_hours",
        "max_rental_period_hours",
        "rewards_points_acceptable",
        "status",
        "rental_counter",
    ];

    protected $casts = [
        "images" => "array",
        "rewards_points_acceptable" => "boolean",
        "min_rental_period_hours" => "integer",
        "max_rental_period_hours" => "integer",
        "rental_counter" => "integer",
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(EquipmentCategory::class, "equipment_category_id");
    }

    public function translations()
    {
        return $this->hasMany(EquipmentTranslation::class);
    }

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }

    public function reviews()
    {
        return $this->hasMany(ReviewRating::class);
    }

    public function damageReports()
    {
        return $this->hasMany(DamageReport::class);
    }
}
