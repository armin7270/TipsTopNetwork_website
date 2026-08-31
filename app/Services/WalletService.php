<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * سرویس کیف پول: ثبت تراکنش، شارژ، بازگشت وجه، تنظیم توسط مدیر
 */
class WalletService
{
    /**
     * ثبت یک تراکنش در دفتر تراکنش‌ها (بدون تغییر موجودی — تغییر موجودی جداگانه انجام می‌شود)
     */
    public function record(
        User $user,
        string $type,
        int $amount,
        string $status = Transaction::STATUS_COMPLETED,
        ?string $method = null,
        ?int $orderId = null,
        ?string $description = null,
        array $meta = [],
    ): Transaction {
        // علامت مبلغ (کسر وجه) برای نمایش صحیح جهت تراکنش حفظ می‌شود
        if ($amount < 0) {
            $meta = $meta + ['direction' => 'debit'];
        }

        return Transaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => abs($amount),
            'status' => $status,
            'method' => $method,
            'order_id' => $orderId,
            'description' => $description,
            'meta' => $meta ?: null,
            'completed_at' => $status === Transaction::STATUS_COMPLETED ? now() : null,
        ]);
    }

    /**
     * ایجاد درخواست شارژ کیف پول (پرداخت کارت به کارت — نیازمند تایید مدیر)
     */
    public function createDeposit(User $user, int $amount, string $method = 'card'): Transaction
    {
        $min = max(1000, (int) Setting::get('wallet_min_deposit', 10000));

        if ($amount < $min) {
            throw new \RuntimeException(__('حداقل مبلغ شارژ :amount تومان است.', ['amount' => number_format($min)]));
        }

        $transaction = $this->record($user, Transaction::TYPE_DEPOSIT, $amount, Transaction::STATUS_PENDING, method: $method, description: __('درخواست شارژ کیف پول'));

        if ($method === 'card') {
            $transaction->update(['status' => Transaction::STATUS_AWAITING_VERIFICATION]);

            NotificationService::notifyAdmins(
                'wallet_deposit',
                __('درخواست شارژ کیف پول جدید'),
                __('کاربر :name مبلغ :amount تومان را برای شارژ کیف پول ثبت کرد.', [
                    'name' => $user->name, 'amount' => number_format($amount),
                ]),
                route('admin.wallet-deposits.index'),
            );
        }

        return $transaction->refresh();
    }

    /**
     * تایید شارژ کیف پول توسط مدیر (افزایش موجودی)
     */
    public function confirmDeposit(Transaction $transaction, ?User $admin = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $admin) {
            if ($transaction->status === Transaction::STATUS_COMPLETED) {
                return $transaction;
            }

            $user = User::query()->whereKey($transaction->user_id)->lockForUpdate()->first();

            $user->increment('balance', $transaction->amount);

            $transaction->update([
                'status' => Transaction::STATUS_COMPLETED,
                'completed_at' => now(),
                'meta' => array_merge($transaction->meta ?? [], ['approved_by' => $admin?->id]),
            ]);

            NotificationService::send(
                $user,
                'wallet_deposit_confirmed',
                __('کیف پول شما شارژ شد ✅'),
                __('مبلغ :amount تومان به کیف پول شما اضافه شد. موجودی فعلی: :balance', [
                    'amount' => number_format($transaction->amount),
                    'balance' => number_format($user->balance),
                ]),
                route('wallet.index'),
            );

            return $transaction;
        });
    }

    /**
     * رد درخواست شارژ توسط مدیر
     */
    public function rejectDeposit(Transaction $transaction, ?User $admin = null, ?string $note = null): Transaction
    {
        $transaction->update([
            'status' => Transaction::STATUS_FAILED,
            'meta' => array_merge($transaction->meta ?? [], [
                'rejected_by' => $admin?->id,
                'note' => $note,
            ]),
        ]);

        NotificationService::send(
            $transaction->user,
            'wallet_deposit_rejected',
            __('درخواست شارژ کیف پول رد شد ❌'),
            $note ?: __('درخواست شارژ شما تایید نشد. لطفاً با پشتیبانی تماس بگیرید.'),
            route('wallet.index'),
        );

        return $transaction;
    }

    /**
     * بازگشت وجه به کیف پول (مثلاً بعد از خطای ساخت کانفیگ)
     */
    public function refund(User $user, int $amount, string $description, ?int $orderId = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $orderId) {
            User::query()->whereKey($user->id)->lockForUpdate()->increment('balance', $amount);

            $transaction = $this->record($user, Transaction::TYPE_REFUND, $amount, Transaction::STATUS_COMPLETED, method: 'wallet', orderId: $orderId, description: $description);

            NotificationService::send(
                $user,
                'wallet_refund',
                __('وجه به کیف پول شما بازگشت داده شد'),
                __('مبلغ :amount تومان به کیف پول شما برگشت خورد. (:reason)', [
                    'amount' => number_format($amount), 'reason' => $description,
                ]),
                route('wallet.index'),
            );

            return $transaction;
        });
    }

    /**
     * تنظیم دستی موجودی توسط مدیر (مبلغ مثبت = افزایش، منفی = کاهش)
     */
    public function adjust(User $user, int $amount, string $reason, ?User $admin = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $reason, $admin) {
            if ($amount >= 0) {
                User::query()->whereKey($user->id)->lockForUpdate()->increment('balance', $amount);
            } else {
                $decrement = min(abs($amount), $user->balance);
                User::query()->whereKey($user->id)->lockForUpdate()->decrement('balance', $decrement);
                $amount = -$decrement;
            }

            $transaction = $this->record(
                $user,
                Transaction::TYPE_ADMIN_ADJUSTMENT,
                $amount,
                Transaction::STATUS_COMPLETED,
                method: 'system',
                description: $reason,
                meta: ['admin_id' => $admin?->id],
            );

            NotificationService::send(
                $user,
                'wallet_adjusted',
                $amount >= 0 ? __('کیف پول شما شارژ شد') : __('از کیف پول شما کسر شد'),
                __('مبلغ :amount تومان (:reason). موجودی فعلی: :balance', [
                    'amount' => number_format(abs($amount)),
                    'reason' => $reason,
                    'balance' => number_format($user->refresh()->balance),
                ]),
                route('wallet.index'),
            );

            return $transaction;
        });
    }
}
