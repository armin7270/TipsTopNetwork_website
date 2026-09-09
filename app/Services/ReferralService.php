<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Telegram\TelegramClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * سیستم معرفی (رفرال): هدیه خوش‌آمدگویی + پاداش معرف بعد از اولین خرید
 */
class ReferralService
{
    /**
     * اتصال کاربر جدید به معرف + اعطای هدیه خوش‌آمدگویی
     */
    public function onUserRegistered(User $user, ?string $referralCode): void
    {
        if (Setting::get('referral_enabled', '1') === '0') {
            return;
        }

        $referralCode = trim((string) $referralCode);

        if ($referralCode === '') {
            return;
        }

        $referrer = User::query()->where('referral_code', strtoupper($referralCode))->first();

        if (! $referrer || $referrer->id === $user->id) {
            return;
        }

        $user->update(['referrer_id' => $referrer->id]);

        // اطلاع به معرف
        NotificationService::send(
            $referrer,
            'referral_joined',
            __('یک کاربر با لینک معرفی شما ثبت‌نام کرد 🎉'),
            __(':name به جمع دوستان شما اضافه شد. با اولین خرید او پاداش می‌گیرید.', ['name' => $user->name]),
            route('referrals.index'),
        );

        $this->grantWelcomeGift($user, $referrer);
    }

    /**
     * اعطای هدیه خوش‌آمدگویی به کاربر جدید دعوت‌شده
     * ضد تقلب: اگر از یک IP دیگر حسابی با موجودی >= هدیه ساخته شده باشد، هدیه تعلق نمی‌گیرد
     */
    protected function grantWelcomeGift(User $user, User $referrer): void
    {
        $welcomeAmount = (int) Setting::get('referral_welcome_amount', 0);
        $minReferrerBalance = (int) Setting::get('referral_min_referrer_balance', 0);

        if ($welcomeAmount <= 0 || $referrer->balance < $minReferrerBalance) {
            return;
        }

        // ضد تقلب IP: ثبت‌نام هم‌IP با حساب بالاتر از مبلغ هدیه = سوءاستفاده احتمالی
        if ($user->ip_address) {
            $suspicious = User::query()
                ->where('ip_address', $user->ip_address)
                ->where('id', '!=', $user->id)
                ->where('balance', '>=', $welcomeAmount)
                ->exists();

            if ($suspicious) {
                Log::warning('referral welcome gift blocked (IP anti-fraud)', ['user' => $user->id, 'ip' => $user->ip_address]);

                return;
            }
        }

        DB::transaction(function () use ($user, $referrer, $welcomeAmount) {
            User::query()->whereKey($user->id)->lockForUpdate()->increment('balance', $welcomeAmount);

            app(WalletService::class)->record(
                $user,
                Transaction::TYPE_REFERRAL_REWARD,
                $welcomeAmount,
                Transaction::STATUS_COMPLETED,
                method: 'system',
                description: __('هدیه خوش‌آمدگویی دعوت از دوستان'),
            );

            NotificationService::send(
                $user,
                'referral_welcome_gift',
                __('هدیه خوش‌آمدگویی 🎁'),
                __('به دلیل دعوت توسط دوستان، مبلغ :amount تومان به کیف پول شما اضافه شد.', [
                    'amount' => number_format($welcomeAmount),
                ]),
                route('wallet.index'),
            );

            NotificationService::send(
                $referrer,
                'referral_gift_sent',
                __('هدیه دعوت به کیف پول کاربر جدید واریز شد'),
                __('مبلغ :amount تومان به کیف پول :name هدیه داده شد.', [
                    'amount' => number_format($welcomeAmount), 'name' => $user->name,
                ]),
            );
        });
    }

    /**
     * پرداخت پاداش به معرف بعد از اولین خرید موفق کاربر دعوت‌شده
     */
    public function rewardForFirstPurchase(Order $order): void
    {
        $user = $order->user;

        if (! $user?->referrer_id) {
            return;
        }

        if (Setting::get('referral_enabled', '1') === '0') {
            return;
        }

        // فقط بار اول خرید کاربر دعوت‌شده پاداش داده می‌شود
        $paidOrdersCount = Order::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [Order::STATUS_ACTIVE, Order::STATUS_EXPIRED])
            ->count();

        if ($paidOrdersCount > 1) {
            return;
        }

        $rewardAmount = (int) Setting::get('referral_reward_amount', 0);

        if ($rewardAmount <= 0) {
            return;
        }

        $referrer = User::query()->find($user->referrer_id);

        if (! $referrer) {
            return;
        }

        DB::transaction(function () use ($referrer, $user, $rewardAmount, $order) {
            User::query()->whereKey($referrer->id)->lockForUpdate()->increment('balance', $rewardAmount);

            app(WalletService::class)->record(
                $referrer,
                Transaction::TYPE_REFERRAL_REWARD,
                $rewardAmount,
                Transaction::STATUS_COMPLETED,
                method: 'system',
                orderId: $order->id,
                description: __('پاداش اولین خرید کاربر دعوت‌شده (:name)', ['name' => $user->name]),
            );

            NotificationService::send(
                $referrer,
                'referral_reward',
                __('پاداش دعوت واریز شد 🎉'),
                __(':name اولین خرید خود را انجام داد و مبلغ :amount تومان به کیف پول شما اضافه شد.', [
                    'name' => $user->name, 'amount' => number_format($rewardAmount),
                ]),
                route('wallet.index'),
            );

            // اطلاع تلگرامی در صورت اتصال ربات
            if ($referrer->telegram_chat_id && Setting::get('tg_bot_enabled') === '1') {
                try {
                    app(TelegramClient::class)->sendMessage(
                        $referrer->telegram_chat_id,
                        '🎉 <b>پاداش دعوت واریز شد!</b>'."\n".
                        e($user->name).' اولین خرید خود را انجام داد و مبلغ '.number_format($rewardAmount).' تومان به کیف پول شما اضافه شد.'
                    );
                } catch (\Throwable) {
                    // ربات در دسترس نیست، اهمیتی ندارد
                }
            }
        });
    }

    /**
     * آمار معرفی یک کاربر
     */
    public function statsFor(User $user): array
    {
        $earned = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', Transaction::TYPE_REFERRAL_REWARD)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('amount');

        return [
            'count' => $user->referrals()->count(),
            'earned' => (int) $earned,
        ];
    }
}
