<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Telegram\TelegramClient;

/**
 * سیستم نوتیفیکیشن درون‌برنامه‌ای
 */
class NotificationService
{
    /**
     * ارسال نوتیفیکیشن به یک کاربر
     */
    public static function send(User $user, string $type, string $title, ?string $body = null, ?string $url = null): UserNotification
    {
        return UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);
    }

    /**
     * ارسال نوتیفیکیشن به همه ادمین‌ها (+ تلگرام در صورت تنظیم بودن چت‌آیدی مدیر)
     */
    public static function notifyAdmins(string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        $admins = User::query()->where('is_admin', true)->get();

        foreach ($admins as $admin) {
            self::send($admin, $type, $title, $body, $url);
        }

        // اطلاع‌رسانی تلگرامی به مدیر (مشابه vPanel)
        $adminChatId = Setting::get('tg_admin_chat_id');
        $botEnabled = Setting::get('tg_bot_enabled', '0');

        if ($adminChatId && $botEnabled === '1' && class_exists(TelegramClient::class)) {
            try {
                app(TelegramClient::class)->sendMessage(
                    $adminChatId,
                    '<b>'.e($title).'</b>'.($body ? "\n".e($body) : '')
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
