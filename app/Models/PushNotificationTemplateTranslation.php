<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationTemplateTranslation extends Model
{
    use HasFactory;

    protected $fillable = ["push_notification_template_id", "locale", "title", "body"];

    public $timestamps = true;

    // Relationship to the parent PushNotificationTemplate model
    public function pushNotificationTemplate()
    {
        return $this->belongsTo(PushNotificationTemplate::class);
    }
}
