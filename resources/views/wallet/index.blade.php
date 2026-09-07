@extends('layouts.app')

@section('title', __('کیف پول'))

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('کیف پول') }}</h1>
    <span class="badge-green">{{ __('موجودی') }}: {{ number_format(auth()->user()->balance) }} {{ __('تومان') }}</span>
</div>

<div class="glass-card mt-6 p-7">
    <h2 class="font-black text-slate-800 dark:text-white">{{ __('شارژ کیف پول') }}</h2>
    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('مبلغ مورد نظر را وارد کنید. حداقل شارژ :amount تومان است.', ['amount' => number_format($minDeposit)]) }}</p>

    <form method="POST" action="{{ route('wallet.charge') }}" class="mt-4 flex flex-wrap gap-2">
        @csrf
        <input type="number" name="amount" min="{{ $minDeposit }}" value="{{ $minDeposit }}" required class="glass-input flex-1" dir="ltr">
        <button class="btn-primary">{{ __('ادامه و مشاهده کارت‌ها') }}</button>
    </form>

    @if ($cryptoEnabled)
        <div class="mt-4 border-t border-dashed border-slate-900/10 pt-4 dark:border-white/10">
            <p class="text-xs text-slate-500 dark:text-slate-400">₿ {{ __('یا با ارز دیجیتال شارژ کنید (تایید خودکار):') }}</p>
            <form method="POST" action="{{ route('wallet.charge-crypto') }}" class="mt-2 flex flex-wrap gap-2">
                @csrf
                <input type="number" name="amount" min="{{ $minDeposit }}" value="{{ $minDeposit }}" required class="glass-input flex-1" dir="ltr">
                <button class="btn-ghost">₿ {{ __('پرداخت با کریپتو') }}</button>
            </form>
        </div>
    @endif
</div>

@if ($pendingDeposit)
    <div class="glass-card border-amber-400/40 mt-4 p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <b class="text-amber-600 dark:text-amber-400">{{ __('درخواست شارژ در انتظار:') }}</b>
                {{ number_format($pendingDeposit->amount) }} {{ __('تومان') }} — {{ $pendingDeposit->statusLabel() }}
            </div>
            <a href="{{ route('wallet.deposit', $pendingDeposit) }}" class="btn-ghost">{{ __('مشاهده / ثبت رسید') }}</a>
        </div>
    </div>
@endif

<h2 class="mt-10 font-black text-slate-800 dark:text-white">{{ __('تاریخچه تراکنش‌ها') }}</h2>
<div class="glass-card mt-4 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>{{ __('نوع') }}</th>
                <th>{{ __('مبلغ') }}</th>
                <th>{{ __('وضعیت') }}</th>
                <th>{{ __('توضیحات') }}</th>
                <th>{{ __('تاریخ') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->typeLabel() }}</td>
                    <td>
                        <b class="{{ $transaction->isCredit() ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $transaction->isCredit() ? '+' : '-' }}{{ number_format($transaction->amount) }}
                        </b>
                    </td>
                    <td>
                        <span class="{{ $transaction->status === \App\Models\Transaction::STATUS_COMPLETED ? 'badge-green' : ($transaction->status === \App\Models\Transaction::STATUS_FAILED ? 'badge-red' : 'badge-yellow') }}">
                            {{ $transaction->statusLabel() }}
                        </span>
                    </td>
                    <td class="text-slate-500 dark:text-slate-400">{{ $transaction->description }}</td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($transaction->created_at, false) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">{{ __('هنوز تراکنشی ثبت نشده است.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $transactions->links() }}</div>
@endsection
