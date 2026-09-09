<?php

namespace App\Services\Vpn;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;

class SubscriptionService
{
    /**
     * محتوای لینک اشتراک کاربر (base64 لیست کانفیگ‌ها)
     * برای سفارش‌های Marzban، خود لینک اشتراک پنل برگردانده می‌شود
     */
    public function contentFor(User $user): string
    {
        $orders = $user->orders()
            ->with('inbounds.server')
            ->where('status', Order::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        $lines = [];

        foreach ($orders as $order) {
            if (empty($order->xui_uuid) || empty($order->xui_email)) {
                continue;
            }

            // سفارش Marzban: لینک اشتراک پنل را برمی‌گردانیم
            if (! empty($order->sub_url)) {
                $lines[] = $order->sub_url;

                continue;
            }

            foreach ($order->inbounds as $inbound) {
                if (! $inbound->is_active || ! $inbound->server?->is_active) {
                    continue;
                }

                $label = trim(($inbound->server?->name ?: 'سرور').' | '.($inbound->remark ?: $order->plan_name));

                $uri = ConfigBuilder::build($inbound, $order->xui_uuid, $order->xui_email, $label);

                if ($uri) {
                    $lines[] = $uri;
                }
            }
        }

        if (empty($lines)) {
            throw new \RuntimeException('هیچ کانفیگ فعالی یافت نشد.');
        }

        return base64_encode(implode("\n", $lines));
    }

    /**
     * لینک اشتراک کاربر
     */
    public function urlFor(User $user): string
    {
        $base = rtrim((string) Setting::get('sub_base_url', ''), '/');

        if ($base === '') {
            $base = rtrim(config('app.url'), '/');
        }

        return $base.'/sub/'.$user->subscription_code;
    }

    /**
     * لیست کانفیگ‌های فعال کاربر (برای نمایش در داشبورد)
     */
    public function configsFor(User $user): array
    {
        $orders = $user->orders()
            ->with('inbounds.server')
            ->where('status', Order::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        $configs = [];

        foreach ($orders as $order) {
            if (empty($order->xui_uuid) || empty($order->xui_email)) {
                continue;
            }

            // سفارش Marzban: فقط لینک اشتراک
            if (! empty($order->sub_url)) {
                $configs[] = ['label' => '🔗 Marzban — '.$order->plan_name, 'uri' => $order->sub_url];

                continue;
            }

            foreach ($order->inbounds as $inbound) {
                if (! $inbound->is_active || ! $inbound->server?->is_active) {
                    continue;
                }

                $label = trim(($inbound->server?->name ?: 'سرور').' | '.($inbound->remark ?: $order->plan_name));

                $uri = ConfigBuilder::build($inbound, $order->xui_uuid, $order->xui_email, $label);

                if ($uri) {
                    $configs[] = ['label' => $label, 'uri' => $uri];
                }
            }
        }

        return $configs;
    }
}
