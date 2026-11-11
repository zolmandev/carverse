<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $appends = ['photo_url', 'logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? url('/provider-logos/'.$this->id.'?v='.substr(sha1($this->logo_path), 0, 12)) : null;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? url('/photos/provider/'.$this->id) : null;
    }

    protected $guarded = [];

    protected $hidden = ['path', 'photo_path', 'logo_path', 'qualifications', 'review_note', 'reviewed_by'];

    protected function casts(): array
    {
        return ['specialties' => 'array', 'verified_at' => 'datetime', 'reviewed_at' => 'datetime', 'years_experience' => 'integer'];
    }

    public function reviews()
    {
        return $this->hasManyThrough(Review::class, Transaction::class, 'organization_id', 'transaction_id');
    }

    public function complaints()
    {
        return $this->hasManyThrough(Complaint::class, Transaction::class, 'organization_id', 'transaction_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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
