@extends('layouts.app')

@section('title', __('داشبورد'))

@section('content')
<h1 data-reveal class="text-2xl font-black text-slate-800 dark:text-white">{{ __('داشبورد') }}</h1>

{{-- دسترسی سریع: کیف پول، دعوت، تیکت، نوتیفیکیشن، اکانت تست --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <a data-reveal href="{{ route('wallet.index') }}" class="glass-card lift glow-border group p-5" data-spotlight>
        <div class="flex items-center gap-2.5 text-xs font-black text-slate-400"><span class="icon-chip chip-green h-9 w-9 text-base">👛</span>{{ __('کیف پول') }}</div>
        <div class="mt-2 text-xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format(auth()->user()->balance) }} <span class="text-xs font-medium text-slate-400">{{ __('تومان') }}</span></div>
        <div class="mt-2 text-xs font-black text-pink-600 dark:text-pink-400">{{ __('شارژ / تراکنش‌ها') }} ←</div>
    </a>
    <a data-reveal style="--reveal-delay: 80ms" href="{{ route('referrals.index') }}" class="glass-card lift glow-border group p-5" data-spotlight>
        <div class="flex items-center gap-2.5 text-xs font-black text-slate-400"><span class="icon-chip chip-pink h-9 w-9 text-base">🎁</span>{{ __('دعوت از دوستان') }}</div>
        <div class="mt-2 text-sm font-black text-slate-800 dark:text-white">{{ __('پاداش نقدی بگیرید!') }}</div>
        <div class="mt-2 text-xs font-black text-pink-600 dark:text-pink-400">{{ __('لینک معرفی شما') }} ←</div>
    </a>
    <a data-reveal style="--reveal-delay: 160ms" href="{{ route('notifications.index') }}" class="glass-card lift glow-border group p-5" data-spotlight>
        <div class="flex items-center gap-2.5 text-xs font-black text-slate-400"><span class="icon-chip chip-yellow h-9 w-9 text-base">🔔</span>{{ __('نوتیفیکیشن‌ها') }}</div>
        <div class="mt-2 text-sm font-black text-slate-800 dark:text-white">
            {{ auth()->user()->notifications()->unread()->count() ? auth()->user()->notifications()->unread()->count().' '.__('خوانده‌نشده') : __('همه خوانده شده') }}
        </div>
        <div class="mt-2 text-xs font-black text-pink-600 dark:text-pink-400">{{ __('مشاهده') }} ←</div>
    </a>
    @if (\App\Models\Setting::get('trial_enabled', '0') === '1' && auth()->user()->trial_accounts_taken < max(1, (int) \App\Models\Setting::get('trial_limit_per_user', 1)))
        <a data-reveal style="--reveal-delay: 240ms" href="{{ route('trial.index') }}" class="glass-card lift glow-border group p-5" data-spotlight>
            <div class="flex items-center gap-2.5 text-xs font-black text-slate-400"><span class="icon-chip chip-cyan h-9 w-9 text-base">🧪</span>{{ __('اکانت تست') }}</div>
            <div class="mt-2 text-sm font-black text-slate-800 dark:text-white">{{ __('هنوز دریافت نکرده‌اید!') }}</div>
            <div class="mt-2 text-xs font-black text-pink-600 dark:text-pink-400">{{ __('دریافت رایگان') }} ←</div>
        </a>
    @else
        <a data-reveal style="--reveal-delay: 240ms" href="{{ route('tickets.create') }}" class="glass-card lift glow-border group p-5" data-spotlight>
            <div class="flex items-center gap-2.5 text-xs font-black text-slate-400"><span class="icon-chip chip-violet h-9 w-9 text-base">🎧</span>{{ __('پشتیبانی') }}</div>
            <div class="mt-2 text-sm font-black text-slate-800 dark:text-white">{{ __('سوال یا مشکل دارید؟') }}</div>
            <div class="mt-2 text-xs font-black text-pink-600 dark:text-pink-400">{{ __('ثبت تیکت') }} ←</div>
        </a>
    @endif
</div>

@if ($activeOrder)
    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div data-reveal class="glass-card glow-border p-7 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-black text-slate-800 dark:text-white">{{ __('لینک اشتراک شما (Subscription)') }}</h2>
                <span class="badge-green">{{ __('فعال تا') }} {{ \App\Support\Format::date($activeOrder->expires_at, false) }}</span>
            </div>

            <div class="mt-4 flex gap-2">
                <input readonly value="{{ $subscriptionUrl }}" onclick="this.select()" class="glass-input text-start" dir="ltr">
                <button data-copy="{{ $subscriptionUrl }}" class="btn-primary shrink-0">{{ __('کپی لینک اشتراک') }}</button>
            </div>

            <div class="mt-7 grid gap-7 sm:grid-cols-2">
                <img src="{{ $subscriptionQr }}" alt="{{ __('QR اشتراک') }}" class="mx-auto w-44 animate-float-slow rounded-2xl bg-white p-3 shadow-lg">
                <ul class="space-y-3 text-sm">
                    <li class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">{{ __('مصرف‌شده') }}:</span><b class="text-slate-800 dark:text-white">{{ $activeOrder->usedLabel() }}</b></li>
                    <li class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">{{ __('کل حجم') }}:</span><b class="text-slate-800 dark:text-white">{{ $activeOrder->totalLabel() }}</b></li>
                    <li class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">{{ __('باقی‌مانده') }}:</span><b class="text-emerald-600 dark:text-emerald-400">{{ $activeOrder->remainingLabel() }}</b></li>
                    <li class="flex justify-between"><span class="text-slate-500 dark:text-slate-400">{{ __('روزهای باقی‌مانده') }}:</span><b class="text-slate-800 dark:text-white">{{ $activeOrder->daysLeft() }} {{ __('روز') }}</b></li>
                </ul>
                <div class="sm:col-span-2">
                    <div class="progress-neon">
                        <div style="width: {{ $activeOrder->usagePercent() }}%"></div>
                    </div>
                </div>
            </div>

            <a href="{{ route('home') }}#plans" class="btn-ghost mt-6">{{ __('تمدید / شارژ اشتراک') }}</a>
        </div>

        <div data-reveal style="--reveal-delay: 120ms" class="glass-card p-7">
            <h2 class="font-black text-slate-800 dark:text-white">{{ __('کانفیگ‌های شما') }}</h2>
            @if (count($configs))
                <div class="mt-5 space-y-3">
                    @foreach ($configs as $config)
                        <div class="rounded-2xl border border-slate-900/10 bg-white/40 p-3 dark:border-white/10 dark:bg-white/[0.04]">
                            <div class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ $config['label'] }}</div>
                            <div class="mt-2 flex gap-2">
                                <input readonly value="{{ $config['uri'] }}" onclick="this.select()" class="w-full rounded-xl border border-slate-900/10 bg-white/50 px-2 py-1.5 text-[11px] text-slate-400 outline-none dark:border-white/10 dark:bg-white/[0.04]" dir="ltr">
                                <button data-copy="{{ $config['uri'] }}" class="btn-ghost shrink-0 rounded-xl! px-3 py-1 text-xs">{{ __('کپی') }}</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ __('کانفیگی برای نمایش نیست. اگر پرداخت شما تایید شده و این پیام را می‌بینید، از پشتیبانی کمک بگیرید.') }}</p>
            @endif
        </div>
    </div>
