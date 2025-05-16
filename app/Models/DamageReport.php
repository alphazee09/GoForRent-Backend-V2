<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    use HasFactory;

    protected $fillable = [
        "rental_id",
        "user_id",
        "equipment_id",
        "description",
        "images", // Stored as JSON
        "ai_assessment_details",
        "status",
        "resolution_details",
        "reported_at",
        "resolved_at",
    ];

    protected $casts = [
        "images" => "array",
        "reported_at" => "datetime",
        "resolved_at" => "datetime",
    ];

    // Relationships
    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
