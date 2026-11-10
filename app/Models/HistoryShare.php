<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryShare extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['record_ids' => 'array', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
