<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Telegram\TelegramClient;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->query('q', '');

        $users = User::query()
            ->withCount('orders')
            ->latest()
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('phone', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")))
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', ['users' => $users, 'q' => $q]);
    }

    public function toggleBlock(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('نمی‌توانید حساب خودتان را مسدود کنید.'));
        }

        $user->update(['status' => $user->isBlocked() ? 'active' : 'blocked']);

        return back()->with('success', __('وضعیت کاربر :name تغییر کرد.', ['name' => $user->name]));
    }

    /**
     * تنظیم دستی موجودی کیف پول کاربر (مشابه vPanel)
     */
    public function adjustWallet(Request $request, User $user, WalletService $wallet): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:200'],
        ], [
            'amount.required' => __('مبلغ الزامی است.'),
            'reason.required' => __('دلیل تغییر موجودی الزامی است.'),
        ]);

        $wallet->adjust($user, (int) $validated['amount'], $validated['reason'], $request->user());

        return back()->with('success', __('کیف پول :name تنظیم شد. موجودی فعلی: :balance', [
            'name' => $user->name,
            'balance' => $user->refresh()->balanceLabel(),
        ]));
    }

    /**
     * ارسال پیام تکی تلگرام به کاربر (مشابه vPanel)
     */
    public function sendTelegram(Request $request, User $user, TelegramClient $telegram): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ], [
            'message.required' => __('متن پیام الزامی است.'),
        ]);

        if (! $user->telegram_chat_id) {
            return back()->with('error', __('این کاربر ربات تلگرام را متصل نکرده است.'));
        }

        try {
            $telegram->sendMessage($user->telegram_chat_id, nl2br(e($validated['message'])));
        } catch (\Throwable $e) {
            return back()->with('error', __('خطا در ارسال پیام').': '.$e->getMessage());
        }

        return back()->with('success', __('پیام برای :name ارسال شد. ✅', ['name' => $user->name]));
    }
}
