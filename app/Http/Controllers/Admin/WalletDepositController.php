<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * شارژهای کیف پول در انتظار تایید — سمت مدیر
 */
class WalletDepositController extends Controller
{
    public function index(): View
    {
        $transactions = Transaction::query()
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION])
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.wallet-deposits', [
            'transactions' => $transactions,
            'cards' => Setting::getJson('cards', []),
        ]);
    }

    public function approve(Request $request, Transaction $transaction, WalletService $wallet): RedirectResponse
    {
        abort_unless($transaction->type === Transaction::TYPE_DEPOSIT, 404);

        if (! in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION], true)) {
            return back()->with('error', __('این درخواست قابل تایید نیست.'));
        }

        $wallet->confirmDeposit($transaction, $request->user());

        return back()->with('success', __('شارژ #:id تایید و موجودی کاربر افزایش یافت. ✅', ['id' => $transaction->id]));
    }

    public function reject(Request $request, Transaction $transaction, WalletService $wallet): RedirectResponse
    {
        abort_unless($transaction->type === Transaction::TYPE_DEPOSIT, 404);

        if (! in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION], true)) {
            return back()->with('error', __('این درخواست قابل رد نیست.'));
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $wallet->rejectDeposit($transaction, $request->user(), $validated['note'] ?? null);

        return back()->with('success', __('درخواست شارژ #:id رد شد.', ['id' => $transaction->id]));
    }
}
