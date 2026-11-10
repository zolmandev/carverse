<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['items' => 'array', 'valid_until' => 'date'];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function request()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }
}
