@extends('admin.layout')

@section('title', __('سفارش').' #'.$order->id)

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('سفارش') }} #{{ $order->id }} — {{ $order->statusLabel() }}</h1>

<div class="mt-6 grid gap-5 lg:grid-cols-2">
    <div class="glass-card p-6 text-sm">
        <h2 class="mb-4 font-black text-slate-800 dark:text-white">{{ __('اطلاعات سفارش') }}</h2>
        <ul class="space-y-2.5">
            <li class="flex justify-between"><span class="text-slate-400">{{ __('کاربر') }}</span><span>{{ $order->user?->name }} (<span dir="ltr">{{ $order->user?->phone }}</span>)</span></li>
            <li class="flex justify-between"><span class="text-slate-400">{{ __('پلن') }}</span><span>{{ $order->plan_name }}</span></li>
            <li class="flex justify-between"><span class="text-slate-400">{{ __('قیمت') }}</span><span>{{ number_format($order->price_toman) }} {{ __('تومان') }}</span></li>
            <li class="flex justify-between"><span class="text-slate-400">{{ __('مبلغ واریزی') }}</span><span>{{ $order->paid_amount ? number_format($order->paid_amount).' '.__('تومان') : '-' }}</span></li>
            <li class="flex justify-between"><span class="text-slate-400">{{ __('کد پیگیری') }}</span><span dir="ltr">{{ $order->bank_reference ?: '-' }}</span></li>
            <li class="flex justify-between"><span class="text-slate-400">{{ __('زمان واریز') }}</span><span>{{ \App\Support\Format::date($order->paid_at) }}</span></li>
            <li class="flex justify-between"><span class="text-slate-400">{{ __('ایمیل کلاینت در پنل') }}</span><span dir="ltr">{{ $order->xui_email ?: '-' }}</span></li>
            <li class="flex justify-between"><span class="text-slate-400">{{ __('انقضا') }}</span><span>{{ \App\Support\Format::date($order->expires_at, false) }}</span></li>
            <li class="flex justify-between"><span class="text-slate-400">{{ __('مصرف') }}</span><span>{{ $order->usedLabel() }} / {{ $order->totalLabel() }}</span></li>
        </ul>
        @if ($order->admin_note)
            <p class="mt-4 rounded-2xl border border-slate-900/10 bg-white/40 p-3 text-xs text-slate-500 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-400">{{ $order->admin_note }}</p>
        @endif
    </div>

    <div class="glass-card p-6 text-sm">
        <h2 class="mb-4 font-black text-slate-800 dark:text-white">{{ __('اینباندهای این سفارش') }}</h2>
        @forelse ($order->inbounds as $inbound)
            <div class="mb-2 rounded-2xl border border-slate-900/10 bg-white/40 p-3 dark:border-white/10 dark:bg-white/[0.04]">
                {{ $inbound->label() }} — {{ __('اینباند پنل') }}: <span dir="ltr">{{ $inbound->xui_inbound_id }}</span>
            </div>
        @empty
            <p class="text-slate-400">{{ __('اینباندی متصل نیست.') }}</p>
        @endforelse
    </div>
</div>

<div class="mt-4">
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-slate-500 hover:text-indigo-600 dark:text-slate-400">{{ __('← بازگشت') }}</a>
</div>
@endsection