<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone', 'location', 'reminder_notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'profile_image_path',
        'phone_verification_code_hash',
    ];

    protected $appends = ['profile_image_url'];

    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->profile_image_path ? url('/profiles/'.$this->id.'/image?v='.$this->updated_at?->timestamp) : null;
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function inOrganization(int $id): bool
    {
        return $this->organizations()->whereKey($id)->where('status', 'approved')->exists();
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'phone_verification_expires_at' => 'datetime',
            'phone_verification_sent_at' => 'datetime',
            'phone_verification_attempts' => 'integer',
            'password' => 'hashed',
            'reminder_notifications' => 'boolean',
        ];
    }
}
