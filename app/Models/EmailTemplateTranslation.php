<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplateTranslation extends Model
{
    use HasFactory;

    protected $fillable = ["email_template_id", "locale", "subject", "body_html"];

    public $timestamps = true;

    // Relationship to the parent EmailTemplate model
    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
