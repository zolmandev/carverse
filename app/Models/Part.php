<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? url('/photos/part/'.$this->id) : null;
    }

    public function fitments()
    {
        return $this->hasMany(Fitment::class);
    }

    protected $guarded = [];

    protected $hidden = ['path', 'photo_path'];

    protected function casts(): array
    {
        return [];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function fingerprint(): string
    {
        return hash('sha256', json_encode($this->only(['title', 'brand', 'oem_reference', 'description', 'condition'])));
    }
}
