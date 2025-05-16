<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardPointsHistory extends Model
{
    use HasFactory;

    protected $table = "reward_points_history"; // Explicitly define table name if it differs from pluralized model name

    protected $fillable = [
        "user_id",
        "points_change",
        "reason",
        "related_rental_id",
    ];

    protected $casts = [
        "points_change" => "integer",
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rental()
    {
        return $this->belongsTo(Rental::class, "related_rental_id");
    }
}
