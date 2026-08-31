<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $settings = Cache::rememberForever('settings.all', function () {
                return self::query()->pluck('value', 'key')->toArray();
            });
        } catch (\Throwable) {
            // جدول هنوز ساخته نشده (مثلاً حین migrate:fresh)
            return $default;
        }

        $value = $settings[$key] ?? null;

        if (is_null($value)) {
            return $default;
        }

        return $value;
    }

    public static function getJson(string $key, mixed $default = null): mixed
    {
        $value = self::get($key);

        if (is_null($value)) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value]);

        Cache::forget('settings.all');
    }
}
