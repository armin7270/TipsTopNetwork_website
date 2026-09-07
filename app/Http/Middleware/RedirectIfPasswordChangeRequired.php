<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * اجبار تعویض رمز عبور در اولین ورود مدیر (وقتی password_changed_at خالی است)
 */
class RedirectIfPasswordChangeRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin() && is_null($user->password_changed_at) && ! $request->routeIs('profile.*', 'logout')) {
            return redirect()->route('profile.password');
        }

        return $next($request);
    }
}
