<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price_toman',
        'volume_gb',
        'duration_days',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'volume_gb' => 'float',
        ];
    }

    public function inbounds(): BelongsToMany
    {
        return $this->belongsToMany(Inbound::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function priceLabel(): string
    {
        return number_format($this->price_toman).' '.__('تومان');
    }

    public function volumeLabel(): string
    {
        return $this->volume_gb > 0
            ? number_format($this->volume_gb).' '.__('گیگابایت')
            : __('نامحدود');
    }

    public function durationLabel(): string
    {
        return number_format($this->duration_days).' '.__('روزه');
    }
}
