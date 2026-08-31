<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyFlowTest extends TestCase
{
    use RefreshDatabase;

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

    protected function seedUser(): User
    {
        return User::create([
            'name' => 'خریدار',
            'phone' => '09121112233',
            'password' => 'Test@12345',
            'status' => 'active',
        ]);
    }

    protected function seedAdmin(): User
    {
        return User::create([
            'name' => 'مدیر',
            'phone' => '09120000000',
            'password' => 'Admin@1234',
            'status' => 'active',
            'is_admin' => true,
        ]);
    }

    public function test_user_can_buy_plan_and_submit_receipt(): void
    {
        $plan = $this->seedPlan();
        $user = $this->seedUser();

        $this->actingAs($user)->get('/buy/'.$plan->id)->assertOk();

        $this->actingAs($user)->post('/buy/'.$plan->id, ['payment_method' => 'card'])
            ->assertRedirect();

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);

        $this->actingAs($user)->post('/orders/'.$order->id.'/receipt', [
            'paid_amount' => 90000,
            'bank_reference' => 'TRX-TEST-1',
            'paid_at' => now()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertSame(Order::STATUS_AWAITING_VERIFICATION, $order->fresh()->status);
    }

    public function test_admin_rejects_payment_with_note(): void
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
            'bank_reference' => 'TRX-TEST-2',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/admin/payments/'.$order->id.'/reject', ['note' => 'مبلغ ناقص'])
            ->assertRedirect();

        $this->assertSame(Order::STATUS_REJECTED, $order->fresh()->status);
        $this->assertSame('مبلغ ناقص', $order->fresh()->admin_note);
    }

    public function test_approve_without_inbounds_fails_gracefully(): void
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
            'bank_reference' => 'TRX-TEST-3',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/admin/payments/'.$order->id.'/approve')
            ->assertRedirect();

        // بدون اینباند، سفارش نباید فعال شود
        $this->assertSame(Order::STATUS_AWAITING_VERIFICATION, $order->fresh()->status);
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = $this->seedUser();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = $this->seedAdmin();

        $this->actingAs($admin)->get('/admin')->assertStatus(200);
    }
}
