<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? url('/photos/provider/'.$this->id) : null;
    }

    protected $guarded = [];

    protected $hidden = ['path', 'photo_path'];

    protected function casts(): array
    {
        return ['specialties' => 'array', 'verified_at' => 'datetime'];
    }

    public function members()
    {
        return $this->belongsToMany(User::class);
    }

    public function parts()
    {
        return $this->hasMany(Part::class);
    }
}
