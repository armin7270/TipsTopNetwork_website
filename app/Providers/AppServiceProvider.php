<?php

namespace App\Providers;

use App\Events\OrderPaid;
use App\Listeners\RewardReferrerListener;
use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->input('phone').'|'.$request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
            ];
        });

        RateLimiter::for('sub', function (Request $request) {
            return [
                Limit::perMinute(60)->by($request->ip()),
            ];
        });

        View::share('siteName', Setting::get('site_name', 'TipStop Network'));
        View::share('supportTelegram', Setting::get('support_telegram', ''));

        // رویدادها و لیسنرها (مشابه vPanel)
        Event::listen(
            OrderPaid::class,
            RewardReferrerListener::class,
        );
    }
}
