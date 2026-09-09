<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * کد تخفیف — درصدی یا مبلغ ثابت با سقف استفاده و انقضا
 */
class DiscountCode extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPES = [
        self::TYPE_PERCENT => 'درصدی',
        self::TYPE_FIXED => 'مبلغ ثابت',
    ];

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_amount',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * آیا کد برای مبلغ مشخص قابل استفاده است؟ (پیام خطا برمی‌گرداند اگر نه)
     */
    public function validateFor(int $amount, int $usedByUser = 0): ?string
    {
        if (! $this->is_active) {
            return __('این کد تخفیف غیرفعال است.');
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return __('این کد تخفیف منقضی شده است.');
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return __('ظرفیت استفاده از این کد تخفیف تمام شده است.');
        }

        if ($this->min_amount > 0 && $amount < $this->min_amount) {
            return __('این کد برای خرید کمتر از :amount تومان قابل استفاده نیست.', ['amount' => number_format($this->min_amount)]);
        }

        return null;
    }

    /**
     * مبلغ تخفیف برای قیمت مشخص
     */
    public function discountFor(int $amount): int
    {
        $discount = $this->type === self::TYPE_PERCENT
            ? (int) floor($amount * min(100, $this->value) / 100)
            : min($this->value, $amount);

        return max(0, $discount);
    }

    public function typeLabel(): string
    {
        return __(self::TYPES[$this->type] ?? $this->type);
    }
}
