<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\AdminLog;
use App\Services\WalletService;
use App\Support\CsvExport;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * شارژهای کیف پول در انتظار تایید — سمت مدیر
 */
class WalletDepositController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Transaction::query()
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION])
            ->with('user')
            ->latest()
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('id', $q)
                ->orWhereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"))));

        if ($request->query('export') === 'csv') {
            return CsvExport::download('wallet-deposits-'.now()->format('Ymd-Hi').'.csv',
                ['شناسه', 'کاربر', 'موبایل', 'مبلغ', 'روش', 'وضعیت', 'تاریخ'],
                $query->cursor()->map(fn (Transaction $t) => [
                    $t->id, $t->user?->name, $t->user?->phone, $t->amount,
                    $t->method, $t->statusLabel(), Format::date($t->created_at),
                ])->all());
        }

        $transactions = $query->paginate(20)->withQueryString();

        return view('admin.wallet-deposits', [
            'transactions' => $transactions,
            'cards' => Setting::getJson('cards', []),
            'q' => $q,
        ]);
    }

    public function approve(Request $request, Transaction $transaction, WalletService $wallet): RedirectResponse
    {
        abort_unless($transaction->type === Transaction::TYPE_DEPOSIT, 404);

        if (! in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION], true)) {
            return back()->with('error', __('این درخواست قابل تایید نیست.'));
        }

        $wallet->confirmDeposit($transaction, $request->user());

        AdminLog::record($request->user(), 'deposit_approved', $transaction, number_format($transaction->amount).' تومان');

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

        AdminLog::record($request->user(), 'deposit_rejected', $transaction, $validated['note'] ?? null);

        return back()->with('success', __('درخواست شارژ #:id رد شد.', ['id' => $transaction->id]));
    }
}
