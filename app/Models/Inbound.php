<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Inbound extends Model
{
    protected $fillable = [
        'server_id',
        'xui_inbound_id',
        'protocol',
        'port',
        'public_port',
        'remark',
        'panel_data',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'panel_data' => 'array',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class);
    }

    public function publicPort(): int
    {
        return $this->public_port ?: $this->port;
    }

    public function label(): string
    {
        $server = $this->server?->name ?: __('سرور');

        return trim(($this->remark ?: $server).' ('.$this->protocol.' - '.__('پورت').' '.$this->publicPort().')');
    }
}
