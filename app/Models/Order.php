<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT => 'در انتظار پرداخت',
        self::STATUS_AWAITING_VERIFICATION => 'در انتظار تایید پرداخت',
        self::STATUS_ACTIVE => 'فعال',
        self::STATUS_EXPIRED => 'منقضی شده',
        self::STATUS_REJECTED => 'رد شده',
        self::STATUS_CANCELLED => 'لغو شده',
    ];

    protected $fillable = [
        'user_id',
        'plan_id',
        'discount_code_id',
        'discount_amount',
        'renewal_of',
        'plan_name',
        'volume_gb',
        'duration_days',
        'price_toman',
        'status',
        'source',
        'payment_method',
        'xui_email',
        'xui_uuid',
        'sub_url',
        'bank_reference',
        'receipt_path',
        'paid_amount',
        'paid_at',
        'admin_note',
        'verified_by',
        'verified_at',
        'starts_at',
        'expires_at',
        'used_bytes',
        'total_bytes',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'volume_gb' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function inbounds(): BelongsToMany
    {
        return $this->belongsToMany(Inbound::class)->with('server');
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function statusLabel(): string
    {
        return __(self::STATUSES[$this->status] ?? $this->status);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_PENDING_PAYMENT, self::STATUS_AWAITING_VERIFICATION => 'yellow',
            self::STATUS_REJECTED, self::STATUS_EXPIRED, self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isRenewal(): bool
    {
        return ! is_null($this->renewal_of);
    }

    public function usedLabel(): string
    {
        return $this->formatBytes($this->used_bytes);
    }

    public function totalLabel(): string
    {
        if ($this->total_bytes <= 0) {
            return 'نامحدود';
        }

        return $this->formatBytes($this->total_bytes);
    }

    public function remainingLabel(): string
    {
        if ($this->total_bytes <= 0) {
            return 'نامحدود';
        }

        $remaining = max(0, $this->total_bytes - $this->used_bytes);

        return $this->formatBytes($remaining);
    }

    public function usagePercent(): int
    {
        if ($this->total_bytes <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->used_bytes / $this->total_bytes) * 100));
    }

    public function daysLeft(): int
    {
        if (! $this->expires_at) {
            return 0;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false));
    }

    protected function formatBytes(int|float $bytes): string
    {
        $bytes = (float) $bytes;
        if ($bytes < 1024) {
            return number_format($bytes).' بایت';
        }
        if ($bytes < 1024 ** 2) {
            return number_format($bytes / 1024, 1).' کیلوبایت';
        }
        if ($bytes < 1024 ** 3) {
            return number_format($bytes / 1024 ** 2, 1).' مگابایت';
        }

        return number_format($bytes / 1024 ** 3, 2).' گیگابایت';
    }
}
