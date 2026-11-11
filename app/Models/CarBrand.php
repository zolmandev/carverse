<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarBrand extends Model
{
    protected $guarded = [];

    protected $hidden = ['logo_path', 'logo_asset'];

    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function models()
    {
        return $this->hasMany(CarModel::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? url('/cars/brands/'.$this->id.'/logo?v='.substr(sha1($this->logo_path), 0, 12)) : ($this->logo_asset ? asset('car-logos/'.$this->logo_asset) : null);
    }
}
