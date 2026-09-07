<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Services\SmsService;
use App\Services\Vpn\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function seedUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'کاربر',
            'phone' => '09121112233',
            'password' => 'Test@12345',
            'password_changed_at' => now(),
            'status' => 'active',
        ], $overrides));
    }

    protected function seedAdmin(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'مدیر',
            'phone' => '09120000000',
            'password' => 'Admin@1234',
            'password_changed_at' => now(),
            'status' => 'active',
            'is_admin' => true,
            'admin_role' => 'super',
        ], $overrides));
    }

    protected function seedPlan(): Plan
    {
        return Plan::create([
            'name' => 'پلن تست',
            'price_toman' => 90000,
            'volume_gb' => 30,
            'duration_days' => 30,
            'is_active' => true,
        ]);
    }

    public function test_admin_with_null_password_is_forced_to_change_it(): void
    {
        $admin = $this->seedAdmin(['password_changed_at' => null]);

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('profile.password'));
        $this->actingAs($admin)->get('/admin/setup')->assertRedirect(route('profile.password'));
        // صفحه تغییر رمز خودش باز می‌شود
        $this->actingAs($admin)->get('/profile/password')->assertOk();
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->seedUser();

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'Test@12345',
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ])->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh()->password_changed_at);
    }

    public function test_finance_admin_cannot_access_super_sections(): void
    {
        $finance = $this->seedAdmin(['phone' => '09120000001', 'admin_role' => 'finance']);

        $this->actingAs($finance)->get('/admin/payments')->assertOk();
        $this->actingAs($finance)->get('/admin/settings')->assertForbidden();
        $this->actingAs($finance)->get('/admin/plans')->assertForbidden();
        $this->actingAs($finance)->get('/admin/activity-logs')->assertForbidden();
    }

    public function test_support_admin_cannot_access_finance_sections(): void
    {
        $support = $this->seedAdmin(['phone' => '09120000002', 'admin_role' => 'support']);

        $this->actingAs($support)->get('/admin/tickets')->assertOk();
        $this->actingAs($support)->get('/admin/payments')->assertForbidden();
        $this->actingAs($support)->get('/admin/users')->assertOk();
    }

    public function test_payment_reject_records_activity_log(): void
    {
        $plan = $this->seedPlan();
        $user = $this->seedUser();
        $admin = $this->seedAdmin();

        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'volume_gb' => $plan->volume_gb,
            'duration_days' => $plan->duration_days,
            'price_toman' => $plan->price_toman,
            'status' => Order::STATUS_AWAITING_VERIFICATION,
            'paid_amount' => 90000,
            'bank_reference' => 'TRX-1',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/admin/payments/'.$order->id.'/reject', ['note' => 'تست'])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'payment_rejected',
            'subject_type' => 'Order',
            'subject_id' => $order->id,
        ]);
    }

    public function test_admin_orders_csv_export(): void
    {
        $admin = $this->seedAdmin();

        $response = $this->actingAs($admin)->get('/admin/orders?export=csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_setup_wizard_renders_for_super_admin(): void
    {
        $admin = $this->seedAdmin();

        $this->actingAs($admin)->get('/admin/setup')->assertOk();
        $this->actingAs($admin)->get('/admin/activity-logs')->assertOk();
    }

    public function test_online_payment_redirects_to_gateway(): void
    {
        Setting::set('zp_enabled', '1');
        Setting::set('zp_merchant_id', 'test-merchant');
        Setting::set('zp_sandbox', '1');

        Http::fake([
            'sandbox.zarinpal.com/*' => Http::response(['Status' => 100, 'Authority' => 'AUTH123'], 200),
        ]);

        $plan = $this->seedPlan();
        $user = $this->seedUser();

        $response = $this->actingAs($user)->post('/buy/'.$plan->id, ['payment_method' => 'online']);

        $response->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/AUTH123');

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertSame('online', $order->payment_method);
    }

    public function test_online_callback_cancelled_does_not_activate(): void
    {
        $plan = $this->seedPlan();
        $user = $this->seedUser();

        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'volume_gb' => $plan->volume_gb,
            'duration_days' => $plan->duration_days,
            'price_toman' => $plan->price_toman,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'online',
        ]);

        $this->actingAs($user)
            ->get('/payment/callback?order='.$order->id.'&Authority=AUTH123&Status=NOK')
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }

    public function test_online_callback_verifies_and_activates(): void
    {
        Setting::set('zp_enabled', '1');
        Setting::set('zp_merchant_id', 'test-merchant');
        Setting::set('zp_sandbox', '1');

        Http::fake([
            'sandbox.zarinpal.com/*' => Http::response(['Status' => 100, 'RefID' => 999888], 200),
        ]);

        $this->mock(ProvisioningService::class, function ($mock) {
            $mock->shouldReceive('provision')->once()->andReturnUsing(function (Order $order) {
                $order->update(['status' => Order::STATUS_ACTIVE, 'expires_at' => now()->addDays(30)]);
            });
        });

        $plan = $this->seedPlan();
        $user = $this->seedUser();

        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'volume_gb' => $plan->volume_gb,
            'duration_days' => $plan->duration_days,
            'price_toman' => $plan->price_toman,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => 'online',
        ]);

        $this->actingAs($user)
            ->get('/payment/callback?order='.$order->id.'&Authority=AUTH123&Status=OK')
            ->assertRedirect(route('orders.show', $order));

        $fresh = $order->fresh();
        $this->assertSame(Order::STATUS_ACTIVE, $fresh->status);
        $this->assertSame('ZP-999888', $fresh->bank_reference);
    }

    public function test_crypto_charge_creates_invoice_and_redirects(): void
    {
        Setting::set('np_enabled', '1');
        Setting::set('np_api_key', 'test-key');
        Setting::set('np_usd_rate_toman', 100000);

        Http::fake([
            'api.nowpayments.io/*' => Http::response(['id' => 'INV1', 'invoice_url' => 'https://nowpayments.io/inv/1'], 200),
        ]);

        $user = $this->seedUser();

        $response = $this->actingAs($user)->post('/wallet/charge-crypto', ['amount' => 100000]);

        $response->assertRedirect('https://nowpayments.io/inv/1');

        $deposit = $user->transactions()->where('type', 'deposit')->where('method', 'crypto')->first();
        $this->assertNotNull($deposit);
    }

    public function test_sms_is_noop_when_disabled(): void
    {
        $this->assertFalse(SmsService::isEnabled());
        $this->assertFalse(SmsService::send('09121112233', 'test'));
    }
}
