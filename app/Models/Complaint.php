<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['moderation_note', 'reviewed_by', 'public_approved_by'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'public_approved_at' => 'datetime'];
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
