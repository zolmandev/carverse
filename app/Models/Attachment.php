<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['path', 'photo_path'];

    protected function casts(): array
    {
        return [];
    }

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function record()
    {
        return $this->belongsTo(ServiceRecord::class, 'service_record_id');
    }

    public function request()
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }
}
