<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class EventSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $this->user() && (! $event || $event->user_id === $this->user()->id);
    }

    public function rules(): array
    {
        return ['title' => 'required|string|max:180', 'category' => 'required|in:meetup,car_show,track_day,motorsport,workshop,other', 'description' => 'required|string|max:8000', 'organizer_name' => 'required|string|max:160', 'contact_email' => 'required|email|max:254', 'contact_phone' => ['required', 'regex:/^(?:\+?254|0)[17][0-9]{8}$/'], 'venue' => 'required|string|max:180', 'location' => 'required|string|max:180', 'starts_at_local' => 'required|date_format:Y-m-d\\TH:i', 'ends_at_local' => 'required|date_format:Y-m-d\\TH:i', 'website_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'], 'poster' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120', 'evidence' => ($this->route('event') ? 'nullable' : 'required').'|file|mimes:pdf,jpg,jpeg,png|max:5120'];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('starts_at_local') || $validator->errors()->has('ends_at_local')) {
                return;
            }$start = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $this->input('starts_at_local'), 'Africa/Nairobi');
            $end = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $this->input('ends_at_local'), 'Africa/Nairobi');
            if ($start->isPast()) {
                $validator->errors()->add('starts_at_local', 'Choose a future start time in Nairobi time.');
            }if ($end->lte($start)) {
                $validator->errors()->add('ends_at_local', 'The event must end after it starts.');
            }
        });
    }
}
