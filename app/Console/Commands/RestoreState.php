<?php

namespace App\Console\Commands;

use App\Support\StateCrypto;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:restore-state {file : مسیر فایل state رمزنگاری‌شده (tipstop-state.bin)} {--force : جایگزینی دیتابیس بدون سوال}')]
#[Description('بازیابی state مهاجرت: دیتابیس SQLite + فایل‌های storage از فایل رمزنگاری‌شده — برای انتقال به اکانت Railway جدید')]
class RestoreState extends Command
{
    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error('فایل state یافت نشد: '.$file);

            return self::FAILURE;
        }

        $key = (string) env('STATE_KEY', '');

        if ($key === '') {
            $this->error('متغیر STATE_KEY تنظیم نشده است — باید همان مقدار اکانت قبلی باشد.');

            return self::FAILURE;
        }

        $this->info('🔒 رمزگشایی state...');

        try {
            $plain = StateCrypto::decrypt((string) file_get_contents($file), $key);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'tsrestore').'.zip';
        file_put_contents($zipPath, $plain);
        unset($plain);

        $zip = new \ZipArchive;

        if ($zip->open($zipPath) !== true) {
            $this->error('محتوای state معتبر نیست.');

            @unlink($zipPath);

            return self::FAILURE;
        }

        $hasDatabase = $zip->locateName('database.sqlite') !== false;
        $storageCount = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (str_starts_with($name, 'storage/') && ! str_ends_with($name, '/')) {
                $storageCount++;
            }
        }

        $this->table(['مورد', 'وضعیت'], [
            ['دیتابیس SQLite', $hasDatabase ? '✅ موجود' : '—'],
            ['فایل‌های storage', $storageCount.' فایل'],
        ]);

        $target = (string) config('database.connections.sqlite.database');

        if ($hasDatabase && $target !== '' && $target !== ':memory:') {
            $this->restoreDatabase($zip, $target);
        }

        if ($storageCount > 0) {
            $this->restoreStorage($zip);
        }

        $zip->close();
        @unlink($zipPath);

        $this->newLine();
        $this->info('🎉 بازیابی state کامل شد. مایگریت‌های جدید هم اجرا شدند — سایت اکانت جدید همان داده‌های قبلی را دارد.');

        return self::SUCCESS;
    }

    /**
     * آیا دیتابیس فعلی تازه/بدون داده است؟
     * معیار: جدول users وجود ندارد یا صفر ردیف دارد.
     * این guard جلوی overwrite شدن دیتابیس زنده در استارت‌های بعدی Railway را می‌گیرد؛
     * با --force به‌صورت دستی می‌توان آن را دور زد (فقط برای ریکاوری اضطراری).
     * عمداً با PDO مستقیم چک می‌شود (نه کانکشن اپ) تا از فایل هدف قطعی بخواند.
     */
    protected function databaseIsEmpty(string $target): bool
    {
        if (! is_file($target) || filesize($target) === 0) {
            return true;
        }

        try {
            $pdo = new \PDO('sqlite:'.$target, null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);

            return ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()) === 0;
        } catch (\Throwable) {
            // جدول users هنوز وجود ندارد (دیتابیس تازه)
            return true;
        }
    }

    protected function restoreDatabase(\ZipArchive $zip, string $target): void
    {
        // Guard مهم: در استارت‌های بعدی Railway، دیتابیس «زنده» نباید با state قدیمی overwrite شود.
        // بازیابی خودکار فقط وقتی انجام می‌شود که دیتابیس فعلی خالی باشد (اولین استارت اکانت جدید).
        // --force فقط برای ریکاوری اضطراری دستی است و این guard را دور می‌زند.
        if (! $this->option('force') && ! $this->databaseIsEmpty($target)) {
            $this->info('ℹ️ دیتابیس فعلی خالی نیست — بازیابی state رد شد (داده‌های زنده محافظت می‌شوند).');

            return;
        }

        $this->info('📦 بازیابی دیتابیس...');

        // استخراج به فایل موقت
        $tmp = tempnam(sys_get_temp_dir(), 'tsdb');
        $stream = $zip->getStream('database.sqlite');

        if ($stream === false || ! file_put_contents($tmp, $stream)) {
            $this->error('استخراج دیتابیس از state ناموفق بود.');

            return;
        }

        fclose($stream);

        // قطع اتصال قبل از جایگزینی فایل (SQLite فایل را قفل می‌کند)
        DB::purge('sqlite');

        if (! copy($tmp, $target)) {
            $this->error('جایگزینی دیتابیس ناموفق بود (مسیر: '.$target.').');

            @unlink($tmp);

            return;
        }

        @unlink($tmp);

        // اجرای مایگریت‌های جدید روی دیتابیس واردشده
        try {
            $this->call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            $this->warn('مایگریت بعد از بازیابی ناموفق بود: '.$e->getMessage());
        }

        $this->info('✅ دیتابیس بازیابی شد.');
    }

    protected function restoreStorage(\ZipArchive $zip): void
    {
        $this->info('📁 بازیابی فایل‌های storage...');

        $base = storage_path('app');
        $count = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (! str_starts_with($name, 'storage/') || str_ends_with($name, '/')) {
                continue;
            }

            // جلوگیری از Zip Slip
            $relative = substr($name, strlen('storage/'));

            if (str_contains($relative, '..')) {
                continue;
            }

            $stream = $zip->getStream($name);

            if ($stream === false) {
                continue;
            }

            $dest = $base.'/'.$relative;
            @mkdir(dirname($dest), 0755, true);

            $count += (int) (bool) file_put_contents($dest, $stream);
            fclose($stream);
        }

        $this->info("✅ {$count} فایل در storage/app بازیابی شد.");
    }
}
