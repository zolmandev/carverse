<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileAccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_login_use_profile_and_logout_with_a_bearer_token(): void
    {
        $user = User::factory()->create(['name' => 'Mobile User', 'email' => 'mobile@example.test']);
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'mobile@example.test',
            'password' => 'password',
            'device_name' => 'Test Phone',
        ])->assertOk()->assertJsonPath('data.user.id', $user->id);
        $token = $login->json('data.token');

        $this->withToken($token)->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Mobile User')
            ->assertJsonMissingPath('data.password');

        $this->withToken($token)->patchJson('/api/v1/profile', [
            'name' => 'Updated Mobile User',
            'location' => 'Nairobi City',
        ])->assertOk()->assertJsonPath('data.name', 'Updated Mobile User');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/v1/profile')->assertUnauthorized();
    }

    public function test_unverified_or_invalid_accounts_cannot_create_mobile_tokens(): void
    {
        User::factory()->unverified()->create(['email' => 'unverified@example.test']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@example.test',
            'password' => 'password',
            'device_name' => 'Test Phone',
        ])->assertForbidden()->assertJsonPath('code', 'PHONE_NOT_VERIFIED');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@example.test',
            'password' => 'wrong-password',
            'device_name' => 'Test Phone',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_profile_photo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('local');
        [$user, $token] = $this->authenticatedUser();

        $this->withToken($token)->post('/api/v1/profile/photo', [
            'profile_image' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $path = $user->fresh()->profile_image_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        Auth::forgetGuards();
        $this->withoutHeader('Authorization')->getJson('/api/v1/profile/photo')->assertUnauthorized();
        $this->withToken($token)->get('/api/v1/profile/photo', ['Accept' => 'application/json'])->assertOk();

        $this->withToken($token)->deleteJson('/api/v1/profile/photo')->assertOk()->assertJsonPath('data.profile_image_url', null);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_user_can_create_list_read_and_update_only_their_cars(): void
    {
        [$user, $token] = $this->authenticatedUser();
        $otherCar = Vehicle::factory()->create();
        $payload = [
            'make' => 'Mazda',
            'model' => 'Demio',
            'year' => 2018,
            'variant' => 'Skyactiv',
            'fuel_type' => 'Petrol',
            'transmission' => 'Automatic',
            'identifier' => 'DJ5FS-123456',
            'mileage' => 62000,
            'mileage_date' => today()->toDateString(),
        ];

        $created = $this->withToken($token)->postJson('/api/v1/cars', $payload)
            ->assertCreated()
            ->assertJsonPath('data.make', 'Mazda')
            ->assertJsonPath('data.has_identifier', true)
            ->assertJsonMissingPath('data.identifier');
        $carId = $created->json('data.id');

        $this->withToken($token)->getJson('/api/v1/cars')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $carId);
        $this->withToken($token)->getJson('/api/v1/cars/'.$otherCar->id)->assertNotFound();
        $this->withToken($token)->patchJson('/api/v1/cars/'.$otherCar->id, ['mileage' => 90000, 'mileage_date' => today()->toDateString()])->assertNotFound();

        $this->withToken($token)->patchJson('/api/v1/cars/'.$carId, [
            'mileage' => 63000,
            'mileage_date' => today()->toDateString(),
        ])->assertOk()->assertJsonPath('data.mileage', 63000);
        $this->withToken($token)->patchJson('/api/v1/cars/'.$carId, [
            'mileage' => 61000,
            'mileage_date' => today()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('mileage');

        $this->assertSame($user->id, Vehicle::findOrFail($carId)->user_id);
    }

    public function test_vehicle_photo_is_private_and_vehicle_deletion_preserves_history(): void
    {
        Storage::fake('local');
        [$user, $token] = $this->authenticatedUser();
        $car = Vehicle::factory()->create(['user_id' => $user->id]);

        $this->withToken($token)->post('/api/v1/cars/'.$car->id.'/photo', [
            'photo' => UploadedFile::fake()->create('car.jpg', 100, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertOk();
        $path = $car->fresh()->photo_path;
        Storage::disk('local')->assertExists($path);
        Auth::forgetGuards();
        $this->withoutHeader('Authorization')->getJson('/api/v1/cars/'.$car->id.'/photo')->assertUnauthorized();
        $this->withToken($token)->get('/api/v1/cars/'.$car->id.'/photo', ['Accept' => 'application/json'])->assertOk();

        ServiceRequest::create([
            'user_id' => $user->id,
            'vehicle_id' => $car->id,
            'kind' => 'service',
            'title' => 'Oil service',
            'notes' => 'Retained history',
            'status' => 'requested',
        ]);
        $this->withToken($token)->deleteJson('/api/v1/cars/'.$car->id)
            ->assertStatus(409)
            ->assertJsonPath('code', 'VEHICLE_HAS_HISTORY');
        $this->assertDatabaseHas('vehicles', ['id' => $car->id]);

        $emptyCar = Vehicle::factory()->create(['user_id' => $user->id]);
        $this->withToken($token)->deleteJson('/api/v1/cars/'.$emptyCar->id)->assertNoContent();
        $this->assertDatabaseMissing('vehicles', ['id' => $emptyCar->id]);
    }

    public function test_profile_and_car_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
        $this->getJson('/api/v1/cars')->assertUnauthorized();
        $this->postJson('/api/v1/cars', [])->assertUnauthorized();
    }

    private function authenticatedUser(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Phone', ['mobile'], now()->addDays(30))->plainTextToken;

        return [$user, $token];
    }
}
