<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RacingEntry extends Model
{
    protected $guarded = [];

    protected $hidden = ['gps_path', 'slip_path'];

    protected function casts(): array
    {
        return ['is_demo' => 'boolean', 'race_date' => 'date:Y-m-d', 'reviewed_at' => 'datetime'];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
