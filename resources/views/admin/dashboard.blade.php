@extends('admin.layout')

@section('title', __('داشبورد مدیریت'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('داشبورد مدیریت') }}</h1>

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
    <div class="glass-card lift p-5">
        <div class="text-xs text-slate-400">{{ __('کاربران') }}</div>
        <div class="mt-1 text-2xl font-black text-slate-800 dark:text-white">{{ number_format($stats['users']) }}</div>
    </div>
    <div class="glass-card lift border-amber-400/40 p-5">
        <div class="text-xs text-amber-600/80 dark:text-amber-400/80">{{ __('در انتظار تایید') }}</div>
        <div class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($stats['pending_payments']) }}</div>
        <a href="{{ route('admin.payments.index') }}" class="mt-2 inline-block text-xs font-bold text-amber-600 hover:underline dark:text-amber-400">{{ __('بررسی پرداخت‌ها') }} ←</a>
    </div>
    <div class="glass-card lift p-5">
        <div class="text-xs text-slate-400">{{ __('اشتراک‌های فعال') }}</div>
        <div class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($stats['active_orders']) }}</div>
    </div>
    <div class="glass-card lift p-5">
        <div class="text-xs text-slate-400">{{ __('درآمد کل') }}</div>
        <div class="mt-1 text-2xl font-black text-slate-800 dark:text-white">{{ number_format($stats['revenue']) }} <span class="text-sm font-medium text-slate-400">{{ __('تومان') }}</span></div>
    </div>
    <div class="glass-card lift p-5">
        <div class="text-xs text-slate-400">{{ __('سفارش‌های امروز') }}</div>
        <div class="mt-1 text-2xl font-black text-slate-800 dark:text-white">{{ number_format($stats['orders_today']) }}</div>
    </div>
    <div class="glass-card lift p-5">
        <div class="text-xs text-slate-400">{{ __('درآمد این ماه') }}</div>
        <div class="mt-1 text-2xl font-black text-slate-800 dark:text-white">{{ number_format($stats['month_revenue']) }} <span class="text-sm font-medium text-slate-400">{{ __('تومان') }}</span></div>
    </div>
    <div class="glass-card lift p-5">
        <div class="text-xs text-slate-400">{{ __('تیکت‌های باز') }}</div>
        <div class="mt-1 text-2xl font-black {{ $stats['open_tickets'] ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-white' }}">{{ number_format($stats['open_tickets']) }}</div>
        <a href="{{ route('admin.tickets.index') }}" class="mt-2 inline-block text-xs font-bold text-amber-600 hover:underline dark:text-amber-400">{{ __('مشاهده تیکت‌ها') }} ←</a>
    </div>
    <div class="glass-card lift p-5">
        <div class="text-xs text-slate-400">{{ __('درخواست شارژ کیف پول') }}</div>
        <div class="mt-1 text-2xl font-black text-slate-800 dark:text-white">{{ number_format($stats['wallet_deposits']) }}</div>
        <a href="{{ route('admin.wallet-deposits.index') }}" class="mt-2 inline-block text-xs font-bold text-amber-600 hover:underline dark:text-amber-400">{{ __('بررسی شارژها') }} ←</a>
    </div>
</div>

{{-- نمودار ۳۰ روزه سفارش و درآمد (تاریخ شمسی) --}}
<div class="glass-card mt-6 p-7">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-black text-slate-800 dark:text-white">{{ __('فروش ۳۰ روز اخیر') }}</h2>
        <div class="flex items-center gap-4 text-xs">
            <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-violet-500"></span>{{ __('سفارش‌ها') }}</span>
            <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-teal-500"></span>{{ __('درآمد') }}</span>
        </div>
    </div>

    @php
        $maxCount = max(1, collect($chart)->max('count'));
        $maxRevenue = max(1, collect($chart)->max('revenue'));
    @endphp

    <div class="mt-6 flex h-40 items-end gap-[3px]" dir="ltr">
        @foreach ($chart as $day)
            <div class="group relative flex h-full flex-1 flex-col justify-end gap-[2px]" title="{{ $day['label'] }} — {{ $day['count'] }} سفارش / {{ number_format($day['revenue']) }} تومان">
                <div class="rounded-t bg-gradient-to-t from-emerald-600 to-teal-400 transition-all duration-300 group-hover:brightness-125" style="height: {{ $day['revenue'] ? max(3, round($day['revenue'] / $maxRevenue * 100)) : 0 }}%"></div>
                <div class="rounded-t bg-gradient-to-t from-violet-600 to-fuchsia-500 transition-all duration-300 group-hover:brightness-125" style="height: {{ $day['count'] ? max(3, round($day['count'] / $maxCount * 100)) : 0 }}%"></div>
            </div>
        @endforeach
    </div>
    <div class="mt-2 flex justify-between text-[10px] text-slate-400" dir="ltr">
        <span>{{ $chart[0]['label'] ?? '' }}</span>
        <span>{{ $chart[intdiv(count($chart), 2)]['label'] ?? '' }}</span>
        <span>{{ $chart[count($chart) - 1]['label'] ?? '' }}</span>
    </div>
</div>

<h2 class="mt-8 font-black text-slate-800 dark:text-white">{{ __('آخرین سفارش‌ها') }}</h2>
<div class="glass-card mt-4 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>{{ __('شناسه') }}</th>
                <th>{{ __('کاربر') }}</th>
                <th>{{ __('پلن') }}</th>
                <th>{{ __('قیمت') }}</th>
                <th>{{ __('وضعیت') }}</th>
                <th>{{ __('تاریخ') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recentOrders as $order)
                <tr class="transition hover:bg-violet-500/5">
                    <td class="text-slate-400">#{{ $order->id }}</td>
                    <td>{{ $order->user?->name }} <span dir="ltr" class="text-xs text-slate-400">{{ $order->user?->phone }}</span></td>
                    <td>{{ $order->plan_name }}</td>
                    <td>{{ number_format($order->price_toman) }}</td>
                    <td><span class="{{ $order->statusColor() === 'green' ? 'badge-green' : ($order->statusColor() === 'yellow' ? 'badge-yellow' : 'badge-red') }}">{{ $order->statusLabel() }}</span></td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($order->created_at, false) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">{{ __('سفارشی ثبت نشده است.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
