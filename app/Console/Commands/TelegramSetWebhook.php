<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {--url= : آدرس کامل وبهوک (پیش‌فرض: APP_URL + /telegram/webhook)}';

    protected $description = 'تنظیم وبهوک ربات تلگرام';

    public function handle(TelegramClient $telegram): int
    {
        if (! $telegram->isConfigured()) {
            $this->error('توکن ربات تلگرام تنظیم نشده است. از پنل مدیریت > تنظیمات، توکن را وارد کنید.');

            return self::FAILURE;
        }

        $url = $this->option('url') ?: rtrim(config('app.url'), '/').'/telegram/webhook';

        try {
            $telegram->setWebhook($url, (string) Setting::get('tg_webhook_secret', '') ?: null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $me = $telegram->getMe();

        $this->info('وبهوک با موفقیت تنظیم شد ✅');
        $this->line('ربات: @'.($me['username'] ?? '?'));
        $this->line('آدرس: '.$url);

        return self::SUCCESS;
    }
}
