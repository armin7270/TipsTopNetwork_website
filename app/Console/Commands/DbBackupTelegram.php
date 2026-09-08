<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\DatabaseTransferService;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('db:backup-telegram')]
#[Description('ارسال بکاپ دیتابیس (+ فایل‌ها) به تلگرام مدیر — بکاپ آف‌سایت رایگان')]
class DbBackupTelegram extends Command
{
    public function handle(DatabaseTransferService $transfer, TelegramClient $telegram): int
    {
        $chatId = trim((string) Setting::get('tg_admin_chat_id', ''));

        if (Setting::get('tg_bot_enabled', '0') !== '1' || $chatId === '' || ! $telegram->isConfigured()) {
            $this->warn('ربات تلگرام یا چت‌آیدی مدیر تنظیم نشده؛ بکاپ ارسال نشد.');

            return self::FAILURE;
        }

        try {
            $db = $transfer->exportDatabase($transfer->transferDir());

            // تلگرام تا ۵۰ مگابایت قبول می‌کند
            if (filesize($db) > 45 * 1024 * 1024) {
                $this->error('حجم دامپ از سقف تلگرام بیشتر است؛ از پنل ادمین دانلود کنید.');

                return self::FAILURE;
            }

            $telegram->sendLocalDocument(
                $chatId,
                $db,
                '💾 <b>بکاپ دیتابیس</b>'."\n".
                '📅 '.now()->format('Y-m-d H:i')."\n".
                '🗄 '.config('database.default').' ('.round(filesize($db) / 1024).' KB)'
            );

            $this->info('بکاپ دیتابیس به تلگرام ارسال شد.');

            if ($zip = $transfer->exportPublicFiles($transfer->transferDir())) {
                if (filesize($zip) <= 45 * 1024 * 1024) {
                    $telegram->sendLocalDocument($chatId, $zip, '📁 <b>بکاپ فایل‌های عمومی</b>');
                    $this->info('بکاپ فایل‌ها به تلگرام ارسال شد.');
                }
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
