<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Transaction;
use App\Services\NotificationService;
use App\Services\Payments\NowPaymentsService;
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
            'cryptoEnabled' => NowPaymentsService::isEnabled(),
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
     * ثبت درخواست شارژ با کریپتو (ساخت فاکتور NOWPayments و هدایت به درگاه)
     */
    public function chargeCrypto(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ], [
            'amount.required' => __('مبلغ شارژ الزامی است.'),
        ]);

        try {
            $transaction = $this->wallet->createDeposit($request->user(), (int) $validated['amount'], 'crypto');

            $invoice = NowPaymentsService::createInvoice(
                $transaction->id,
                $transaction->amount,
                route('wallet.index'),
                route('wallet.deposit', $transaction),
            );

            $transaction->update(['meta' => array_merge($transaction->meta ?? [], [
                'np_invoice_id' => $invoice['invoice_id'],
                'np_invoice_url' => $invoice['url'],
            ])]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($invoice['url']);
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
            // رسید تصویری/PDF — اختیاری، حداکثر ۴ مگابایت
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ], [
            'bank_reference.required' => __('کد پیگیری/شماره ارجاع الزامی است.'),
            'paid_at.before_or_equal' => __('زمان پرداخت نمی‌تواند در آینده باشد.'),
            'receipt.mimes' => __('رسید باید عکس (JPG/PNG/WEBP) یا PDF باشد.'),
            'receipt.max' => __('حجم فایل رسید نباید بیشتر از ۴ مگابایت باشد.'),
        ]);

        $meta = array_merge($transaction->meta ?? [], $validated);

        if ($request->hasFile('receipt')) {
            $meta['receipt_path'] = $request->file('receipt')->store('receipts', 'local');
        }

        $transaction->update([
            'status' => Transaction::STATUS_AWAITING_VERIFICATION,
            'meta' => $meta,
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
