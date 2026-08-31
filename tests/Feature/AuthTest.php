<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_reach_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'تست کاربر',
            'phone' => '09121112233',
            'password' => 'Test@12345',
            'password_confirmation' => 'Test@12345',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('phone', '09121112233')->first();
        $this->assertNotNull($user);
        $this->assertNotEmpty($user->subscription_code);

        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    }

    public function test_user_can_login_with_phone_and_password(): void
    {
        $user = User::create([
            'name' => 'کاربر',
            'phone' => '09123334455',
            'password' => 'Test@12345',
            'status' => 'active',
        ]);

        $this->post('/login', [
            'phone' => '09123334455',
            'password' => 'Test@12345',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_blocked_user_cannot_login(): void
    {
        User::create([
            'name' => 'مسدود',
            'phone' => '09120009999',
            'password' => 'Test@12345',
            'status' => 'blocked',
        ]);

        $this->post('/login', [
            'phone' => '09120009999',
            'password' => 'Test@12345',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }
}
