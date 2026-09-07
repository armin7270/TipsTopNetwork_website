<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // کاربر مدیر (اطلاعات ورود را بعد از اولین ورود از منوی مدیریت تغییر دهید)
        // password_changed_at خالی = اجبار تعویض رمز در اولین ورود
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'مدیر سایت',
                'phone' => env('ADMIN_PHONE', '09120000000'),
                'password' => env('ADMIN_PASSWORD', 'admin'),
                'password_changed_at' => null,
                'is_admin' => true,
                'admin_role' => 'super',
                'status' => 'active',
            ]
        );

        // تنظیمات پیش‌فرض
        Setting::set('site_name', 'TipStop Network');
        Setting::set('support_telegram', '');
        Setting::set('sub_base_url', '');
        Setting::set('cards', []);

        // پلن‌های نمونه (قیمت‌ها را از پنل مدیریت ویرایش کنید)
        $plans = [
            ['name' => 'پلن یک‌ماهه', 'price_toman' => 90000, 'volume_gb' => 30, 'duration_days' => 30, 'sort_order' => 1],
            ['name' => 'پلن دوماهه', 'price_toman' => 160000, 'volume_gb' => 60, 'duration_days' => 60, 'sort_order' => 2],
            ['name' => 'پلن سه‌ماهه', 'price_toman' => 220000, 'volume_gb' => 100, 'duration_days' => 90, 'sort_order' => 3],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan + ['description' => null, 'is_active' => true]);
        }
    }
}
