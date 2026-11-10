<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['service_date' => 'date'];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function revisions()
    {
        return $this->hasMany(RecordRevision::class);
    }
}
