<?php

namespace App\Services\Telegram;

use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * کلاینت سبک API تلگرام (بدون نیاز به پکیج اضافه — بر پایه Guzzle موجود)
 * مستندات: https://core.telegram.org/bots/api
 */
class TelegramClient
{
    protected Client $http;

    protected ?string $token = null;

    public function __construct(?string $token = null)
    {
        $this->token = $token ?: (string) Setting::get('tg_bot_token', '');

        $this->http = new Client([
            'timeout' => 20,
            'connect_timeout' => 10,
            // پیش‌فرض فعال؛ اگر سرور با گواهی/CA مشکل داشت، TELEGRAM_VERIFY_TLS=false در .env
            'verify' => (bool) env('TELEGRAM_VERIFY_TLS', true),
        ]);
    }

    /**
     * آیا توکن ربات تنظیم شده است؟
     */
    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    /**
     * ارسال درخواست به API تلگرام
     *
     * @throws TelegramException
     */
    public function call(string $method, array $params = []): array
    {
        if (! $this->isConfigured()) {
            throw new TelegramException('توکن ربات تلگرام تنظیم نشده است.');
        }

        try {
            $response = $this->http->post("https://api.telegram.org/bot{$this->token}/{$method}", [
                'json' => $params,
            ]);

            $data = json_decode((string) $response->getBody(), true);
        } catch (\Throwable $e) {
            Log::warning('telegram api error', ['method' => $method, 'error' => $e->getMessage()]);

            throw new TelegramException('خطا در ارتباط با تلگرام: '.$e->getMessage(), 0, $e);
        }

        if (($data['ok'] ?? false) !== true) {
            throw new TelegramException('خطای تلگرام: '.($data['description'] ?? 'پاسخ نامعتبر'));
        }

        return $data['result'] ?? [];
    }

    /**
     * ارسال پیام متنی (HTML)
     */
    public function sendMessage(string $chatId, string $html, ?array $inlineKeyboard = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($inlineKeyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $inlineKeyboard];
        }

        return $this->call('sendMessage', $params);
    }

    /**
     * ویرایش متن پیام (برای منوهای callback)
     */
    public function editMessageText(string $chatId, int $messageId, string $html, ?array $inlineKeyboard = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($inlineKeyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $inlineKeyboard];
        }

        return $this->call('editMessageText', $params);
    }

    /**
     * پاسخ به callback query (متوقف کردن حالت loading)
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): array
    {
        $params = ['callback_query_id' => $callbackQueryId];

        if ($text) {
            $params['text'] = $text;
        }

        return $this->call('answerCallbackQuery', $params);
    }

    /**
     * ارسال عکس (مسیر فایل محلی، URL یا file_id)
     */
    public function sendPhoto(string $chatId, string $photo, ?string $caption = null, ?array $inlineKeyboard = null): array
    {
        // فایل محلی → آپلود multipart
        if (is_file($photo)) {
            $multipart = [
                ['name' => 'chat_id', 'contents' => $chatId],
                ['name' => 'photo', 'contents' => fopen($photo, 'rb')],
            ];

            if ($caption) {
                $multipart[] = ['name' => 'caption', 'contents' => $caption];
                $multipart[] = ['name' => 'parse_mode', 'contents' => 'HTML'];
            }

            if ($inlineKeyboard) {
                $multipart[] = ['name' => 'reply_markup', 'contents' => json_encode(['inline_keyboard' => $inlineKeyboard], JSON_UNESCAPED_UNICODE)];
            }

            return $this->upload('sendPhoto', $multipart);
        }

        $params = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'parse_mode' => 'HTML',
        ];

        if ($caption) {
            $params['caption'] = $caption;
        }

        if ($inlineKeyboard) {
            $params['reply_markup'] = ['inline_keyboard' => $inlineKeyboard];
        }

        return $this->call('sendPhoto', $params);
    }

    /**
     * ارسال multipart (آپلود فایل) به API تلگرام
     *
     * @throws TelegramException
     */
    public function upload(string $method, array $multipart): array
    {
        if (! $this->isConfigured()) {
            throw new TelegramException('توکن ربات تلگرام تنظیم نشده است.');
        }

        try {
            $response = $this->http->post("https://api.telegram.org/bot{$this->token}/{$method}", [
                'multipart' => $multipart,
            ]);

            $data = json_decode((string) $response->getBody(), true);
        } catch (\Throwable $e) {
            throw new TelegramException('خطا در آپلود به تلگرام: '.$e->getMessage(), 0, $e);
        }

        if (($data['ok'] ?? false) !== true) {
            throw new TelegramException('خطای تلگرام: '.($data['description'] ?? 'پاسخ نامعتبر'));
        }

        return $data['result'] ?? [];
    }

    /**
     * ارسال مدارک (فایل - برای رسید رسید و...)
     */
    public function sendDocument(string $chatId, string $document, ?string $caption = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'document' => $document,
            'parse_mode' => 'HTML',
        ];

        if ($caption) {
            $params['caption'] = $caption;
        }

        return $this->call('sendDocument', $params);
    }

    /**
     * ارسال فایل لوکال به‌صورت داکیومنت (آپلود multipart)
     */
    public function sendLocalDocument(string $chatId, string $filePath, ?string $caption = null): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new TelegramException('فایل یافت نشد: '.$filePath);
        }

        $multipart = [
            ['name' => 'chat_id', 'contents' => $chatId],
            ['name' => 'document', 'contents' => fopen($filePath, 'r'), 'filename' => basename($filePath)],
            ['name' => 'parse_mode', 'contents' => 'HTML'],
        ];

        if ($caption) {
            $multipart[] = ['name' => 'caption', 'contents' => mb_substr($caption, 0, 1000)];
        }

        return $this->upload('sendDocument', $multipart);
    }

    /**
     * حذف پیام
     */
    public function deleteMessage(string $chatId, int $messageId): array
    {
        return $this->call('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    /**
     * بررسی عضویت کاربر در کانال
     */
    public function isMemberOfChannel(string $chatId, string $channel): bool
    {
        $channel = trim($channel, '@');

        try {
            $member = $this->call('getChatMember', [
                'chat_id' => '@'.$channel,
                'user_id' => $chatId,
            ]);

            return in_array($member['status'] ?? '', ['creator', 'administrator', 'member'], true);
        } catch (\Throwable) {
            // اگر کانال خصوصی یا خطا بود، اجازه عبور می‌دهیم تا ربات از کار نیفتد
            return true;
        }
    }

    /**
     * تنظیم وبهوک ربات
     */
    public function setWebhook(string $url, ?string $secret = null): array
    {
        $params = [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ];

        if ($secret) {
            $params['secret_token'] = $secret;
        }

        return $this->call('setWebhook', $params);
    }

    /**
     * اطلاعات ربات (برای تست اتصال)
     */
    public function getMe(): array
    {
        return $this->call('getMe');
    }
}
