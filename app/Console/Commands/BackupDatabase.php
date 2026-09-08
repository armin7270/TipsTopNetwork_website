<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:backup-database {--keep=14 : تعداد بکاپ‌های نگهداری‌شده} {--send-telegram : ارسال بکاپ به تلگرام مدیر (بکاپ آفسایت)}')]
#[Description('بکاپ دیتابیس SQLite در storage/app/backups + حذف نسخه‌های قدیمی (+ ارسال آفسایت به تلگرام)')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->warn('این دستور فقط برای دیتابیس SQLite است.');

            return self::FAILURE;
        }

        $source = config('database.connections.sqlite.database');

        if (! $source || $source === ':memory:' || ! file_exists($source)) {
            $this->error('فایل دیتابیس یافت نشد.');

            return self::FAILURE;
        }

        Storage::makeDirectory('backups');

        $name = 'backups/backup-'.now()->format('Y-m-d_H-i').'.sqlite';
        $target = Storage::path($name);

        // کپی امن حین اجرا (WAL checkpoint اول)
        try {
            \DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable) {
        }

        if (! copy($source, $target)) {
            $this->error('کپی دیتابیس ناموفق بود.');

            return self::FAILURE;
        }

        // حذف بکاپ‌های قدیمی‌تر از حد نگهداری
        $files = collect(Storage::files('backups'))
            ->filter(fn ($f) => str_ends_with($f, '.sqlite'))
            ->sort()
            ->values();

        $keep = max(1, (int) $this->option('keep'));

        foreach ($files->slice(0, max(0, $files->count() - $keep)) as $old) {
            Storage::delete($old);
        }

        $this->info('بکاپ ذخیره شد: '.$name.' ('.round(filesize($target) / 1024).' KB)');

        // بکاپ آفسایت: ارسال فایل دیتابیس به تلگرام مدیر (در صورت گم شدن سرور، نسخه بیرون از سرور موجود است)
        if ($this->option('send-telegram')) {
            $this->sendToTelegram($target);
        }

        return self::SUCCESS;
    }

    /**
     * ارسال بکاپ به چت تلگرامی مدیر
     */
    protected function sendToTelegram(string $file): void
    {
        $chatId = (string) Setting::get('tg_admin_chat_id', '');

        $botEnabled = Setting::get('tg_bot_enabled', '0') === '1';

        if ($chatId === '' || ! $botEnabled) {
            $this->warn('ارسال آفسایت رد شد: ربات تلگرام یا چت‌آیدی مدیر تنظیم نشده است.');

            return;
        }

        if (filesize($file) > 49 * 1024 * 1024) {
            $this->warn('ارسال آفسایت رد شد: حجم بکاپ از سقف ۵۰ مگابایت تلگرام بیشتر است.');

            return;
        }

        try {
            app(TelegramClient::class)->upload('sendDocument', [
                ['name' => 'chat_id', 'contents' => $chatId],
                ['name' => 'document', 'contents' => fopen($file, 'rb')],
                ['name' => 'caption', 'contents' => '🗄 بکاپ خودکار دیتابیس '.now()->format('Y-m-d H:i')],
            ]);

            $this->info('بکاپ به تلگرام مدیر ارسال شد. ✅');
        } catch (\Throwable $e) {
            $this->warn('ارسال بکاپ به تلگرام ناموفق بود: '.$e->getMessage());
        }
    }
}
