<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', 'make' => 'required|string|max:80', 'model' => 'required|string|max:80', 'year' => 'required|integer|min:1950|max:'.(date('Y') + 1), 'variant' => 'nullable|string|max:100', 'engine_code' => 'nullable|string|max:80', 'fuel_type' => 'required|in:Petrol,Diesel,Hybrid,Electric', 'transmission' => 'required|in:Automatic,Manual,CVT', 'identifier' => 'nullable|string|min:4|max:80', 'mileage' => 'required|integer|min:0|max:3000000', 'mileage_date' => 'required|date|before_or_equal:today'];
    }
}
