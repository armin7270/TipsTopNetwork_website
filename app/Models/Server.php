<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'name',
        'panel_type',
        'api_scheme',
        'api_host',
        'api_port',
        'api_path',
        'username',
        'password',
        'public_host',
        'is_active',
        'last_check_at',
        'last_check_ok',
        'last_check_error',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'is_active' => 'boolean',
            'last_check_at' => 'datetime',
            'last_check_ok' => 'boolean',
        ];
    }

    public const PANEL_TYPES = [
        'xui' => '3x-ui / TX-UI',
        'marzban' => 'Marzban',
    ];

    public function panelTypeLabel(): string
    {
        return self::PANEL_TYPES[$this->panel_type] ?? $this->panel_type;
    }

    public function inbounds(): HasMany
    {
        return $this->hasMany(Inbound::class);
    }

    public function publicHost(): string
    {
        return $this->public_host ?: $this->api_host;
    }
}
