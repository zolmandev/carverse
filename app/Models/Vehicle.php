<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? url('/photos/vehicle/'.$this->id) : null;
    }

    protected $guarded = [];

    protected $hidden = ['path', 'photo_path'];

    protected function casts(): array
    {
        return ['identifier' => 'encrypted', 'mileage_date' => 'date'];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function records()
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function fingerprint(): string
    {
        return hash('sha256', json_encode($this->only(['make', 'model', 'year', 'variant', 'engine_code', 'fuel_type', 'transmission', 'identifier'])));
    }
}
