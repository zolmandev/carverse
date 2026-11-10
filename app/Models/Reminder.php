<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'notified_at' => 'datetime'];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
