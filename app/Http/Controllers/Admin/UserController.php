<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminLog;
use App\Services\Telegram\TelegramClient;
use App\Services\WalletService;
use App\Support\CsvExport;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $q = $request->query('q', '');

        $query = User::query()
            ->withCount('orders')
            ->latest()
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('phone', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")));

        if ($request->query('export') === 'csv') {
            return CsvExport::download('users-'.now()->format('Ymd-Hi').'.csv',
                ['شناسه', 'نام', 'موبایل', 'موجودی', 'وضعیت', 'سفارش‌ها', 'عضویت'],
                $query->cursor()->map(fn (User $u) => [
                    $u->id, $u->name, $u->phone, $u->balance,
                    $u->isBlocked() ? 'مسدود' : 'فعال', $u->orders_count, Format::date($u->created_at, false),
                ])->all());
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users', ['users' => $users, 'q' => $q]);
    }

    public function toggleBlock(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('نمی‌توانید حساب خودتان را مسدود کنید.'));
        }

        $wasBlocked = $user->isBlocked();
        $user->update(['status' => $wasBlocked ? 'active' : 'blocked']);

        AdminLog::record(auth()->user(), $wasBlocked ? 'user_unblocked' : 'user_blocked', $user);

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

        AdminLog::record($request->user(), 'wallet_adjusted', $user, number_format($validated['amount']).' — '.$validated['reason']);

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

        AdminLog::record($request->user(), 'telegram_sent', $user);

        return back()->with('success', __('پیام برای :name ارسال شد. ✅', ['name' => $user->name]));
    }

    /**
     * تغییر نقش مدیریتی کاربر (فقط مدیرکل)
     */
    public function setRole(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        if ($user->id === $request->user()->id) {
            return back()->with('error', __('نمی‌توانید نقش خودتان را تغییر دهید.'));
        }

        $validated = $request->validate([
            'is_admin' => ['required', 'boolean'],
            'admin_role' => ['required', 'in:super,finance,support'],
        ]);

        $user->update([
            'is_admin' => (bool) $validated['is_admin'],
            'admin_role' => $validated['admin_role'],
        ]);

        AdminLog::record($request->user(), 'user_role_changed', $user, $user->adminRoleLabel());

        return back()->with('success', __('نقش :name به «:role» تغییر کرد.', ['name' => $user->name, 'role' => $user->adminRoleLabel()]));
    }
}
