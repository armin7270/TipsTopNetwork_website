<?php

use App\Models\Order;
use App\Models\Server;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\Telegram\TelegramClient;
use App\Services\Vpn\ProvisioningService;
use App\Services\Xui\XuiService;
use Illuminate\Support\Facades\Schedule;

// غیرفعال/حذف خودکار سفارش‌های منقضی‌شده از پنل
Schedule::call(function () {
    app(ProvisioningService::class)->disableExpiredOrders();
})->everyFiveMinutes()->name('expire-orders')->withoutOverlapping();

// همگام‌سازی ترافیک مصرفی سفارش‌های فعال
Schedule::call(function () {
    app(ProvisioningService::class)->syncActiveOrdersTraffic();
})->everyFifteenMinutes()->name('sync-traffic')->withoutOverlapping();

// هلث‌چک ساعتی سرورها + اطلاع به مدیر در صورت قطعی
Schedule::call(function () {
    foreach (Server::query()->where('is_active', true)->get() as $server) {
        try {
            $result = (new XuiService($server))->testConnection();
            $ok = (bool) ($result['ok'] ?? false);
            $error = $ok ? null : mb_substr((string) ($result['message'] ?? 'unknown'), 0, 400);
        } catch (Throwable $e) {
            $ok = false;
            $error = mb_substr($e->getMessage(), 0, 400);
        }

        $wasOk = $server->last_check_ok;

        $server->update([
            'last_check_at' => now(),
            'last_check_ok' => $ok,
            'last_check_error' => $error,
        ]);

        // فقط هنگام تغییر وضعیت «سالم → قطع» اطلاع بده (جلوگیری از اسپم)
        if ($wasOk !== false && $ok === false) {
            NotificationService::notifyAdmins(
                'server_down',
                __('⚠️ سرور :name از دسترس خارج شد', ['name' => $server->name]),
                $error ?: __('اتصال به API پنل برقرار نشد.'),
                route('admin.inbounds.index'),
            );
        }
    }
})->hourly()->name('server-health-check')->withoutOverlapping();

// بکاپ روزانه دیتابیس
Schedule::command('app:backup-database')->dailyAt('03:30')->name('db-backup')->withoutOverlapping();

// اطلاع‌رسانی انقضای نزدیک سرویس‌ها (۳ روز قبل — مشابه vPanel)
Schedule::call(function () {
    $orders = Order::query()
        ->with('user')
        ->where('status', Order::STATUS_ACTIVE)
        ->whereBetween('expires_at', [now()->startOfDay(), now()->addDays(3)->endOfDay()])
        ->whereDoesntHave('user.notifications', function ($q) {
            $q->where('type', 'order_expiring')
                ->where('created_at', '>=', now()->subDays(2));
        })
        ->get();

    foreach ($orders as $order) {
        NotificationService::send(
            $order->user,
            'order_expiring',
            __('سرویس شما به‌زودی منقضی می‌شود ⏳'),
            __('پلن «:plan» در :days روز منقضی می‌شود. همین حالا تمدید کنید.', [
                'plan' => $order->plan_name,
                'days' => $order->daysLeft(),
            ]),
            route('orders.show', $order),
        );

        // اطلاع تلگرامی در صورت اتصال ربات
        if ($order->user->telegram_chat_id && Setting::get('tg_bot_enabled') === '1') {
            try {
                app(TelegramClient::class)->sendMessage(
                    $order->user->telegram_chat_id,
                    '⏳ <b>سرویس شما به‌زودی منقضی می‌شود!</b>'."\n".
                    'پلن «'.e($order->plan_name).'» در '.$order->daysLeft().' روز منقضی می‌شود. برای تمدید از ربات استفاده کنید.'
                );
            } catch (Throwable) {
            }
        } elseif (! $order->user->telegram_chat_id) {
            // پیامک یادآوری برای کاربرانی که تلگرام ندارند
            SmsService::send(
                $order->user->phone,
                __(':site: پلن :plan شما :days روز دیگر منقضی می‌شود. برای تمدید وارد سایت شوید.', [
                    'site' => Setting::get('site_name', 'TipStop'),
                    'plan' => $order->plan_name,
                    'days' => $order->daysLeft(),
                ])
            );
        }
    }
})->dailyAt('10:00')->name('expiry-reminders')->withoutOverlapping();
