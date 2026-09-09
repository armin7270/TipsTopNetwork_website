<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'username', 'phone', 'password', 'is_admin', 'status', 'subscription_code',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'phone',
        'password',
        'password_changed_at',
        'is_admin',
        'admin_role',
        'status',
        'ip_address',
        'subscription_code',
        'balance',
        'referral_code',
        'referrer_id',
        'telegram_chat_id',
        'telegram_username',
        'bot_state',
        'trial_accounts_taken',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * مقادیر پیش‌فرض در سطح مدل (مهم: پیش‌فرض دیتابیس به نمونه in-memory برنمی‌گردد
     * و چک‌های نقش روی همان نمونه انجام می‌شود)
     */
    protected $attributes = [
        'admin_role' => 'super',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'password_changed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->subscription_code)) {
                do {
                    $code = Str::random(32);
                } while (self::where('subscription_code', $code)->exists());
                $user->subscription_code = $code;
            }

            if (empty($user->referral_code)) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (self::where('referral_code', $code)->exists());
                $user->referral_code = $code;
            }
        });
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public const ADMIN_ROLES = [
        'super' => 'مدیرکل',
        'finance' => 'مدیر مالی',
        'support' => 'پشتیبانی',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && $this->admin_role === 'super';
    }

    public function adminRoleLabel(): string
    {
        return __(self::ADMIN_ROLES[$this->admin_role] ?? $this->admin_role);
    }

    /**
     * آیا این مدیر به بخش مشخصی از پنل دسترسی دارد؟
     */
    public function canAccessSection(string $section): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->admin_role === $section;
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrders(): HasMany
    {
        return $this->orders()->where('status', Order::STATUS_ACTIVE);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class)->latest();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function trials(): HasMany
    {
        return $this->hasMany(UserTrial::class)->latest();
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referrer_id');
    }

    public function balanceLabel(): string
    {
        return number_format($this->balance).' '.__('تومان');
    }
}
