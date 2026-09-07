<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendTelegramBroadcast;
use App\Models\Setting;
use App\Models\User;
use App\Services\AdminLog;
use App\Services\Telegram\TelegramClient;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * برودکست (پیام همگانی) ربات تلگرام — سمت مدیر
 */
class BroadcastController extends Controller
{
    public function index(TelegramClient $telegram): View
    {
        $lastBroadcast = Setting::get('tg_last_broadcast_at');

        return view('admin.broadcast', [
            'usersWithTelegram' => User::query()->whereNotNull('telegram_chat_id')->where('status', 'active')->count(),
            'lastBroadcast' => $lastBroadcast ? Format::date(Carbon::parse($lastBroadcast), false) : null,
            'botEnabled' => Setting::get('tg_bot_enabled', '0') === '1' && $telegram->isConfigured(),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ], [
            'message.required' => __('متن پیام الزامی است.'),
        ]);

        SendTelegramBroadcast::dispatch(nl2br(e($validated['message'])));

        AdminLog::record($request->user(), 'broadcast_sent', null, mb_substr($validated['message'], 0, 120));

        return back()->with('success', __('پیام همگانی در صف ارسال قرار گرفت. به‌تدریج برای همه کاربران ارسال می‌شود.'));
    }
}
