<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_REFUND = 'refund';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const TYPE_REFERRAL_REWARD = 'referral_reward';

    public const TYPE_ADMIN_ADJUSTMENT = 'admin_adjustment';

    public const TYPES = [
        self::TYPE_DEPOSIT => 'شارژ کیف پول',
        self::TYPE_PURCHASE => 'خرید سرویس',
        self::TYPE_REFUND => 'بازگشت وجه',
        self::TYPE_WITHDRAWAL => 'برداشت',
        self::TYPE_REFERRAL_REWARD => 'پاداش دعوت',
        self::TYPE_ADMIN_ADJUSTMENT => 'تنظیم توسط مدیر',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING => 'در انتظار پرداخت',
        self::STATUS_AWAITING_VERIFICATION => 'در انتظار تایید',
        self::STATUS_COMPLETED => 'تکمیل شده',
        self::STATUS_FAILED => 'ناموفق',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'status',
        'method',
        'description',
        'order_id',
        'meta',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function typeLabel(): string
    {
        return __(self::TYPES[$this->type] ?? $this->type);
    }

    public function statusLabel(): string
    {
        return __(self::STATUSES[$this->status] ?? $this->status);
    }

    /**
     * آیا این تراکنش افزایش موجودی است؟
     */
    public function isCredit(): bool
    {
        if ($this->type === self::TYPE_ADMIN_ADJUSTMENT) {
            // تنظیم دستی مدیر با علامت منفی = کسر وجه
            return ($this->meta['direction'] ?? 'credit') !== 'debit';
        }

        return in_array($this->type, [self::TYPE_DEPOSIT, self::TYPE_REFUND, self::TYPE_REFERRAL_REWARD], true)
            && $this->amount > 0;
    }
}
