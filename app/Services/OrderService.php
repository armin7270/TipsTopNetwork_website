<?php

namespace App\Services;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Vpn\ProvisioningService;
use Illuminate\Support\Facades\DB;

/**
 * سرویس سفارش: ساخت سفارش، پرداخت با کیف پول، تمدید
 */
class OrderService
{
    public function __construct(
        protected ProvisioningService $provisioning,
        protected WalletService $wallet,
    ) {}

    /**
     * ساخت سفارش جدید برای پلن (با منطق تمدید: اگر سرویس فعال داشته باشد، سفارش تمدید محسوب می‌شود)
     */
    public function createForPlan(User $user, Plan $plan, string $source = 'web', ?string $paymentMethod = null): Order
    {
        $activeOrder = $user->orders()
            ->where('status', Order::STATUS_ACTIVE)
            ->latest()
            ->first();

        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'renewal_of' => $activeOrder?->id,
            'plan_name' => $plan->name,
            'volume_gb' => $plan->volume_gb,
            'duration_days' => $plan->duration_days,
            'price_toman' => $plan->price_toman,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'source' => $source,
            'payment_method' => $paymentMethod,
        ]);

        $order->inbounds()->sync($plan->inbounds()->pluck('inbounds.id'));

        return $order;
    }

    /**
     * پرداخت آنی سفارش با کیف پول (تراکنشال — در صورت خطا مبلغ برمی‌گردد)
     */
    public function payWithWallet(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $user = User::query()->whereKey($order->user_id)->lockForUpdate()->first();

            if (! $user || $user->balance < $order->price_toman) {
                throw new \RuntimeException(__('موجودی کیف پول کافی نیست. ابتدا کیف پول خود را شارژ کنید.'));
            }

            $user->decrement('balance', $order->price_toman);

            $this->wallet->record(
                $user,
                Transaction::TYPE_PURCHASE,
                -$order->price_toman,
                Transaction::STATUS_COMPLETED,
                method: 'wallet',
                orderId: $order->id,
                description: __('خرید پلن :plan', ['plan' => $order->plan_name]),
            );

            $order->update([
                'payment_method' => 'wallet',
                'status' => Order::STATUS_AWAITING_VERIFICATION,
                'paid_amount' => $order->price_toman,
                'paid_at' => now(),
            ]);

            return $this->activatePaidOrder($order);
        });
    }

    /**
     * فعال‌سازی سفارش پرداخت‌شده (ساخت/تمدید کانفیگ + اطلاع + رویداد)
     * باید داخل تراکنش دیتابیس صدا زده شود. Idempotent است: سفارش فعال دوباره فعال نمی‌شود.
     */
    public function activatePaidOrder(Order $order): Order
    {
        $order->refresh();

        if ($order->status === Order::STATUS_ACTIVE) {
            return $order;
        }

        // فعال‌سازی سرویس در پنل (تمدید یا ساخت جدید)
        if ($order->isRenewal()) {
            $this->provisioning->extend($order);
        } else {
            $this->provisioning->provision($order);
        }

        $order->refresh();

        NotificationService::send(
            $order->user,
            'order_paid',
            $order->isRenewal() ? __('سرویس شما تمدید شد ✅') : __('سرویس شما فعال شد ✅'),
            __('پلن «:plan» فعال شد. لینک اشتراک در داشبورد موجود است.', ['plan' => $order->plan_name]),
            route('orders.show', $order),
        );

        // پیامک تایید برای کاربرانی که تلگرام ندارند
        if (! $order->user->telegram_chat_id) {
            SmsService::send(
                $order->user->phone,
                __(':site: سرویس :plan شما فعال شد. لینک اشتراک در داشبورد سایت موجود است.', [
                    'site' => Setting::get('site_name', 'TipStop'),
                    'plan' => $order->plan_name,
                ])
            );
        }

        event(new OrderPaid($order));

        return $order;
    }
}
