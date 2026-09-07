<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:backup-database {--keep=14 : تعداد بکاپ‌های نگهداری‌شده}')]
#[Description('بکاپ دیتابیس SQLite در storage/app/backups + حذف نسخه‌های قدیمی')]
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

        return self::SUCCESS;
    }
}
