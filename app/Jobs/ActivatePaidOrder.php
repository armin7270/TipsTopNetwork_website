<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * فعال‌سازی سفارش پرداخت‌شده در صف (ساخت کانفیگ در پنل 3x-ui)
 *
 * مزیت نسبت به ساخت همگام: خطای موقت شبکه/پنل باعث retry خودکار می‌شود
 * و درخواست HTTP ادمین/درگاه timeout نمی‌خورد.
 * تعداد تلاش: ۳ — با فاصله نمایی (فایل queue در دیتابیس).
 */
class ActivatePaidOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $orderId)
    {
        $this->onQueue('provisioning');
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(OrderService $orders): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order) {
            return;
        }

        // Idempotent — سفارش فعال دوباره فعال نمی‌شود
        if ($order->status !== Order::STATUS_AWAITING_VERIFICATION) {
            return;
        }

        try {
            $orders->activatePaidOrder($order);
        } catch (Throwable $e) {
            Log::error('job activate order failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            throw $e; // برای retry
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('order activation permanently failed', ['order' => $this->orderId, 'error' => $e->getMessage()]);

        $order = Order::query()->find($this->orderId);

        if ($order && $order->status === Order::STATUS_AWAITING_VERIFICATION) {
            NotificationService::notifyAdmins(
                'order_activation_failed',
                __('⚠️ فعال‌سازی خودکار سفارش #:id ناموفق ماند', ['id' => $this->orderId]),
                __('پرداخت انجام شده ولی ساخت کانفیگ بعد از ۳ تلاش ناموفق بود. از بخش «پرداخت‌ها» دوباره «تایید» را بزنید. خطا: :err', ['err' => Str::limit($e->getMessage(), 200)]),
                route('admin.payments.index'),
            );
        }
    }
}
