@extends('admin.layout')

@section('title', __('تایید پرداخت‌ها'))

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('صف تایید پرداخت‌ها') }}</h1>
    <span class="badge-yellow">{{ $orders->total() }} {{ __('مورد در انتظار') }}</span>
</div>

<div class="mt-6 space-y-4">
    @forelse ($orders as $order)
        <div class="glass-card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="font-black text-slate-800 dark:text-white">{{ __('سفارش') }} #{{ $order->id }} — {{ $order->plan_name }} @if($order->isRenewal())<span class="text-xs text-emerald-500">({{ __('تمدید سفارش') }} #{{ $order->renewal_of }})</span>@endif</div>
                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('کاربر') }}: {{ $order->user?->name }} (<span dir="ltr">{{ $order->user?->phone }}</span>)
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-xs text-slate-400">{{ __('مبلغ سفارش') }}</div>
                    <div class="text-xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($order->price_toman) }} {{ __('تومان') }}</div>
                </div>
            </div>

            <div class="mt-4 grid gap-3 rounded-2xl border border-slate-900/10 bg-white/40 p-4 text-sm sm:grid-cols-3 dark:border-white/10 dark:bg-white/[0.04]">
                <div><span class="block text-xs text-slate-400">{{ __('مبلغ واریزی کاربر') }}</span><b class="text-slate-800 dark:text-white">{{ number_format($order->paid_amount) }} {{ __('تومان') }}</b></div>
                <div><span class="block text-xs text-slate-400">{{ __('کد پیگیری') }}</span><b dir="ltr" class="text-slate-800 dark:text-white">{{ $order->bank_reference }}</b></div>
                <div><span class="block text-xs text-slate-400">{{ __('زمان واریز') }}</span><b class="text-slate-800 dark:text-white">{{ \App\Support\Format::date($order->paid_at) }}</b></div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.payments.approve', $order) }}" class="flex-1 sm:flex-none">
                    @csrf
                    <button class="btn-success w-full sm:w-auto">✅ {{ __('تایید و ساخت کانفیگ') }}</button>
                </form>
                <details class="flex-1 sm:flex-none">
                    <summary class="btn-danger inline-flex cursor-pointer list-none">❌ {{ __('رد پرداخت') }}</summary>
                    <form method="POST" action="{{ route('admin.payments.reject', $order) }}" class="mt-3 flex flex-wrap gap-2">
                        @csrf
                        <input type="text" name="note" required placeholder="{{ __('دلیل رد (نمایش به کاربر)') }}" class="glass-input flex-1">
                        <button class="btn-danger">{{ __('ثبت رد') }}</button>
                    </form>
                </details>
                <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-bold text-slate-500 hover:text-indigo-600 dark:text-slate-400">{{ __('جزئیات سفارش') }}</a>
            </div>
        </div>
    @empty
        <div class="glass-card p-12 text-center text-slate-400">🎉 {{ __('پرداخت در انتظار تاییدی وجود ندارد.') }}</div>
    @endforelse
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection