<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    use HasFactory;

    public function brand()
    {
        return $this->belongsTo(CarBrand::class, 'car_brand_id');
    }

    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }

    public function trim()
    {
        return $this->belongsTo(CarTrim::class, 'car_trim_id');
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [];
    }
}
