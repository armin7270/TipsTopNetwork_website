<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'fa'));

        if (in_array($locale, ['fa', 'en'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
