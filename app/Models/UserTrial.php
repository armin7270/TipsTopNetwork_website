<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTrial extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'uuid',
        'config',
        'volume_mb',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->expires_at->isFuture();
    }

    public function volumeLabel(): string
    {
        return number_format($this->volume_mb).' مگابایت';
    }
}
