<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Idempotent برای اجرای در هر استارت Railway: اگر دیتابیس قبلاً کاربر دارد
        // (مثلاً بعد از بازیابی state)، سیدر هیچ کاری نمی‌کند تا داده‌های زنده دست‌نخورده بمانند.
        if (User::query()->exists()) {
            return;
        }

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

        // تنظیمات پیش‌فرض — فقط وقتی کلید وجود ندارد (هرگز تنظیمات تغییر‌یافته ادمین بازنویسی نمی‌شود)
        $defaults = [
            'site_name' => 'TipStop Network',
            'support_telegram' => '',
            'sub_base_url' => '',
            'cards' => '[]',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings.all');

        // پلن‌های نمونه (قیمت‌ها را از پنل مدیریت ویرایش کنید) — فقط اگر هیچ پلنی وجود ندارد
        if (! Plan::query()->exists()) {
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
}
