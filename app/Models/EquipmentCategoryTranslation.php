<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentCategoryTranslation extends Model
{
    use HasFactory;

    protected $fillable = ["equipment_category_id", "locale", "name"];

    public $timestamps = true; // Ensure timestamps are managed if not already default

    // Relationship to the parent EquipmentCategory model
    public function equipmentCategory()
    {
        return $this->belongsTo(EquipmentCategory::class);
    }
}
