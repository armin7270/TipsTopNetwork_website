<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAdminSection;
use App\Http\Middleware\RedirectIfPasswordChangeRequired;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return $app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'admin.section' => EnsureAdminSection::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            RedirectIfPasswordChangeRequired::class,
        ]);

        // پروکسی‌های معتبر برای تشخیص IP و HTTPS واقعی.
        // روی VPS مستقیم، TRUSTED_PROXIES را در .env با IP پروکسی (مثلاً Cloudflare) تنظیم کنید؛
        // مقدار * فقط برای هاست اشتراکی/ورث پلتفرم‌ها که IP ورودی قابل فهمیدن نیست — پیش‌فرض همه (سازگاری با رفتار قبلی)
        $trusted = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(at: $trusted === '' ? [] : array_map('trim', explode(',', $trusted)));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// پشتیبانی از مسیر storage سفارشی (محیط‌های serverless مثل Vercel — /tmp)
// بعد از create() و قبل از بوت شدن سرویس‌پروایدرها اجرا می‌شود
if (($storagePath = env('APP_STORAGE')) && $storagePath !== '') {
    $app->useStoragePath($storagePath);
}
