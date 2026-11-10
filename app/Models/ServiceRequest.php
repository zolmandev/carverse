<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
}
