<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(User $user): Response
    {
        $isOwner = auth()->id() === $user->id;

        return Inertia::render('carverse/profile', [
            'profile' => $user->only(['id', 'name', 'role', 'location', 'profile_image_url', 'created_at']),
            'isOwner' => $isOwner,
            'counties' => config('kenya-counties'),
            'vehicles' => $isOwner ? $user->vehicles()->latest()->get() : [],
            'activeVehicleId' => $isOwner ? request()->session()->get('active_vehicle') : null,
        ]);
    }

    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('carverse/profile', [
            'profile' => $user->only(['id', 'name', 'role', 'location', 'profile_image_url', 'created_at']),
            'isOwner' => true,
            'counties' => config('kenya-counties'),
            'vehicles' => $user->vehicles()->latest()->get(),
            'activeVehicleId' => $request->session()->get('active_vehicle'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => ['nullable', Rule::in(config('kenya-counties'))],
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);
        unset($data['profile_image']);
        $oldPath = $user->profile_image_path;
        if ($request->hasFile('profile_image')) {
            $data['profile_image_path'] = $request->file('profile_image')->store('profile-images', 'local');
        }
        // The image path is intentionally hidden and not mass-assignable on the model.
        // Save this already-validated profile payload explicitly so uploads persist.
        $user->forceFill($data)->save();
        if (! empty($data['profile_image_path']) && $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('message', 'Profile updated.');
    }

    public function image(User $user)
    {
        abort_unless($user->profile_image_path, 404);

        return response()->file(Storage::disk('local')->path($user->profile_image_path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
