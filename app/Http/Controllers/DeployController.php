<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\StateCrypto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * نقاط کمکی دیپلوی روی هاست اشتراکی (بدون SSH):
 * اجرای مایگریشن و کرون/صف از طریق URL + کلید مخفی DEPLOY_KEY
 * + خروجی رمزنگاری‌شده state (دیتابیس + فایل‌ها) برای مهاجرت خودکار بین اکانت‌ها
 */
class DeployController extends Controller
{
    protected function authorizeKey(Request $request): void
    {
        $key = (string) env('DEPLOY_KEY', '');

        abort_unless($key !== '', 404);

        // کلید ترجیحاً از هدر X-Deploy-Key (نمی‌ماند در لاگ‌های سرور/پروکسی)؛ پارامتر key هم برای سازگاری قبول است
        $provided = (string) ($request->header('X-Deploy-Key') ?: $request->query('key', ''));

        abort_unless($provided !== '' && hash_equals($key, $provided), 403);
    }

    /**
     * اجرای مایگریشن + سید اولیه + لینک storage (فقط یک‌بار بعد از آپلود)
     */
    public function migrate(Request $request): JsonResponse
    {
        $this->authorizeKey($request);

        $log = [];

        Artisan::call('migrate', ['--force' => true]);
        $log['migrate'] = trim(Artisan::output());

        if (User::query()->count() === 0) {
            Artisan::call('db:seed', ['--force' => true]);
            $log['seed'] = trim(Artisan::output());
        } else {
            $log['seed'] = 'skipped (users table not empty)';
        }

        try {
            Artisan::call('storage:link');
            $log['storage_link'] = trim(Artisan::output()) ?: 'done';
        } catch (\Throwable $e) {
            $log['storage_link'] = 'failed: '.$e->getMessage();
        }

        return response()->json(['ok' => true, 'log' => $log]);
    }

    /**
     * اجرای زمان‌بند لاراول + خالی کردن صف (برای کرون خارجی مثل cron-job.org هر ۵ دقیقه)
     */
    public function cron(Request $request): JsonResponse
    {
        $this->authorizeKey($request);

        set_time_limit(120);

        Artisan::call('schedule:run');
        $schedule = trim(Artisan::output());

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 3,
            '--sleep' => 1,
            '--timeout' => 100,
        ]);
        $queue = trim(Artisan::output());

        return response()->json(['ok' => true, 'schedule' => $schedule, 'queue' => $queue]);
    }

    /**
     * خروجی state مهاجرت (دیتابیس SQLite + فایل‌های storage) — رمزنگاری‌شده با STATE_KEY.
     *
     * برای انتقال سایت به اکانت Railway جدید بدون از دست رفتن داده:
     * GitHub Action این فایل را دانلود و در ریپو (deploy-state/state.bin) کامیت می‌کند،
     * و در اکانت جدید دستور `php artisan app:restore-state` هنگام استارت، آن را بازمی‌گرداند.
     * STATE_KEY فقط روی خود سرویس لازم است — در گیت‌هاب چیزی جز DEPLOY_KEY ذخیره نمی‌شود.
     */
    public function state(Request $request): Response
    {
        $this->authorizeKey($request);

        $key = (string) env('STATE_KEY', '');

        abort_unless($key !== '', 404, 'STATE_KEY is not configured on the server.');

        set_time_limit(120);

        $zipPath = tempnam(sys_get_temp_dir(), 'tsstate').'.zip';

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'ساخت فایل state ناموفق بود.');
        }

        // دیتابیس SQLite (کپی امن — ابتدا WAL checkpoint)
        if (config('database.default') === 'sqlite') {
            $db = config('database.connections.sqlite.database');

            if ($db && $db !== ':memory:' && is_file($db)) {
                try {
                    DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
                } catch (\Throwable) {
                }

                $zip->addFile($db, 'database.sqlite');
            }
        }

        // فایل‌های storage/app (رسیدها، پیوست‌ها) — بدون بکاپ‌های خودکار و فایل‌های موقت مهاجرت
        $base = storage_path('app');

        if (is_dir($base)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
                    fn (\SplFileInfo $item, $k, $it) => ! $it->hasChildren()
                        || ! in_array($item->getFilename(), ['backups', 'migration', 'framework', 'cache'], true)
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $item) {
                /** @var \SplFileInfo $item */
                if ($item->isFile() && $item->getFilename() !== '.gitignore') {
                    $relative = substr($item->getPathname(), strlen($base) + 1);

                    $zip->addFile($item->getPathname(), 'storage/'.$relative);
                }
            }
        }

        $zip->close();

        $plain = (string) file_get_contents($zipPath);
        @unlink($zipPath);

        $encrypted = StateCrypto::encrypt($plain, $key);

        return response($encrypted, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="tipstop-state.bin"',
            'Content-Length' => strlen($encrypted),
        ]);
    }
}
