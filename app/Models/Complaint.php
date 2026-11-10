<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [];
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
