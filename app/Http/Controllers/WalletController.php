<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Transaction;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * کیف پول کاربر: موجودی، شارژ (کارت به کارت)، تراکنش‌ها
 */
class WalletController extends Controller
{
    public function __construct(protected WalletService $wallet) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('wallet.index', [
            'transactions' => $user->transactions()->paginate(15),
            'minDeposit' => max(1000, (int) Setting::get('wallet_min_deposit', 10000)),
            'pendingDeposit' => $user->transactions()
                ->where('type', Transaction::TYPE_DEPOSIT)
                ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION])
                ->first(),
        ]);
    }

    /**
     * ثبت درخواست شارژ کیف پول (کارت به کارت)
     */
    public function charge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ], [
            'amount.required' => __('مبلغ شارژ الزامی است.'),
        ]);

        try {
            $transaction = $this->wallet->createDeposit($request->user(), (int) $validated['amount'], 'card');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('wallet.deposit', $transaction)
            ->with('success', __('درخواست شارژ ثبت شد. لطفاً مبلغ را واریز و رسید را ثبت کنید.'));
    }

    /**
     * صفحه ثبت رسید شارژ
     */
    public function deposit(Request $request, Transaction $transaction): View
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);
        abort_unless($transaction->type === Transaction::TYPE_DEPOSIT, 404);
        abort_unless(in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION], true), 404);

        return view('wallet.deposit', [
            'transaction' => $transaction,
            'cards' => Setting::getJson('cards', []),
        ]);
    }

    /**
     * ثبت رسید شارژ (کد پیگیری + زمان پرداخت)
     */
    public function submitDepositReceipt(Request $request, Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);
        abort_unless($transaction->type === Transaction::TYPE_DEPOSIT, 404);
        abort_unless(in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION], true), 404);

        $validated = $request->validate([
            'bank_reference' => ['required', 'string', 'min:4', 'max:100'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
        ], [
            'bank_reference.required' => __('کد پیگیری/شماره ارجاع الزامی است.'),
            'paid_at.before_or_equal' => __('زمان پرداخت نمی‌تواند در آینده باشد.'),
        ]);

        $transaction->update([
            'status' => Transaction::STATUS_AWAITING_VERIFICATION,
            'meta' => array_merge($transaction->meta ?? [], $validated),
        ]);

        NotificationService::notifyAdmins(
            'wallet_deposit',
            __('رسید شارژ کیف پول ثبت شد'),
            $request->user()->name.' برای مبلغ '.number_format($transaction->amount).' تومان رسید ثبت کرد.',
            route('admin.wallet-deposits.index'),
        );

        return redirect()
            ->route('wallet.index')
            ->with('success', __('رسید شما ثبت شد. پس از تایید مدیر، موجودی کیف پول شما شارژ می‌شود.'));
    }
}
