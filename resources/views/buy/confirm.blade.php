@extends('layouts.app')

@section('title', __('تایید خرید'))

@section('content')
<div class="mx-auto max-w-lg">
    <div class="glass-card relative overflow-hidden p-8">
        <div class="pointer-events-none absolute -top-20 -end-20 h-48 w-48 rounded-full bg-indigo-400/25 blur-3xl"></div>

        <div class="relative">
            <h1 class="text-xl font-black text-slate-800 dark:text-white">{{ __('تایید خرید پلن') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('جزئیات پلن انتخابی خود را بررسی و روش پرداخت را انتخاب کنید.') }}</p>

            <div class="mt-6 rounded-3xl border border-indigo-400/20 bg-indigo-500/5 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-lg font-black text-slate-800 dark:text-white">{{ $plan->name }}</span>
                    <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($plan->price_toman) }} <span class="text-sm font-medium text-slate-500">{{ __('تومان') }}</span></span>
                </div>
                <div class="mt-3 flex flex-wrap gap-4 text-sm text-slate-500 dark:text-slate-400">
                    <span>📦 {{ __('حجم') }}: {{ $plan->volumeLabel() }}</span>
                    <span>⏳ {{ __('اعتبار') }}: {{ $plan->durationLabel() }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('buy.store', $plan) }}" class="mt-6 space-y-4">
                @csrf

                <label class="glass-card flex cursor-pointer items-center gap-4 p-4 transition hover:scale-[1.01] has-[:checked]:ring-2 has-[:checked]:ring-indigo-400/50">
                    <input type="radio" name="payment_method" value="card" checked class="h-4 w-4 text-indigo-600">
                    <span class="flex-1">
                        <span class="block font-bold text-slate-800 dark:text-white">💳 {{ __('کارت به کارت') }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('واریز به شماره کارت + ثبت رسید (تایید دستی مدیر)') }}</span>
                    </span>
                </label>

                <label class="glass-card flex cursor-pointer items-center gap-4 p-4 transition hover:scale-[1.01] has-[:checked]:ring-2 has-[:checked]:ring-indigo-400/50">
                    <input type="radio" name="payment_method" value="wallet" class="h-4 w-4 text-indigo-600">
                    <span class="flex-1">
                        <span class="block font-bold text-slate-800 dark:text-white">👛 {{ __('کیف پول') }}</span>
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('موجودی فعلی') }}: <b class="text-emerald-600 dark:text-emerald-400">{{ number_format($walletBalance) }} {{ __('تومان') }}</b></span>
                    </span>
                </label>

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('home') }}#plans" class="btn-ghost flex-1">{{ __('انصراف') }}</a>
                    <button class="btn-primary flex-[2]">{{ __('ادامه و پرداخت') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
