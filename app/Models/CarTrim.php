<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarTrim extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'model_year' => 'integer'];
    }

    public function model()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }
}
