@extends('admin.layout')

@section('title', __('شارژهای کیف پول'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('صف تایید شارژ کیف پول') }}</h1>

<form method="GET" action="{{ route('admin.wallet-deposits.index') }}" class="glass-card mt-4 flex flex-wrap gap-2 p-3">
    <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('جستجو: شناسه، نام/موبایل کاربر...') }}" class="glass-input flex-1">
    <button class="btn-ghost">{{ __('جستجو') }}</button>
    <a href="{{ route('admin.wallet-deposits.index', array_filter(['q' => $q, 'export' => 'csv'])) }}" class="btn-ghost">📥 {{ __('خروجی CSV') }}</a>
</form>

<div class="mt-6 space-y-4">
    @forelse ($transactions as $transaction)
        <div class="glass-card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-black text-slate-800 dark:text-white">
                        {{ __('درخواست شارژ') }} #{{ $transaction->id }} — {{ $transaction->user?->name }}
                        <span dir="ltr" class="text-xs text-slate-400">{{ $transaction->user?->phone }}</span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $transaction->statusLabel() }} — {{ \App\Support\Format::date($transaction->created_at, false) }}</div>
                </div>
                <div class="text-end">
                    <div class="text-xs text-slate-400">{{ __('مبلغ') }}</div>
                    <div class="text-xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($transaction->amount) }} {{ __('تومان') }}</div>
                </div>
            </div>

            @if (data_get($transaction->meta, 'bank_reference'))
                <div class="mt-4 grid gap-3 rounded-2xl border border-slate-900/10 bg-white/40 p-4 text-sm sm:grid-cols-2 dark:border-white/10 dark:bg-white/[0.04]">
                    <div><span class="block text-xs text-slate-400">{{ __('کد پیگیری') }}</span><b dir="ltr">{{ data_get($transaction->meta, 'bank_reference') }}</b></div>
                    <div><span class="block text-xs text-slate-400">{{ __('زمان واریز') }}</span><b>{{ data_get($transaction->meta, 'paid_at') }}</b></div>
                </div>
            @endif

            @if (data_get($transaction->meta, 'receipt_path'))
                <div class="mt-4">
                    <img src="{{ asset('storage/'.data_get($transaction->meta, 'receipt_path')) }}" alt="{{ __('رسید') }}" class="max-h-72 rounded-2xl border border-slate-900/10 dark:border-white/10">
                </div>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.wallet-deposits.approve', $transaction) }}" class="flex-1 sm:flex-none">
                    @csrf
                    <button class="btn-success w-full sm:w-auto">✅ {{ __('تایید و افزایش موجودی') }}</button>
                </form>
                <details class="flex-1 sm:flex-none">
                    <summary class="btn-danger inline-flex cursor-pointer list-none">❌ {{ __('رد درخواست') }}</summary>
                    <form method="POST" action="{{ route('admin.wallet-deposits.reject', $transaction) }}" class="mt-3 flex flex-wrap gap-2">
                        @csrf
                        <input type="text" name="note" placeholder="{{ __('دلیل رد (نمایش به کاربر)') }}" class="glass-input flex-1">
                        <button class="btn-danger">{{ __('ثبت رد') }}</button>
                    </form>
                </details>
            </div>
        </div>
    @empty
        <div class="glass-card p-12 text-center text-slate-400">🎉 {{ __('درخواست شارژ در انتظار تاییدی وجود ندارد.') }}</div>
    @endforelse
</div>

<div class="mt-4">{{ $transactions->links() }}</div>
@endsection
