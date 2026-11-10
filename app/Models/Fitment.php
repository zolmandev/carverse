<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fitment extends Model
{
    use HasFactory;

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return ['assessed_at' => 'datetime', 'vehicle_details' => 'array', 'part_details' => 'array'];
    }
}
