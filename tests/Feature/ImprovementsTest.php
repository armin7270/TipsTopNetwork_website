<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImprovementsTest extends TestCase
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

    // ---- بازیابی رمز عبور با OTP پیامکی ----

    public function test_forgot_password_sends_otp_and_reset_works(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_api_key', 'test-key');
        Setting::set('sms_sender', '10004346');

        Http::fake([
            'api.kavenegar.com/*' => Http::response(['return' => ['status' => 200]], 200),
        ]);

        $this->seedUser();

        $capturedMessage = '';

        // درخواست کد بازیابی
        $response = $this->post('/forgot-password', ['phone' => '09121112233']);

        Http::assertSent(function ($request) use (&$capturedMessage) {
            $capturedMessage = (string) ($request['message'] ?? '');

            return true;
        });

        preg_match('/(\d{6})/', $capturedMessage, $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'OTP code must be present in the sent SMS');
        $code = $matches[1];

        $response->assertRedirect(route('password.reset'));

        // صفحه تعیین رمز جدید باز می‌شود
        $this->get('/reset-password')->assertOk();

        // کد اشتباه رد می‌شود
        $this->post('/reset-password', [
            'code' => '000000',
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ])->assertSessionHasErrors('code');

        // کد درست، رمز را عوض می‌کند
        $this->post('/reset-password', [
            'code' => $code,
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ])->assertRedirect(route('login'));

        $user = User::where('phone', '09121112233')->first();

        $this->assertTrue(password_verify('NewPass@123', $user->password));
        $this->assertNotNull($user->password_changed_at);
    }

    public function test_forgot_password_is_generic_for_unknown_phone(): void
    {
        Http::fake();

        // شماره ثبت‌نشده — نباید پیامکی برود و پاسخ عمومی باشد
        $this->post('/forgot-password', ['phone' => '09350001122'])
            ->assertRedirect()
            ->assertSessionHas('info');

        Http::assertNothingSent();
    }

    public function test_forgot_password_fails_without_sms_gateway(): void
    {
        // پیامک فعال نیست
        $this->seedUser();

        $this->post('/forgot-password', ['phone' => '09121112233'])
            ->assertSessionHasErrors('phone');
    }

    // ---- وبهوک NOWPayments (fail-closed) ----

    protected function signPayload(array $payload, string $secret): string
    {
        ksort($payload);

        return hash_hmac('sha512', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
    }

    public function test_nowpayments_webhook_rejected_without_secret(): void
    {
        Setting::set('nowpayments_ipn_secret', '');

        $this->postJson('/webhooks/nowpayments', ['payment_status' => 'finished', 'order_id' => 1])
            ->assertStatus(403);
    }

    public function test_nowpayments_webhook_rejected_with_invalid_signature(): void
    {
        Setting::set('nowpayments_ipn_secret', 'secret-123');

        $user = $this->seedUser();
        $tx = app(WalletService::class)->createDeposit($user, 50000, 'crypto');

        $payload = ['order_id' => $tx->id, 'payment_status' => 'finished'];

        $this->postJson('/webhooks/nowpayments', $payload, ['x-nowpayments-sig' => 'bad-signature'])
            ->assertStatus(403);

        $this->assertSame(Transaction::STATUS_PENDING, $tx->fresh()->status);
    }

    public function test_nowpayments_webhook_confirms_deposit_with_valid_signature(): void
    {
        Setting::set('nowpayments_ipn_secret', 'secret-123');

        $user = $this->seedUser();
        $tx = app(WalletService::class)->createDeposit($user, 50000, 'crypto');

        $payload = ['order_id' => $tx->id, 'payment_status' => 'finished'];

        $this->postJson('/webhooks/nowpayments', $payload, [
            'x-nowpayments-sig' => $this->signPayload($payload, 'secret-123'),
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertSame(Transaction::STATUS_COMPLETED, $tx->fresh()->status);
        $this->assertSame(50000, $user->fresh()->balance);
    }

    // ---- نقاط دیپلوی: هدر X-Deploy-Key ----

    public function test_deploy_endpoints_accept_key_via_header(): void
    {
        putenv('DEPLOY_KEY=test-secret-key');

        try {
            $this->get('/deploy/cron', ['X-Deploy-Key' => 'test-secret-key'])
                ->assertOk()
                ->assertJsonPath('ok', true);
        } finally {
            putenv('DEPLOY_KEY');
        }
    }

    // ---- آپلود فایل رسید سفارش ----

    public function test_order_receipt_accepts_image_upload(): void
    {
        Storage::fake('local');

        $user = $this->seedUser();
        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => null,
            'plan_name' => 'پلن تست',
            'volume_gb' => 10,
            'duration_days' => 30,
            'price_toman' => 90000,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);

        $this->actingAs($user)
            ->post('/orders/'.$order->id.'/receipt', [
                'paid_amount' => 90000,
                'bank_reference' => 'TRX-UP-1',
                'paid_at' => now()->format('Y-m-d\TH:i'),
                'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect();

        $fresh = $order->fresh();

        $this->assertSame(Order::STATUS_AWAITING_VERIFICATION, $fresh->status);
        $this->assertNotNull($fresh->receipt_path);
        Storage::disk('local')->assertExists($fresh->receipt_path);
    }

    public function test_order_receipt_rejects_invalid_file_type(): void
    {
        $user = $this->seedUser();
        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => null,
            'plan_name' => 'پلن تست',
            'volume_gb' => 10,
            'duration_days' => 30,
            'price_toman' => 90000,
            'status' => Order::STATUS_PENDING_PAYMENT,
        ]);

        $this->actingAs($user)
            ->from('/orders/'.$order->id)
            ->post('/orders/'.$order->id.'/receipt', [
                'paid_amount' => 90000,
                'bank_reference' => 'TRX-UP-2',
                'paid_at' => now()->format('Y-m-d\TH:i'),
                'receipt' => UploadedFile::fake()->create('malware.exe', 100),
            ])
            ->assertSessionHasErrors('receipt');

        $this->assertNull($order->fresh()->receipt_path);
    }
}
