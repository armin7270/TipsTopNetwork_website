<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function seedSuperAdmin(): User
    {
        return User::create([
            'name' => 'مدیرکل',
            'phone' => '09120000000',
            'password' => 'Admin@1234',
            'password_changed_at' => now(),
            'status' => 'active',
            'is_admin' => true,
            'admin_role' => 'super',
        ]);
    }

    public function test_admin_can_save_settings(): void
    {
        $admin = $this->seedSuperAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'پنل تستی من',
                'support_telegram' => 'my_support',
                'card_bank' => ['ملت'],
                'card_holder' => ['علی رضایی'],
                'card_number' => ['6104-1234-5678-9012'],
                'tg_bot_enabled' => '1',
                'tg_bot_token' => '123456:ABC-DEF_test',
                'tg_admin_chat_id' => '111222333',
                'wallet_min_deposit' => 20000,
                'referral_welcome_amount' => 10000,
                'referral_reward_amount' => 20000,
                'referral_min_referrer_balance' => 0,
                'trial_volume_mb' => 500,
                'trial_duration_hours' => 24,
                'trial_limit_per_user' => 1,
                'np_usd_rate_toman' => 100000,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('پنل تستی من', Setting::get('site_name'));
        $this->assertSame('my_support', Setting::get('support_telegram'));
        $this->assertSame('123456:ABC-DEF_test', Setting::get('tg_bot_token'));
        $this->assertSame('1', Setting::get('tg_bot_enabled'));

        $cards = Setting::getJson('cards', []);
        $this->assertCount(1, $cards);
        $this->assertSame('6104123456789012', $cards[0]['number']);
        $this->assertSame('ملت', $cards[0]['bank']);

        // بعد از پاک شدن کش هم درست باشد
        Cache::forget('settings.all');
        $this->assertSame('پنل تستی من', Setting::get('site_name'));
    }

    public function test_settings_page_shows_saved_values(): void
    {
        $admin = $this->seedSuperAdmin();

        Setting::set('site_name', 'نام ذخیره شده');
        Setting::set('cards', [['bank' => 'سامان', 'holder' => 'رضا', 'number' => '6219861012345678']]);

        $response = $this->actingAs($admin)->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('نام ذخیره شده');
        $response->assertSee('6219861012345678');
    }

    public function test_settings_ephemeral_db_warning_shows_on_railway(): void
    {
        $admin = $this->seedSuperAdmin();

        putenv('RAILWAY_ENVIRONMENT=production');

        try {
            $response = $this->actingAs($admin)->get(route('admin.settings.edit'));
            $response->assertOk();
            $response->assertSee('دیتابیس شما موقتی است');
        } finally {
            putenv('RAILWAY_ENVIRONMENT');
        }
    }
}
