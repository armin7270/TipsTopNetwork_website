<?php

namespace App\Services;

use App\Models\DiscountCode;

/**
 * اعتبارسنجی و اعمال کد تخفیف
 */
class DiscountService
{
    /**
     * پیدا کردن کد معتبر — یا خطا
     *
     * @return array{0: ?DiscountCode, 1: ?string} [کد, خطا]
     */
    public function lookup(string $code, int $amount): array
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return [null, __('کد تخفیف را وارد کنید.')];
        }

        $discount = DiscountCode::query()->where('code', $code)->first();

        if (! $discount) {
            return [null, __('کد تخفیف نامعتبر است.')];
        }

        if ($error = $discount->validateFor($amount)) {
            return [null, $error];
        }

        return [$discount, null];
    }

    /**
     * مصرف یک بار کد (بعد از پرداخت موفق)
     */
    public function consume(DiscountCode $discount): void
    {
        $discount->increment('used_count');
    }
}
