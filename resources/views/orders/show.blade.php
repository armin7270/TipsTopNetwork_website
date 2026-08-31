@extends('layouts.app')

@section('title', __('سفارش').' #'.$order->id)

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('سفارش') }} #{{ $order->id }}</h1>
        <span class="{{ $order->statusColor() === 'green' ? 'badge-green' : ($order->statusColor() === 'yellow' ? 'badge-yellow' : 'badge-red') }}">{{ $order->statusLabel() }}</span>
    </div>

    <div class="glass-card mt-6 p-6">
        <div class="grid grid-cols-2 gap-5 text-sm md:grid-cols-4">
            <div><span class="block text-xs text-slate-400">{{ __('پلن') }}</span><b class="text-slate-800 dark:text-white">{{ $order->plan_name }} @if($order->isRenewal())<span class="text-emerald-500">({{ __('تمدید') }})</span>@endif</b></div>
            <div><span class="block text-xs text-slate-400">{{ __('حجم') }}</span><b class="text-slate-800 dark:text-white">{{ \App\Support\Format::bytes($order->volume_gb * 1024 ** 3) }}</b></div>
            <div><span class="block text-xs text-slate-400">{{ __('اعتبار') }}</span><b class="text-slate-800 dark:text-white">{{ $order->duration_days }} {{ __('روز') }}</b></div>
            <div><span class="block text-xs text-slate-400">{{ __('قیمت') }}</span><b class="text-indigo-600 dark:text-indigo-400">{{ number_format($order->price_toman) }} {{ __('تومان') }}</b></div>
            @if ($order->isActive())
                <div><span class="block text-xs text-slate-400">{{ __('شروع') }}</span><b class="text-slate-800 dark:text-white">{{ \App\Support\Format::date($order->starts_at, false) }}</b></div>
                <div><span class="block text-xs text-slate-400">{{ __('انقضا') }}</span><b class="text-slate-800 dark:text-white">{{ \App\Support\Format::date($order->expires_at, false) }}</b></div>
                <div><span class="block text-xs text-slate-400">{{ __('مصرف') }}</span><b class="text-slate-800 dark:text-white">{{ $order->usedLabel() }} / {{ $order->totalLabel() }}</b></div>
            @endif
        </div>
        @if ($order->admin_note)
            <p class="mt-4 rounded-2xl border border-slate-900/10 bg-white/40 p-3 text-xs leading-6 text-slate-500 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-400">{{ __('یادداشت پشتیبانی') }}: {{ $order->admin_note }}</p>
        @endif
    </div>

    @if (in_array($order->status, [\App\Models\Order::STATUS_PENDING_PAYMENT, \App\Models\Order::STATUS_REJECTED], true))
        <div class="glass-card mt-6 p-6">
            <h2 class="font-black text-slate-800 dark:text-white">💳 {{ __('پرداخت آنی با کیف پول') }}</h2>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <span class="badge-green">{{ __('موجودی شما') }}: {{ number_format(auth()->user()->balance) }} {{ __('تومان') }}</span>
                @if (auth()->user()->balance >= $order->price_toman)
                    <form method="POST" action="{{ route('orders.pay-wallet', $order) }}">
                        @csrf
                        <button class="btn-success">⚡️ {{ __('پرداخت و فعال‌سازی آنی') }}</button>
                    </form>
                @else
                    <a href="{{ route('wallet.index') }}" class="btn-ghost">{{ __('موجودی کافی نیست — شارژ کیف پول') }}</a>
                @endif
            </div>
            @if ($order->payment_method === 'wallet' && $order->status === \App\Models\Order::STATUS_PENDING_PAYMENT)
                <p class="mt-2 text-xs text-slate-400">{{ __('نکته: پرداخت قبلی با کیف پول ناموفق بوده؛ دوباره تلاش کنید.') }}</p>
            @endif
        </div>

        <div class="glass-card mt-6 border-amber-400/40 p-7">
            <h2 class="font-black text-amber-600 dark:text-amber-400">{{ __('مرحله ۱: واریز مبلغ') }} {{ number_format($order->price_toman) }} {{ __('تومان') }}</h2>

            @if (count($cards))
                <div class="mt-4 space-y-3">
                    @foreach ($cards as $card)
                        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-900/10 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[0.04]">
                            <div class="flex-1">
                                <div class="text-xs text-slate-400">{{ $card['bank'] }} — {{ __('به نام') }} {{ $card['holder'] }}</div>
                                <div dir="ltr" class="mt-1 font-mono text-lg tracking-widest text-slate-800 dark:text-white">{{ $card['number'] }}</div>
                            </div>
                            <button data-copy="{{ $card['number'] }}" class="btn-primary">{{ __('کپی شماره کارت') }}</button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('شماره کارت هنوز تنظیم نشده است؛ با پشتیبانی تماس بگیرید.') }}</p>
            @endif

            <h2 class="mt-7 font-black text-amber-600 dark:text-amber-400">{{ __('مرحله ۲: ثبت رسید واریز') }}</h2>
            <form method="POST" action="{{ route('orders.receipt', $order) }}" class="mt-4 grid gap-4 sm:grid-cols-3">
                @csrf
                <div>
                    <label class="glass-label" for="paid_amount">{{ __('مبلغ واریزی (تومان)') }}</label>
                    <input id="paid_amount" name="paid_amount" type="number" value="{{ old('paid_amount', $order->price_toman) }}" required class="glass-input">
                    @error('paid_amount')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="glass-label" for="bank_reference">{{ __('کد پیگیری واریز') }}</label>
                    <input id="bank_reference" name="bank_reference" type="text" value="{{ old('bank_reference', $order->bank_reference) }}" required placeholder="123456789" class="glass-input" dir="ltr">
                    @error('bank_reference')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="glass-label" for="paid_at">{{ __('زمان واریز') }}</label>
                    <input id="paid_at" name="paid_at" type="datetime-local" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" required class="glass-input">
                    @error('paid_at')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <button class="btn-primary sm:col-span-3">{{ __('ثبت رسید و ارسال برای تایید') }}</button>
            </form>
        </div>
    @elseif ($order->status === \App\Models\Order::STATUS_AWAITING_VERIFICATION)
        <div class="glass-card mt-6 border-amber-400/40 p-7 text-sm font-bold leading-8 text-amber-600 dark:text-amber-400">
            ✅ {{ __('رسید شما ثبت شد و در صف بررسی مدیر است. به‌محض تایید، کانفیگ به‌صورت خودکار ساخته می‌شود و در داشبورد فعال می‌گردد.') }}
        </div>
    @elseif ($order->isActive())
        <div class="glass-card mt-6 border-emerald-400/40 p-7">
            <p class="text-sm font-bold leading-8 text-emerald-600 dark:text-emerald-400">🎉 {{ __('اشتراک شما فعال است! لینک اشتراک و کانفیگ‌ها را از داشبورد بردارید.') }} <a href="{{ route('dashboard') }}" class="underline">{{ __('داشبورد') }}</a></p>
        </div>
    @elseif ($order->status === \App\Models\Order::STATUS_REJECTED)
        <div class="glass-card mt-6 border-red-400/40 p-7 text-sm font-bold leading-8 text-red-600 dark:text-red-400">
            ❌ {{ __('رسید پرداخت شما رد شد. دلیل:') }} {{ $order->admin_note ?: __('نامشخص') }} — {{ __('در صورت اعتراض با پشتیبانی تماس بگیرید. می‌توانید رسید جدید ثبت کنید.') }}
        </div>
    @endif

    @if (in_array($order->status, [\App\Models\Order::STATUS_PENDING_PAYMENT, \App\Models\Order::STATUS_AWAITING_VERIFICATION, \App\Models\Order::STATUS_REJECTED], true))
        <form method="POST" action="{{ route('orders.cancel', $order) }}" class="mt-4 text-end">
            @csrf
            <button class="text-xs text-slate-400 transition hover:text-red-500">{{ __('لغو این سفارش') }}</button>
        </form>
    @endif

    <a href="{{ route('orders.index') }}" class="mt-6 inline-block text-sm text-slate-500 hover:text-indigo-600 dark:text-slate-400">{{ __('← بازگشت به سفارش‌ها') }}</a>
</div>
@endsection