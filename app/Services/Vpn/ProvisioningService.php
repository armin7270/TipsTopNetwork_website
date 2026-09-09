<?php

namespace App\Services\Vpn;

use App\Models\Order;
use App\Services\Marzban\MarzbanService;
use App\Services\Xui\XuiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProvisioningService
{
    protected array $xuiCache = [];

    protected array $marzbanCache = [];

    /**
     * ساخت کانفیگ‌های سفارش جدید در پنل (بعد از تایید پرداخت)
     */
    public function provision(Order $order): void
    {
        $inbounds = $order->inbounds()->where('is_active', true)->with('server')->get();

        if ($inbounds->isEmpty()) {
            throw new \RuntimeException(__('هیچ اینباند فعالی به این پلن متصل نیست. از بخش «سرورها و اینباندها» اینباند به پلن اضافه کنید.'));
        }

        $order->xui_uuid = $order->xui_uuid ?: (string) Str::uuid();
        $order->xui_email = $order->xui_email ?: $this->clientEmail($order);

        $expiryMs = now()->addDays($order->duration_days)->getTimestampMs();
        $totalBytes = $this->volumeToBytes($order->volume_gb);

        [$ok, $errors] = $this->forEachInbound($inbounds, function (XuiService $xui, $inbound) use ($order, $expiryMs, $totalBytes) {
            $xui->addClient($inbound, $this->clientPayload($order, $inbound, $expiryMs, $totalBytes));
        });

        if ($ok === 0) {
            throw new \RuntimeException(__('ساخت کانفیگ در پنل ناموفق بود: :msg', ['msg' => implode(' | ', $errors)]));
        }

        $this->afterProvision($order, $errors);

        $order->fill([
            'status' => Order::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => now()->addDays($order->duration_days),
            'total_bytes' => $totalBytes,
            'verified_at' => now(),
            'admin_note' => $errors ? 'با خطا فعال شد: '.implode(' | ', $errors) : null,
        ]);
        $order->save();

        // سفارش قبلی که تمدید شده، منقضی می‌شود
        if ($order->renewal_of) {
            Order::query()
                ->where('id', $order->renewal_of)
                ->where('status', Order::STATUS_ACTIVE)
                ->update(['status' => Order::STATUS_EXPIRED, 'admin_note' => 'تمدید شد (سفارش #'.$order->id.')']);
        }
    }

    /**
     * تمدید سفارش: تمدید/افزایش حجم کلاینت‌های قبلی در پنل
     */
    public function extend(Order $order): void
    {
        $previous = $order->renewal_of ? Order::find($order->renewal_of) : null;

        $order->xui_email = $previous?->xui_email ?: $this->clientEmail($order);
        $order->xui_uuid = $previous?->xui_uuid ?: (string) Str::uuid();

        $remainingBytes = max(0, ($previous?->total_bytes ?? 0) - ($previous?->used_bytes ?? 0));
        $newTotalBytes = $this->volumeToBytes($order->volume_gb);
        if ($newTotalBytes > 0) {
            $newTotalBytes += $remainingBytes;
        }

        $start = now();
        if ($previous?->expires_at && $previous->expires_at->isFuture()) {
            $start = $previous->expires_at->copy();
        }
        $expiresAt = $start->copy()->addDays($order->duration_days);
        $expiryMs = $expiresAt->getTimestampMs();

        $inbounds = $order->inbounds()->where('is_active', true)->with('server')->get();

        if ($inbounds->isEmpty()) {
            throw new \RuntimeException(__('هیچ اینباند فعالی به این پلن متصل نیست.'));
        }

        [$ok, $errors] = $this->forEachInbound($inbounds, function (XuiService $xui, $inbound) use ($order, $expiryMs, $newTotalBytes) {
            try {
                $xui->updateClient($inbound, $order->xui_uuid, $this->clientPayload($order, $inbound, $expiryMs, $newTotalBytes));
            } catch (\Throwable $e) {
                // کلاینت ممکن است در این اینباند وجود نداشته باشد؛ دوباره می‌سازیم
                $xui->addClient($inbound, $this->clientPayload($order, $inbound, $expiryMs, $newTotalBytes));
            }
        });

        if ($ok === 0) {
            throw new \RuntimeException(__('تمدید در پنل ناموفق بود: :msg', ['msg' => implode(' | ', $errors)]));
        }

        $this->afterProvision($order, $errors);

        $order->fill([
            'status' => Order::STATUS_ACTIVE,
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'total_bytes' => $newTotalBytes,
            'verified_at' => now(),
            'admin_note' => $errors ? 'با خطا فعال شد: '.implode(' | ', $errors) : null,
        ]);
        $order->save();

        if ($previous && $previous->status === Order::STATUS_ACTIVE) {
            $previous->update(['status' => Order::STATUS_EXPIRED, 'admin_note' => 'تمدید شد (سفارش #'.$order->id.')']);
        }
    }

    /**
     * غیرفعال/حذف کانفیگ‌های سفارش از پنل (در انقضا یا لغو)
     */
    public function disable(Order $order): void
    {
        if (empty($order->xui_uuid)) {
            return;
        }

        $inbounds = $order->inbounds()->with('server')->get();

        $this->forEachInbound($inbounds, function (XuiService $xui, $inbound) use ($order) {
            try {
                $xui->deleteClient($order->xui_uuid);
            } catch (\Throwable $e) {
                Log::warning('xui deleteClient failed', ['order' => $order->id, 'inbound' => $inbound->id, 'error' => $e->getMessage()]);
            }
        });
    }

    /**
     * همگام‌سازی ترافیک مصرفی سفارش فعال با پنل
     */
    public function syncTraffic(Order $order): void
    {
        if (empty($order->xui_email) || ! $order->isActive()) {
            return;
        }

        $inbound = $order->inbounds()->with('server')->first();
        if (! $inbound?->server) {
            return;
        }

        $traffic = $this->xui($inbound)->clientTraffic($order->xui_email);
        if ($traffic) {
            $order->used_bytes = (int) (($traffic['up'] ?? 0) + ($traffic['down'] ?? 0));
            if ((int) ($traffic['total'] ?? 0) > 0) {
                $order->total_bytes = (int) $traffic['total'];
            }
            $order->last_sync_at = now();
            $order->save();
        }
    }

    /**
     * حذف خودکار سفارش‌های منقضی‌شده از پنل
     */
    public function disableExpiredOrders(): int
    {
        $orders = Order::query()
            ->where('status', Order::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            $this->disable($order);
            $order->update(['status' => Order::STATUS_EXPIRED, 'admin_note' => 'انقضای خودکار']);
            $count++;
        }

        return $count;
    }

    /**
     * به‌روزرسانی مصرف همه سفارش‌های فعال
     */
    public function syncActiveOrdersTraffic(): int
    {
        $orders = Order::query()->where('status', Order::STATUS_ACTIVE)->whereNotNull('xui_email')->get();

        $count = 0;
        foreach ($orders as $order) {
            try {
                $this->syncTraffic($order);
                $count++;
            } catch (\Throwable $e) {
                Log::warning('traffic sync failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    protected function clientEmail(Order $order): string
    {
        return 'tsn-u'.$order->user_id.'o'.$order->id;
    }

    /**
     * عملیات پس از ساخت/تمدید در پنل — درایور Marzban کاربر را آنجا می‌سازد
     * و لینک اشتراک را ذخیره می‌کند (برای 3x-ui خنثی است)
     */
    protected function afterProvision(Order $order, array $errors = []): void
    {
        $server = $order->inbounds()->where('is_active', true)->with('server')->first()?->server;

        if (! $server || $server->panel_type !== 'marzban') {
            return;
        }

        $expiryTs = (int) ($order->expires_at?->timestamp ?? now()->addDays($order->duration_days)->timestamp);
        $totalBytes = $this->volumeToBytes($order->volume_gb);

        try {
            $marzban = $this->marzban($server);
            $created = $marzban->createUser($order->xui_email, $totalBytes, $expiryTs);

            $order->sub_url = $created['sub_url'] ?: $order->sub_url;
        } catch (\Throwable $e) {
            // کلاینت ممکن است قبلاً ساخته شده باشد — تمدید/به‌روزرسانی می‌کنیم
            try {
                $marzban = $this->marzban($server);
                $marzban->updateUser($order->xui_email, $totalBytes, $expiryTs);
                $order->sub_url = $order->sub_url ?: '';
            } catch (\Throwable $e2) {
                Log::error('marzban provisioning failed', ['order' => $order->id, 'error' => $e2->getMessage()]);
                $errors[] = 'Marzban: '.$e2->getMessage();
            }
        }

        $order->save();
    }

    protected function volumeToBytes(float $volumeGb): int
    {
        return $volumeGb > 0 ? (int) round($volumeGb * 1024 ** 3) : 0;
    }

    protected function clientPayload(Order $order, $inbound, int $expiryMs, int $totalBytes): array
    {
        return [
            'id' => $order->xui_uuid,
            'flow' => ($inbound->protocol === 'vless' && ConfigBuilder::isReality($inbound)) ? 'xtls-rprx-vision' : '',
            'email' => $order->xui_email,
            'limitIp' => 0,
            'totalGB' => $totalBytes,
            'expiryTime' => $expiryMs,
            'enable' => true,
            'tgId' => '',
            'subId' => '',
            'reset' => 0,
        ];
    }

    protected function xui($inbound): XuiService
    {
        $serverId = $inbound->server->id;

        return $this->xuiCache[$serverId] ??= new XuiService($inbound->server);
    }

    protected function marzban($server): MarzbanService
    {
        return $this->marzbanCache[$server->id] ??= new MarzbanService($server);
    }

    /**
     * اجرای یک عملیات روی همه اینباندها و جمع‌آوری خطاها
     *
     * @return array{0: int, 1: array<int, string>} [تعداد موفق, خطاها]
     */
    protected function forEachInbound($inbounds, callable $callback): array
    {
        $ok = 0;
        $errors = [];

        foreach ($inbounds as $inbound) {
            try {
                $callback($this->xui($inbound), $inbound);
                $ok++;
            } catch (\Throwable $e) {
                $errors[] = $inbound->label().': '.$e->getMessage();
                Log::error('xui operation failed', ['inbound' => $inbound->id, 'error' => $e->getMessage()]);
            }
        }

        return [$ok, $errors];
    }
}
