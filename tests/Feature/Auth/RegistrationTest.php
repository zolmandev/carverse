<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        config(['services.hashback.key' => 'test-key']);
        Http::fake([
            'www.hashback.co.ke/*' => Http::response(['success' => true, 'sms' => ['id' => 'test-message']], 200),
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0712 345 678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('enthusiast', $user->role);
        $this->assertSame('+254712345678', $user->phone);
        $this->assertNull($user->phone_verified_at);
        $this->assertNotNull($user->phone_verification_code_hash);
        $response->assertRedirect(route('phone.verification.notice', absolute: false));

        Http::assertSent(fn (Request $request) => $request->url() === 'https://www.hashback.co.ke/api/sms/send'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['to'] === '+254712345678');
    }

    public function test_registration_is_not_kept_when_sms_delivery_fails()
    {
        config(['services.hashback.key' => 'test-key']);
        Http::fake(['www.hashback.co.ke/*' => Http::response(['success' => false, 'code' => 'INSUFFICIENT_FUNDS'], 402)]);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0712345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
