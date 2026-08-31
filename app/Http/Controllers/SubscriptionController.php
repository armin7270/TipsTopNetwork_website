<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Vpn\SubscriptionService;
use Illuminate\Http\Response;

class SubscriptionController extends Controller
{
    public function show(string $code): Response
    {
        $user = User::query()->where('subscription_code', $code)->firstOrFail();

        try {
            $content = app(SubscriptionService::class)->contentFor($user);
        } catch (\Throwable) {
            // کاربر فعلاً کانفیگ فعالی ندارد — بدنه خالی به‌جای خطا (اپ‌های VPN خطا را به‌عنوان خرابی لینک نشان می‌دهند)
            $content = '';
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="tipstop"',
            'Profile-Update-Interval' => '6',
        ]);
    }
}
