<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Morilog\Jalali\Jalalian;

class Format
{
    public static function toman(int|float|string|null $value): string
    {
        return number_format((float) $value).' '.__('تومان');
    }

    public static function bytes(int|float|null $bytes): string
    {
        $bytes = (float) $bytes;

        if ($bytes < 1024) {
            return number_format($bytes).' '.__('بایت');
        }
        if ($bytes < 1024 ** 2) {
            return number_format($bytes / 1024, 1).' '.__('کیلوبایت');
        }
        if ($bytes < 1024 ** 3) {
            return number_format($bytes / 1024 ** 2, 1).' '.__('مگابایت');
        }

        return number_format($bytes / 1024 ** 3, 2).' '.__('گیگابایت');
    }

    /**
     * نمایش تاریخ: شمسی برای فارسی، میلادی برای انگلیسی
     */
    public static function date(?CarbonInterface $date, bool $withTime = true): string
    {
        if (! $date) {
            return '-';
        }

        $format = $withTime ? 'Y/m/d H:i' : 'Y/m/d';

        if (app()->getLocale() === 'fa' && class_exists(Jalalian::class)) {
            return Jalalian::fromCarbon($date)->format($format);
        }

        return $date->format($format);
    }
}
