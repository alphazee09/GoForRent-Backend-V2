<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentTranslation extends Model
{
    use HasFactory;

    protected $fillable = ["equipment_id", "locale", "name", "description"];

    public $timestamps = true;

    // Relationship to the parent Equipment model
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
