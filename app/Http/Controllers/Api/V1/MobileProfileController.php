<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MobileProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => self::profileData($request->user())]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($request->user()->id)],
            'location' => ['sometimes', 'nullable', Rule::in(config('kenya-counties'))],
        ]);
        $request->user()->update($data);

        return response()->json(['data' => self::profileData($request->user()->fresh())]);
    }

    public function photo(Request $request): JsonResponse
    {
        $request->validate(['profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072']]);
        $user = $request->user();
        $oldPath = $user->profile_image_path;
        $newPath = $request->file('profile_image')->store('profile-images', 'local');
        $user->forceFill(['profile_image_path' => $newPath])->save();

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return response()->json(['data' => self::profileData($user->fresh())]);
    }

    public function image(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->profile_image_path, 404);

        return response()->file(Storage::disk('local')->path($request->user()->profile_image_path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function destroyPhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        $oldPath = $user->profile_image_path;
        $user->forceFill(['profile_image_path' => null])->save();

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return response()->json(['data' => self::profileData($user->fresh())]);
    }

    public static function profileData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
            'role' => $user->role,
            'location' => $user->location,
            'profile_image_url' => $user->profile_image_path ? url('/api/v1/profile/photo') : null,
            'joined_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
