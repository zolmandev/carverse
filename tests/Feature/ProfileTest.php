<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_garage_is_on_the_owners_profile_and_private_to_other_viewers(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->get('/app/garage')->assertRedirect('/profile#garage');
        $this->get('/profile')->assertInertia(fn (Assert $page) => $page->component('carverse/profile')
            ->has('vehicles', 1)->where('vehicles.0.id', $vehicle->id)
            ->missing('vehicles.0.registration'));
        $this->assertFalse(Schema::hasColumn('vehicles', 'registration'));

        $this->post('/logout');
        $this->get('/profile/'.$owner->id)->assertInertia(fn (Assert $page) => $page->component('carverse/profile')
            ->where('isOwner', false)->has('vehicles', 0));

        $other = User::factory()->create();
        $this->actingAs($other)->get('/profile/'.$owner->id)->assertInertia(fn (Assert $page) => $page->has('vehicles', 0));
    }

    public function test_profile_image_is_persisted(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile', [
            'name' => $user->name,
            'location' => 'Nairobi City',
            'profile_image' => UploadedFile::fake()->create('profile.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();
        $path = $user->refresh()->profile_image_path;

        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
    }
}
