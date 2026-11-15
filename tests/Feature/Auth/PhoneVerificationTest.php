<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-phone')->assertOk();
    }

    public function test_valid_code_verifies_phone_and_unlocks_app(): void
    {
        $user = User::factory()->unverified()->create([
            'phone_verification_code_hash' => Hash::make('123456'),
            'phone_verification_expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)
            ->post('/verify-phone', ['code' => '123456'])
            ->assertRedirect(route('home', absolute: false));

        $this->assertNotNull($user->fresh()->phone_verified_at);
        $this->actingAs($user->fresh())->get('/app')->assertOk();
    }

    public function test_invalid_code_is_rejected_and_attempt_is_recorded(): void
    {
        $user = User::factory()->unverified()->create([
            'phone_verification_code_hash' => Hash::make('123456'),
            'phone_verification_expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)
            ->post('/verify-phone', ['code' => '654321'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, $user->fresh()->phone_verification_attempts);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_unverified_user_is_redirected_to_phone_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/app')
            ->assertRedirect(route('phone.verification.notice', absolute: false));
    }

    public function test_user_can_request_a_new_code(): void
    {
        config(['services.hashback.key' => 'test-key']);
        Http::fake(['www.hashback.co.ke/*' => Http::response(['success' => true, 'sms' => ['id' => 'test-message']], 200)]);
        $user = User::factory()->unverified()->create(['phone_verification_sent_at' => null]);

        $this->actingAs($user)
            ->post('/phone/verification-notification')
            ->assertSessionHas('status', 'verification-code-sent');

        $this->assertNotNull($user->fresh()->phone_verification_code_hash);
        Http::assertSentCount(1);
    }

    public function test_legacy_user_without_phone_can_add_one_and_receive_a_code(): void
    {
        config(['services.hashback.key' => 'test-key']);
        Http::fake(['www.hashback.co.ke/*' => Http::response(['success' => true], 200)]);
        $user = User::factory()->unverified()->create(['phone' => null]);

        $this->actingAs($user)
            ->post('/verify-phone/number', ['phone' => '0712 345 678'])
            ->assertSessionHas('status', 'verification-code-sent');

        $user->refresh();
        $this->assertSame('+254712345678', $user->phone);
        $this->assertNotNull($user->phone_verification_code_hash);
    }
}
