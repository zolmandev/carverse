<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $hidden = ['commission_minor', 'variable_cost_minor'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quote_snapshot' => 'array', 'completed_at' => 'datetime', 'fitment_return' => 'boolean'];
    }

    public function request()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
