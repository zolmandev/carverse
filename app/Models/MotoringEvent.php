<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotoringEvent extends Model
{
    protected $guarded = [];

    protected $hidden = ['user_id', 'contact_email', 'contact_phone', 'poster_path', 'evidence_path', 'evidence_name', 'review_note', 'reviewed_by'];

    protected $appends = ['poster_url'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'reviewed_at' => 'datetime', 'version' => 'integer', 'approved_version' => 'integer'];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getPosterUrlAttribute(): ?string
    {
        return $this->poster_path ? url('/events/'.$this->id.'/poster?v='.$this->version) : null;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'approved')->whereColumn('approved_version', 'version');
    }
}
