<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class EquipmentCategory extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ["name"];

    protected $fillable = []; // No mass assignable fields directly on this table, names are in translations

    // Relationships
    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function translations()
    {
        return $this->hasMany(EquipmentCategoryTranslation::class);
    }
}
