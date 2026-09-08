<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * اجبار https روی پلتفرم‌های پراکسی‌شده (Railway و مشابه).
 * بدون این، دارایی‌های Vite با http:// تولید می‌شوند و مرورگر آن‌ها را به‌عنوان
 * Mixed Content مسدود می‌کند → سایت بدون CSS/JS و به‌هم‌ریخته نمایش داده می‌شود.
 * نکته: URL::forceScheme به‌تنهایی روی آدرس‌های Vite اثر ندارد چون Vite اسکیم را
 * از خود درخواست می‌خواند؛ برای همین asset-url هم بازنویسی می‌شود.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (env('RAILWAY_ENVIRONMENT') !== null || env('FORCE_HTTPS') === '1') {
            URL::forceScheme('https');
            // دارایی‌های Vite آدرسشان از اسکیم خودِ درخواست ساخته می‌شود؛
            // با این هک به asset() وصل می‌شوند که اسکیم اجباری را رعایت می‌کند
            Vite::createAssetPathsUsing(fn (string $path, ?bool $secure = null): string => asset($path, $secure));
        }

        return $next($request);
    }
}
