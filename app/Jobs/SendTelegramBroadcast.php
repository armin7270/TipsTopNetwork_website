<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\User;
use App\Services\Telegram\TelegramClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ارسال پیام همگانی (برودکست) به همه کاربران دارای تلگرام — صف‌شده و chunk شده
 */
class SendTelegramBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;

    public function __construct(public string $html) {}

    public function handle(TelegramClient $telegram): void
    {
        if (! $telegram->isConfigured()) {
            return;
        }

        User::query()
            ->whereNotNull('telegram_chat_id')
            ->where('status', 'active')
            ->select('telegram_chat_id')
            ->chunk(100, function ($users) use ($telegram) {
                foreach ($users as $user) {
                    try {
                        $telegram->sendMessage($user->telegram_chat_id, $this->html);
                    } catch (\Throwable) {
                        // خطای ارسال به یک کاربر، بقیه را متوقف نمی‌کند
                    }

                    usleep(50000); // 50ms فاصله برای جلوگیری از محدودیت نرخ تلگرام
                }
            });

        Setting::set('tg_last_broadcast_at', now()->toDateTimeString());
    }
}
