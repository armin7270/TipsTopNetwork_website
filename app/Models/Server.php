<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'name',
        'api_scheme',
        'api_host',
        'api_port',
        'api_path',
        'username',
        'password',
        'public_host',
        'ssl_verify',
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
            'ssl_verify' => 'boolean',
            'is_active' => 'boolean',
            'last_check_at' => 'datetime',
            'last_check_ok' => 'boolean',
        ];
    }

    /**
     * آیا هنگام اتصال HTTPS به پنل، گواهی SSL بررسی شود؟
     * روی لوکال/گواهی self-signed ادمین می‌تواند آن را خاموش کند.
     */
    public function shouldVerifySsl(): bool
    {
        if ($this->api_scheme !== 'https') {
            return true;
        }

        return $this->ssl_verify !== false;
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
