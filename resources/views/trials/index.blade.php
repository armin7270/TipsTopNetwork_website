@extends('layouts.app')

@section('title', __('اکانت تست'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">🧪 {{ __('اکانت تست') }}</h1>

@if ($enabled)
    <div class="glass-card mt-6 p-7">
        @if ($taken < $limit)
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('برای آشنایی با کیفیت سرویس، یک اکانت تست رایگان دریافت کنید. حجم و مدت محدود است و برای هر حساب فقط یک‌بار قابل دریافت است.') }}
            </p>
            <form method="POST" action="{{ route('trial.request') }}" class="mt-4">
                @csrf
                <button class="btn-primary">🧪 {{ __('دریافت اکانت تست رایگان') }}</button>
            </form>
        @else
            <p class="text-sm text-amber-600 dark:text-amber-400">{{ __('شما اکانت تست خود را دریافت کرده‌اید. برای خرید سرویس اصلی به صفحه پلن‌ها مراجعه کنید.') }}</p>
        @endif
    </div>
@else
    <div class="glass-card mt-6 p-7 text-slate-400">{{ __('قابلیت اکانت تست در حال حاضر غیرفعال است.') }}</div>
@endif

<h2 class="mt-10 font-black text-slate-800 dark:text-white">{{ __('اکانت‌های تست شما') }}</h2>
<div class="mt-4 space-y-3">
    @forelse ($trials as $trial)
        <div class="glass-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <b class="{{ $trial->isActive() ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                    {{ $trial->isActive() ? '✅ فعال' : '⛔️ منقضی' }} — {{ $trial->volumeLabel() }}
                </b>
                <span class="text-xs text-slate-400">{{ __('انقضا') }}: {{ \App\Support\Format::date($trial->expires_at, false) }}</span>
            </div>
            @if ($trial->config)
                <div class="mt-3 flex gap-2">
                    <input readonly value="{{ $trial->config }}" onclick="this.select()" class="glass-input text-[11px]" dir="ltr">
                    <button data-copy="{{ $trial->config }}" class="btn-ghost shrink-0 rounded-xl! px-3 py-1 text-xs">{{ __('کپی') }}</button>
                </div>
            @endif
        </div>
    @empty
        <div class="glass-card p-12 text-center text-slate-400">{{ __('هنوز اکانت تستی دریافت نکرده‌اید.') }}</div>
    @endforelse
</div>
@endsection
