<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "equipment_id",
        "rental_start_datetime",
        "rental_end_datetime",
        "total_amount",
        "payment_method",
        "payment_status",
        "status",
    ];

    protected $casts = [
        "rental_start_datetime" => "datetime",
        "rental_end_datetime" => "datetime",
        "total_amount" => "decimal:2",
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

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function damageReports()
    {
        return $this->hasMany(DamageReport::class);
    }

    public function rewardPointsHistory()
    {
        // A rental might be paid by points, or points might be earned from a rental (future scope)
        // This assumes a rental can be the reason for a reward point transaction.
        return $this->hasMany(RewardPointsHistory::class, "related_rental_id");
    }
}
