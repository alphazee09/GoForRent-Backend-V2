<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $guard_name = "api"; // Add this line to specify the guard

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "full_name",
        "email",
        "phone_number",
        "password",
        "profile_image_url",
        "otp",
        "otp_expires_at",
        "reward_points",
        "is_verified_badge",
        "verified_badge_request_status",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        "password",
        "remember_token",
        "otp",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "email_verified_at" => "datetime",
        "phone_verified_at" => "datetime",
        "otp_expires_at" => "datetime",
        "password" => "hashed",
        "is_verified_badge" => "boolean",
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships
    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }

    public function reviews()
    {
        return $this->hasMany(ReviewRating::class);
    }

    public function damageReports()
    {
        return $this->hasMany(DamageReport::class);
    }

    public function rewardPointsHistory()
    {
        return $this->hasMany(RewardPointsHistory::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function sentPushNotifications()
    {
        return $this->hasMany(SentPushNotification::class, "created_by_admin_id");
    }
}

