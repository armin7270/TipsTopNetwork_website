@extends('layouts.app')

@section('title', __('ثبت رسید شارژ'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('ثبت رسید شارژ کیف پول') }}</h1>

<div class="glass-card mt-6 p-7">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <b class="text-slate-800 dark:text-white">{{ __('مبلغ قابل پرداخت') }}</b>
        <span class="text-xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($transaction->amount) }} {{ __('تومان') }}</span>
    </div>

    @if ($transaction->method === 'crypto')
        <div class="animate-pop-in mt-6 rounded-3xl border border-fuchsia-400/30 bg-fuchsia-500/5 p-6 text-center">
            <div class="text-4xl">₿</div>
            <h2 class="mt-3 font-black text-slate-800 dark:text-white">{{ __('پرداخت کریپتو') }}</h2>
            <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ __('برای تکمیل شارژ، روی دکمه زیر بزنید و در صفحه NOWPayments با ارز دیجیتال پرداخت کنید. بعد از تایید شبکه، کیف پول شما خودکار شارژ می‌شود.') }}</p>
            @if (! empty($transaction->meta['np_invoice_url']))
                <a href="{{ $transaction->meta['np_invoice_url'] }}" target="_blank" rel="noopener" class="btn-primary mt-5">{{ __('ادامه پرداخت در NOWPayments') }}</a>
            @endif
            <p class="mt-3 text-xs text-slate-400">{{ __('اگر پرداخت را انجام داده‌اید، چند دقیقه صبر کنید و صفحه کیف پول را رفرش کنید.') }}</p>
            <a href="{{ route('wallet.index') }}" class="btn-ghost mt-4">{{ __('بازگشت به کیف پول') }}</a>
        </div>
    @else
    @if ($cards)
        <h2 class="mt-6 font-black text-slate-800 dark:text-white">{{ __('واریز به کارت‌های زیر:') }}</h2>
        <div class="mt-3 space-y-3">
            @foreach ($cards as $card)
                <div class="rounded-2xl border border-slate-900/10 bg-white/40 p-4 text-sm dark:border-white/10 dark:bg-white/[0.04]">
                    <div class="flex flex-wrap justify-between gap-2">
                        <span><b>{{ $card['bank'] ?? '-' }}</b> — {{ __('به نام') }} {{ $card['holder'] ?? '-' }}</span>
                        <b dir="ltr" class="select-all text-lg text-slate-800 dark:text-white">{{ $card['number'] ?? '-' }}</b>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="mt-4 text-sm text-amber-600 dark:text-amber-400">{{ __('شماره کارت‌ها هنوز ثبت نشده است. با پشتیبانی تماس بگیرید.') }}</p>
    @endif

    <h2 class="mt-8 font-black text-slate-800 dark:text-white">{{ __('اطلاعات رسید') }}</h2>
    <form method="POST" action="{{ route('wallet.deposit.receipt', $transaction) }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
        @csrf
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('کد پیگیری / شماره ارجاع') }}</label>
            <input type="text" name="bank_reference" value="{{ old('bank_reference') }}" required class="glass-input w-full" dir="ltr">
            @error('bank_reference')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('زمان پرداخت') }}</label>
            <input type="datetime-local" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" required class="glass-input w-full" dir="ltr">
            @error('paid_at')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('عکس یا فایل رسید (اختیاری — JPG/PNG/WEBP/PDF حداکثر ۴ مگابایت)') }}</label>
            <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf" class="glass-input w-full">
            @error('receipt')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <button class="btn-primary w-full sm:w-auto">{{ __('ثبت رسید و ارسال برای تایید') }}</button>
            <a href="{{ route('wallet.index') }}" class="btn-ghost">{{ __('انصراف') }}</a>
        </div>
    </form>
    @endif
</div>
@endsection
