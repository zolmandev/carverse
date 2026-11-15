<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\HashbackSms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PhoneVerificationController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->hasVerifiedPhone()) {
            return to_route('home');
        }

        return Inertia::render('auth/verify-phone', [
            'status' => $request->session()->get('status'),
            'phone' => $this->maskedPhone((string) $request->user()->phone),
            'hasPhone' => filled($request->user()->phone),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $error = null;

        DB::transaction(function () use ($request, $validated, &$error) {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);

            if ($user->hasVerifiedPhone()) {
                return;
            }

            if (! $user->phone_verification_code_hash || ! $user->phone_verification_expires_at) {
                $error = 'Request a verification code first.';

                return;
            }

            if ($user->phone_verification_expires_at->isPast()) {
                $error = 'This code has expired. Request a new one.';

                return;
            }

            if ($user->phone_verification_attempts >= 5) {
                $error = 'Too many incorrect attempts. Request a new code.';

                return;
            }

            if (! Hash::check($validated['code'], $user->phone_verification_code_hash)) {
                $user->increment('phone_verification_attempts');
                $error = 'That verification code is incorrect.';

                return;
            }

            $user->forceFill([
                'phone_verified_at' => now(),
                'phone_verification_code_hash' => null,
                'phone_verification_expires_at' => null,
                'phone_verification_attempts' => 0,
            ])->save();
        });

        if ($error) {
            throw ValidationException::withMessages(['code' => $error]);
        }

        $request->session()->regenerate();

        return to_route('home')->with('status', 'phone-verified');
    }

    public function send(Request $request, HashbackSms $sms): RedirectResponse
    {
        if ($request->user()->hasVerifiedPhone()) {
            return to_route('home');
        }

        $sms->sendVerificationCode($request->user());

        return back()->with('status', 'verification-code-sent');
    }

    public function updatePhone(Request $request, HashbackSms $sms): RedirectResponse
    {
        if ($request->user()->hasVerifiedPhone()) {
            return to_route('home');
        }

        $phone = HashbackSms::normalizeKenyanPhone((string) $request->phone);
        $request->merge(['phone' => $phone]);
        $request->validate([
            'phone' => [
                'required',
                'regex:/^\+254[17]\d{8}$/',
                Rule::unique(User::class)->ignore($request->user()->id),
            ],
        ]);

        $request->user()->forceFill([
            'phone' => $phone,
            'phone_verified_at' => null,
            'phone_verification_code_hash' => null,
            'phone_verification_expires_at' => null,
            'phone_verification_sent_at' => null,
            'phone_verification_attempts' => 0,
        ])->save();

        $sms->sendVerificationCode($request->user());

        return back()->with('status', 'verification-code-sent');
    }

    private function maskedPhone(string $phone): string
    {
        return strlen($phone) > 6 ? substr($phone, 0, 5).'•••'.substr($phone, -3) : $phone;
    }
}
