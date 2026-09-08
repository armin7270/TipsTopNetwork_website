<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * خروجی/ورودی دیتابیس و فایل‌ها برای مهاجرت بین هاست‌ها
 * (مثلاً انتقال از یک اکانت Railway به اکانت دیگر بدون از دست رفتن داده)
 */
class DatabaseTransferService
{
    public function driver(): string
    {
        return (string) config('database.default');
    }

    /**
     * حجم تقریبی دیتابیس برای نمایش
     */
    public function databaseSizeLabel(): string
    {
        try {
            if ($this->driver() === 'sqlite') {
                $file = config('database.connections.sqlite.database');

                return is_file($file) ? $this->humanSize(filesize($file)) : '—';
            }

            if ($this->driver() === 'pgsql') {
                $bytes = DB::selectOne('SELECT pg_database_size(current_database()) AS size')?->size;

                return $bytes ? $this->humanSize((int) $bytes) : '—';
            }

            if ($this->driver() === 'mysql') {
                $db = config('database.connections.mysql.database');
                $bytes = DB::selectOne(
                    'SELECT SUM(data_length + index_length) AS size FROM information_schema.tables WHERE table_schema = ?',
                    [$db]
                )?->size;

                return $bytes ? $this->humanSize((int) $bytes) : '—';
            }
        } catch (\Throwable) {
        }

        return '—';
    }

    public function storageSizeLabel(): string
    {
        $dir = storage_path('app/public');

        if (! is_dir($dir)) {
            return '—';
        }

        $bytes = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $bytes += $file->getSize();
        }

