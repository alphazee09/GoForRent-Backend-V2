<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentPushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "title",
        "body",
        "data",
        "sent_at",
        "status",
        "response",
        "created_by_admin_id",
    ];

    protected $casts = [
        "data" => "array",
        "sent_at" => "datetime",
    ];

    // Relationships
    public function user() // The recipient user
    {
        return $this->belongsTo(User::class);
    }

    public function createdByAdmin() // The admin who sent the notification
    {
        return $this->belongsTo(User::class, "created_by_admin_id");
    }
}
