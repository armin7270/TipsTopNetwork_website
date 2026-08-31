<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * بعد از پرداخت موفق و فعال شدن سفارش صادر می‌شود
 * (پرداخت کیف پول، تایید رسید کارت، خرید از ربات تلگرام)
 */
class OrderPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
