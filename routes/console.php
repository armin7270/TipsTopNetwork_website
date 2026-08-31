<?php

use App\Models\Order;
use App\Models\Setting;
use App\Services\NotificationService;
use App\Services\Telegram\TelegramClient;
use App\Services\Vpn\ProvisioningService;
use Illuminate\Support\Facades\Schedule;

// غیرفعال/حذف خودکار سفارش‌های منقضی‌شده از پنل
Schedule::call(function () {
    app(ProvisioningService::class)->disableExpiredOrders();
})->everyFiveMinutes()->name('expire-orders')->withoutOverlapping();

// همگام‌سازی ترافیک مصرفی سفارش‌های فعال
Schedule::call(function () {
    app(ProvisioningService::class)->syncActiveOrdersTraffic();
})->everyFifteenMinutes()->name('sync-traffic')->withoutOverlapping();

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
        }
    }
})->dailyAt('10:00')->name('expiry-reminders')->withoutOverlapping();
