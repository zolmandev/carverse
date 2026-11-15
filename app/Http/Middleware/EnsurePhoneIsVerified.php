<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->hasVerifiedPhone()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Verify your mobile number before continuing.',
                    'code' => 'PHONE_NOT_VERIFIED',
                ], 403);
            }

            return redirect()->guest(route('phone.verification.notice'));
        }

        return $next($request);
    }
}