@else
    <div data-reveal class="glass-card glow-border mt-6 p-14 text-center">
        <div class="mx-auto mb-5 grid h-16 w-16 animate-float place-items-center rounded-3xl bg-gradient-to-br from-violet-500 via-fuchsia-500 to-cyan-400 text-3xl text-white shadow-glow">🚀</div>
        <p class="text-slate-500 dark:text-slate-400">{{ __('هنوز اشتراک فعالی ندارید.') }}</p>
        <a href="{{ route('home') }}#plans" class="btn-primary mt-6">{{ __('مشاهده پلن‌ها') }}</a>
    </div>
@endif

<h2 data-reveal class="mt-10 font-black text-slate-800 dark:text-white">{{ __('آخرین سفارش‌ها') }}</h2>
<div data-reveal class="glass-card mt-4 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>{{ __('پلن') }}</th>
                <th>{{ __('قیمت') }}</th>
                <th>{{ __('وضعیت') }}</th>
                <th>{{ __('تاریخ') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="transition hover:bg-violet-500/5">
                    <td>{{ $order->plan_name }}</td>
                    <td>{{ number_format($order->price_toman) }} {{ __('تومان') }}</td>
                    <td><span class="{{ $order->statusColor() === 'green' ? 'badge-green' : ($order->statusColor() === 'yellow' ? 'badge-yellow' : 'badge-red') }}">{{ $order->statusLabel() }}</span></td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($order->created_at, false) }}</td>
                    <td><a href="{{ route('orders.show', $order) }}" class="font-bold text-violet-600 hover:underline dark:text-violet-400">{{ __('جزئیات') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">{{ __('سفارشی ثبت نشده است.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
