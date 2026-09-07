<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * نقاط کمکی دیپلوی روی هاست اشتراکی (بدون SSH):
 * اجرای مایگریشن و کرون/صف از طریق URL + کلید مخفی DEPLOY_KEY
 */
class DeployController extends Controller
{
    protected function authorizeKey(Request $request): void
    {
        $key = (string) env('DEPLOY_KEY', '');

        abort_unless($key !== '', 404);
        abort_unless(hash_equals($key, (string) $request->query('key', '')), 403);
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
}
