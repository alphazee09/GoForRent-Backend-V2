<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        "rental_id",
        "go4rent_signature_image_url",
        "user_signature_footprint", // Stored as JSON
        "signed_by_user_at",
        "contract_pdf_url",
        "status",
    ];

    protected $casts = [
        "user_signature_footprint" => "array",
        "signed_by_user_at" => "datetime",
    ];

    // Relationships
    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}
