<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * دسترسی بخش‌بندی‌شده پنل مدیریت بر اساس نقش (super همه‌چیز، finance مالی، support پشتیبانی)
 * استفاده: ->middleware('admin.section:payments,orders')
 */
class EnsureAdminSection
{
    public function handle(Request $request, Closure $next, string ...$sections): Response
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            abort(403, __('دسترسی فقط برای مدیر سیستم مجاز است.'));
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! in_array($user->admin_role, $sections, true)) {
            abort(403, __('نقش مدیریتی شما به این بخش دسترسی ندارد.'));
        }

        return $next($request);
    }
}
