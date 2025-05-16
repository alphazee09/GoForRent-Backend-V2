<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        "rental_id",
        "user_id",
        "amount",
        "payment_method",
        "transaction_id",
        "status",
        "wire_transfer_receipt_url",
        "payment_gateway_response", // Stored as JSON
    ];

    protected $casts = [
        "amount" => "decimal:2",
        "payment_gateway_response" => "array",
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
}
