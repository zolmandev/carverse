<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class HashbackSms
{
    public static function normalizeKenyanPhone(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));

        if (preg_match('/^0([17]\d{8})$/', $phone, $matches)) {
            return '+254'.$matches[1];
        }

        if (preg_match('/^254([17]\d{8})$/', $phone, $matches)) {
            return '+254'.$matches[1];
        }

        if (preg_match('/^\+254([17]\d{8})$/', $phone, $matches)) {
            return '+254'.$matches[1];
        }

        return null;
    }

    public function sendVerificationCode(User $user): void
    {
        if (! $user->phone) {
            throw ValidationException::withMessages(['phone' => 'Add a Kenyan mobile number before requesting a code.']);
        }

        if ($user->phone_verification_sent_at?->isAfter(now()->subMinute())) {
            throw ValidationException::withMessages(['phone' => 'Please wait one minute before requesting another code.']);
        }

        $apiKey = (string) config('services.hashback.key');

        if ($apiKey === '') {
            throw ValidationException::withMessages(['phone' => 'SMS verification is temporarily unavailable. Please try again later.']);
        }

        $code = (string) random_int(100000, 999999);
        try {
            $response = Http::baseUrl(rtrim((string) config('services.hashback.base_url'), '/'))
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(10)
                ->post('/api/sms/send', [
                    'to' => $user->phone,
                    'message' => "Your Carverse verification code is {$code}. It expires in 10 minutes.",
                ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['phone' => 'The SMS service could not be reached. Please try again shortly.']);
        }

        if (! $response->successful() || $response->json('success') !== true) {
            throw ValidationException::withMessages(['phone' => $this->failureMessage($response)]);
        }

        $user->forceFill([
            'phone_verification_code_hash' => Hash::make($code),
            'phone_verification_expires_at' => now()->addMinutes(10),
            'phone_verification_sent_at' => now(),
            'phone_verification_attempts' => 0,
        ])->save();
    }

    private function failureMessage(Response $response): string
    {
        $code = (string) ($response->json('code') ?? $response->json('error.code') ?? '');
        $retryAfter = max(1, (int) $response->header('Retry-After'));

        return match (true) {
            $response->status() === 402 || $code === 'INSUFFICIENT_FUNDS' => 'SMS verification is temporarily unavailable while the messaging wallet is topped up.',
            $code === 'GLOBAL_PERMANENTLY_BLOCKED', $code === 'SENDER_NOT_APPROVED' => 'SMS verification is temporarily unavailable because the sender is not active.',
            $response->status() === 429 || $code === 'RATE_LIMITED' => "Too many SMS requests. Please try again in {$retryAfter} seconds.",
            default => 'We could not send the verification code. Please check the number and try again.',
        };
    }
}
