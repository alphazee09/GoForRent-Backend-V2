<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewRating extends Model
{
    use HasFactory;

    protected $table = "reviews_ratings";

    protected $fillable = [
        "user_id",
        "equipment_id",
        "rating",
        "review_text",
        "is_approved",
    ];

    protected $casts = [
        "rating" => "integer",
        "is_approved" => "boolean",
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
