@extends('layouts.app')

@section('title', __('دعوت از دوستان'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">🎁 {{ __('دعوت از دوستان') }}</h1>

<div class="glass-card mt-6 p-7">
    <p class="text-sm text-slate-500 dark:text-slate-400">
        {{ __('لینک زیر را با دوستانتان به اشتراک بگذارید. به ازای هر ثبت‌نام موفق، هدیه خوش‌آمدگویی و بعد از اولین خرید او، پاداش نقدی دریافت می‌کنید.') }}
    </p>

    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <div class="glass-card p-5 text-center">
            <div class="text-xs text-slate-400">{{ __('تعداد دعوت‌شدگان') }}</div>
            <div class="mt-1 text-3xl font-black text-slate-800 dark:text-white">{{ number_format($stats['count']) }}</div>
        </div>
        <div class="glass-card p-5 text-center">
            <div class="text-xs text-slate-400">{{ __('مجموع پاداش دریافتی') }}</div>
            <div class="mt-1 text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($stats['earned']) }} <span class="text-sm font-medium text-slate-400">{{ __('تومان') }}</span></div>
        </div>
    </div>

    @if ($welcomeAmount || $rewardAmount)
        <div class="mt-5 rounded-2xl border border-indigo-400/30 bg-indigo-500/5 p-4 text-sm text-slate-600 dark:text-slate-300">
            @if ($welcomeAmount)
                <p>🎁 {{ __('هدیه خوش‌آمدگویی دوستان شما:') }} <b>{{ number_format($welcomeAmount) }} {{ __('تومان') }}</b></p>
            @endif
            @if ($rewardAmount)
                <p class="mt-1">💰 {{ __('پاداش اولین خرید دعوت‌شدگان:') }} <b>{{ number_format($rewardAmount) }} {{ __('تومان') }}</b></p>
            @endif
        </div>
    @endif

    <label class="mt-6 mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('لینک اختصاصی شما') }}</label>
    <div class="flex gap-2">
        <input readonly value="{{ $link }}" onclick="this.select()" class="glass-input flex-1" dir="ltr">
        <button data-copy="{{ $link }}" class="btn-primary shrink-0">{{ __('کپی لینک') }}</button>
    </div>

    <label class="mt-4 mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('کد معرفی') }}</label>
    <div class="flex gap-2">
        <input readonly value="{{ $code }}" onclick="this.select()" class="glass-input flex-1 text-center font-black tracking-widest" dir="ltr">
        <button data-copy="{{ $code }}" class="btn-ghost shrink-0">{{ __('کپی کد') }}</button>
    </div>
</div>

<h2 class="mt-10 font-black text-slate-800 dark:text-white">{{ __('دعوت‌شدگان شما') }}</h2>
<div class="glass-card mt-4 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>{{ __('نام') }}</th>
                <th>{{ __('تاریخ ثبت‌نام') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($referrals as $referral)
                <tr>
                    <td>{{ $referral->name }}</td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($referral->created_at, false) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="px-4 py-8 text-center text-slate-400">{{ __('هنوز کسی با لینک شما ثبت‌نام نکرده است.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
