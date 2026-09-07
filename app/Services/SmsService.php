<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ارسال پیامک با کاوه‌نگار (یادآوری انقضا، تایید سفارش و...)
 * غیرفعال به‌صورت پیش‌فرض — با تنظیم کلید از پنل مدیریت فعال می‌شود.
 */
class SmsService
{
    public static function isEnabled(): bool
    {
        return Setting::get('sms_enabled', '0') === '1'
            && trim((string) Setting::get('sms_api_key', '')) !== ''
            && trim((string) Setting::get('sms_sender', '')) !== '';
    }

    /**
     * ارسال پیامک به یک شماره موبایل ایرانی — ناموفق بودن لاگ می‌شود ولی اکسپشن نمی‌دهد
     */
    public static function send(string $phone, string $message): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        if (! preg_match('/^09\d{9}$/', $phone)) {
            return false;
        }

        try {
            $response = Http::timeout(20)->post(
                'https://api.kavenegar.com/v1/'.trim((string) Setting::get('sms_api_key')).'/sms/send.json',
                [
                    'receptor' => $phone,
                    'sender' => trim((string) Setting::get('sms_sender')),
                    'message' => mb_substr($message, 0, 500),
                ]
            )->json();

            $ok = isset($response['return']['status']) && (int) $response['return']['status'] === 200;

            if (! $ok) {
                Log::warning('sms send failed', ['phone' => $phone, 'response' => $response]);
            }

            return $ok;
        } catch (\Throwable $e) {
            Log::warning('sms send error: '.$e->getMessage());

            return false;
        }
    }
}
