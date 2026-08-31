<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_success_and_shows_plans(): void
    {
        Plan::create([
            'name' => 'پلن تست',
            'price_toman' => 90000,
            'volume_gb' => 30,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('پلن تست');
        $response->assertSee('پلن‌های اشتراک');
    }

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_login_and_register_pages_render(): void
    {
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
    }
}
