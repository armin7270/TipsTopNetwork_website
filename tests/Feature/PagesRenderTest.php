<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function seedUser(): User
    {
        return User::create([
            'name' => 'کاربر',
            'phone' => '09121112233',
            'password' => 'Test@12345',
            'status' => 'active',
        ]);
    }

    protected function seedAdmin(): User
    {
        return User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'مدیر',
                'phone' => '09120000000',
                'password' => 'admin',
                'status' => 'active',
                'is_admin' => true,
            ]
        );
    }

    public function test_home_and_auth_pages_render(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_admin_can_login_with_username_admin(): void
    {
        $admin = $this->seedAdmin();

        $this->post('/login', ['phone' => 'admin', 'password' => 'admin'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_authed_user_pages_render(): void
    {
        $user = $this->seedUser();

        $pages = ['/dashboard', '/orders', '/wallet', '/tickets', '/tickets/create', '/referrals', '/notifications', '/trial'];

        foreach ($pages as $page) {
            $this->actingAs($user)->get($page)->assertOk();
        }
    }

    public function test_admin_pages_render(): void
    {
        $this->seedAdmin();

        $pages = [
            '/admin', '/admin/payments', '/admin/wallet-deposits', '/admin/orders',
            '/admin/tickets', '/admin/broadcast', '/admin/users', '/admin/plans',
            '/admin/inbounds', '/admin/settings',
        ];

        foreach ($pages as $page) {
            $this->actingAs($this->seedAdmin())->get($page)->assertOk();
        }
    }

    public function test_buy_confirm_page_renders(): void
    {
        $plan = Plan::create([
            'name' => 'پلن تست',
            'price_toman' => 90000,
            'volume_gb' => 30,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $user = $this->seedUser();

        $this->actingAs($user)->get('/buy/'.$plan->id)->assertOk();
    }

    public function test_detail_pages_render(): void
    {
        $plan = Plan::create([
            'name' => 'پلن تست',
            'price_toman' => 90000,
            'volume_gb' => 30,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $user = $this->seedUser();

        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'volume_gb' => $plan->volume_gb,
            'duration_days' => $plan->duration_days,
            'price_toman' => $plan->price_toman,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'subject' => 'تست تیکت',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $deposit = $user->transactions()->create([
            'type' => 'deposit',
            'amount' => 50000,
            'status' => 'awaiting_verification',
            'method' => 'card',
        ]);

        $this->actingAs($user)->get('/tickets/'.$ticket->id)->assertOk();
        $this->actingAs($user)->get('/wallet/deposit/'.$deposit->id)->assertOk();
        $this->actingAs($this->seedAdmin())->get('/admin/orders/'.$order->id)->assertOk();
        $this->actingAs($this->seedAdmin())->get('/admin/tickets/'.$ticket->id)->assertOk();
    }

    public function test_subscription_endpoint_returns_empty_body_for_user_without_configs(): void
    {
        $user = $this->seedUser();

        $this->get('/sub/'.$user->subscription_code)
            ->assertOk()
            ->assertHeader('Profile-Update-Interval', '6');

        $this->assertSame('', $this->get('/sub/'.$user->subscription_code)->getContent());
    }

    public function test_order_show_page_renders(): void
    {
        $plan = Plan::create([
            'name' => 'پلن تست',
            'price_toman' => 90000,
            'volume_gb' => 30,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $user = $this->seedUser();

        Setting::set('cards', [['bank' => 'ملت', 'holder' => 'مدیر', 'number' => '1234-5678-9012-3456']]);

        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'volume_gb' => $plan->volume_gb,
            'duration_days' => $plan->duration_days,
            'price_toman' => $plan->price_toman,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);

        $this->actingAs($user)->get('/orders/'.$order->id)->assertOk();
    }
}
