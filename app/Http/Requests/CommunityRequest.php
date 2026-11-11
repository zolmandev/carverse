<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:160', 'car_brand_id' => ['required', Rule::exists('car_brands', 'id')->where('is_active', true)], 'car_model_id' => ['required', Rule::exists('car_models', 'id')->where('car_brand_id', $this->input('car_brand_id'))->where('is_active', true)], 'car_trim_id' => ['nullable', Rule::exists('car_trims', 'id')->where('car_model_id', $this->input('car_model_id'))->where('is_active', true)], 'url' => ['required', 'url', 'max:255', 'regex:~^https://chat\\.whatsapp\\.com/[a-zA-Z0-9]+$~']];
    }
}
