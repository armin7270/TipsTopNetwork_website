<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'کم',
        self::PRIORITY_MEDIUM => 'متوسط',
        self::PRIORITY_HIGH => 'زیاد',
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN => 'باز',
        self::STATUS_ANSWERED => 'پاسخ داده شده',
        self::STATUS_CLOSED => 'بسته شده',
    ];

    protected $fillable = [
        'user_id',
        'subject',
        'priority',
        'status',
        'last_reply_at',
    ];

    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('id');
    }

    public function statusLabel(): string
    {
        return __(self::STATUSES[$this->status] ?? $this->status);
    }

    public function priorityLabel(): string
    {
        return __(self::PRIORITIES[$this->priority] ?? $this->priority);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'yellow',
            self::STATUS_ANSWERED => 'green',
            default => 'gray',
        };
    }
}