        return $this->humanSize($bytes);
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 ** 2) {
            return round($bytes / 1024, 1).' KB';
        }
        if ($bytes < 1024 ** 3) {
            return round($bytes / 1024 ** 2, 1).' MB';
        }

        return round($bytes / 1024 ** 3, 2).' GB';
    }

    protected function run(string $command, array $env = []): void
    {
        $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($command, $descriptor, $pipes, null, $env + $_ENV);

        if (! is_resource($process)) {
            throw new \RuntimeException(__('اجرای دستور پشتیبان ناموفق بود (proc_open).'));
        }

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        if ($code !== 0) {
            throw new \RuntimeException(__('خطا در خروجی دیتابیس: :msg', ['msg' => mb_substr(trim($stderr), 0, 300) ?: ('exit '.$code)]));
        }
    }

    /**
     * خروجی SQL دیتابیس — مسیر فایل ساخته‌شده را برمی‌گرداند
     *
     * @throws \RuntimeException
     */
    public function exportDatabase(string $dir): string
    {
        if (! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            throw new \RuntimeException(__('ساخت پوشه خروجی ناموفق بود.'));
        }

        $file = rtrim($dir, '/').'/db-'.now()->format('Y-m-d_H-i').'-'.$this->driver().'.sql';

        if ($this->driver() === 'sqlite') {
            $source = config('database.connections.sqlite.database');

            if (! $source || $source === ':memory:' || ! is_file($source)) {
                throw new \RuntimeException(__('دیتابیس SQLite یافت نشد.'));
            }

            try {
                DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
            } catch (\Throwable) {
            }

            if (! copy($source, $file.'.sqlite')) {
                throw new \RuntimeException(__('کپی فایل دیتابیس ناموفق بود.'));
            }

            return $file.'.sqlite';
        }

        if ($this->driver() === 'pgsql') {
            $c = config('database.connections.pgsql');

            $this->run(sprintf(
                'pg_dump --no-owner --clean --if-exists -h %s -p %s -U %s -d %s -f %s',
                escapeshellarg($c['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($c['port'] ?? '5432')),
                escapeshellarg($c['username'] ?? ''),
                escapeshellarg($c['database'] ?? ''),
                escapeshellarg($file),
            ), ['PGPASSWORD' => (string) ($c['password'] ?? '')]);

            return $file;
        }

        if ($this->driver() === 'mysql') {
            $c = config('database.connections.mysql');

            $this->run(sprintf(
                'mysqldump --single-transaction --skip-comments -h %s -P %s -u %s %s %s > %s',
                escapeshellarg($c['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($c['port'] ?? '3306')),
                escapeshellarg($c['username'] ?? ''),
                $c['password'] !== '' && $c['password'] !== null ? '-p'.escapeshellarg($c['password']) : '',
                escapeshellarg($c['database'] ?? ''),
                escapeshellarg($file),
            ));

            return $file;
        }

        throw new \RuntimeException(__('درایور دیتابیس پشتیبانی نمی‌شود: :driver', ['driver' => $this->driver()]));
    }

    /**
     * ورود فایل SQL به دیتابیس فعلی
     *
     * @throws \RuntimeException
     */
    public function importDatabase(string $sqlFile): void
    {
        if (! is_file($sqlFile)) {
            throw new \RuntimeException(__('فایل یافت نشد.'));
        }

        if ($this->driver() === 'sqlite' && str_ends_with($sqlFile, '.sqlite')) {
            $target = config('database.connections.sqlite.database');

            if (! $target || $target === ':memory:') {
                throw new \RuntimeException(__('مسیر دیتابیس SQLite نامعتبر است.'));
            }

            if (! copy($sqlFile, $target)) {
                throw new \RuntimeException(__('جایگزینی فایل دیتابیس ناموفق بود.'));
            }

            return;
        }

        if ($this->driver() === 'pgsql') {
            $c = config('database.connections.pgsql');

            $this->run(sprintf(
                'psql -h %s -p %s -U %s -d %s -v ON_ERROR_STOP=1 -f %s',
                escapeshellarg($c['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($c['port'] ?? '5432')),
                escapeshellarg($c['username'] ?? ''),
                escapeshellarg($c['database'] ?? ''),
                escapeshellarg($sqlFile),
            ), ['PGPASSWORD' => (string) ($c['password'] ?? '')]);

            return;
        }

        if ($this->driver() === 'mysql') {
            $c = config('database.connections.mysql');

            $this->run(sprintf(
                'mysql -h %s -P %s -u %s %s %s < %s',
                escapeshellarg($c['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($c['port'] ?? '3306')),
                escapeshellarg($c['username'] ?? ''),
                $c['password'] !== '' && $c['password'] !== null ? '-p'.escapeshellarg($c['password']) : '',
                escapeshellarg($c['database'] ?? ''),
                escapeshellarg($sqlFile),
            ));

            return;
        }

        throw new \RuntimeException(__('درایور دیتابیس پشتیبانی نمی‌شود: :driver', ['driver' => $this->driver()]));
    }

    /**
     * زیپ فایل‌های عمومی (رسیدها و پیوست‌ها) — null اگر چیزی برای بکاپ نیست
     */
    public function exportPublicFiles(string $dir): ?string
    {
        $source = storage_path('app/public');

        if (! is_dir($source)) {
            return null;
        }

        if (! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            throw new \RuntimeException(__('ساخت پوشه خروجی ناموفق بود.'));
        }

        $file = rtrim($dir, '/').'/storage-'.now()->format('Y-m-d_H-i').'.zip';

        $zip = new ZipArchive;

        if ($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException(__('ساخت فایل زیپ ناموفق بود.'));
        }

        $added = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && basename($item->getPathname()) !== '.gitignore') {
                $zip->addFile($item->getPathname(), substr($item->getPathname(), strlen($source) + 1));
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($file);

            return null;
        }

        return $file;
    }

    /**
     * استخراج زیپ فایل‌های عمومی در storage/app/public
     */
    public function importPublicFiles(string $zipFile): int
    {
        $zip = new ZipArchive;

        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException(__('فایل زیپ معتبر نیست.'));
        }

        $target = storage_path('app/public');

        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $count = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            // جلوگیری از Zip Slip
            if (str_contains($name, '..') || str_starts_with($name, '/')) {
                continue;
            }

            if ($zip->extractTo($target, $name)) {
                $count++;
            }
        }

        $zip->close();

        return $count;
    }

    public function transferDir(): string
    {
        return Storage::path('migration');
    }
}
