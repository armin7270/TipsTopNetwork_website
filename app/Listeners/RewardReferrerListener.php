<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * پرداخت پاداش به معرف بعد از اولین خرید موفق کاربر دعوت‌شده
 */
class RewardReferrerListener implements ShouldQueue
{
    public function __construct(protected ReferralService $referrals) {}

    public function handle(OrderPaid $event): void
    {
        $this->referrals->rewardForFirstPurchase($event->order);
    }
}
