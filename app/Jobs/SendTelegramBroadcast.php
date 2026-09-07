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
 * ارسال پیام همگانی (برودکست) به کاربران دارای تلگرام — تکه‌تکه (chunk) شده
 * تا روی هاست اشتراکی با محدودیت زمانی هم با چند اجرای کرون کامل شود.
 */
class SendTelegramBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 2;

    public const CHUNK_SIZE = 50;

    public function __construct(
        public string $html,
        public int $offset = 0,
        public bool $isRoot = true,
    ) {}

    public function handle(TelegramClient $telegram): void
    {
        if (! $telegram->isConfigured()) {
            return;
        }

        $base = User::query()
            ->whereNotNull('telegram_chat_id')
            ->where('status', 'active')
            ->orderBy('id');

        // ریشه: بقیه تکه‌ها را هم در صف می‌گذارد تا هر کدام جدا پردازش شوند
        if ($this->isRoot) {
            $total = (clone $base)->count();

            for ($offset = self::CHUNK_SIZE; $offset < $total; $offset += self::CHUNK_SIZE) {
                self::dispatch($this->html, $offset, false);
            }

            Setting::set('tg_last_broadcast_at', now()->toDateTimeString());
        }

        $users = (clone $base)->skip($this->offset)->take(self::CHUNK_SIZE)->pluck('telegram_chat_id');

        foreach ($users as $chatId) {
            try {
                $telegram->sendMessage($chatId, $this->html);
            } catch (\Throwable) {
                // خطای ارسال به یک کاربر، بقیه را متوقف نمی‌کند
            }

            usleep(50000); // 50ms فاصله برای جلوگیری از محدودیت نرخ تلگرام
        }
    }
}
