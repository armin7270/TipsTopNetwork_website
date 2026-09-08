@extends('layouts.app')

@section('title', __('بازیابی رمز عبور'))

@section('content')
<div class="mx-auto max-w-md" data-reveal>
    <div class="glass-card rainbow-ring relative overflow-hidden p-8">
        <div class="pointer-events-none absolute -top-10 -start-10 animate-float" aria-hidden="true"><span class="icon-chip chip-cyan h-20 w-20 text-3xl">🔐</span></div>
        <div class="pointer-events-none absolute -bottom-8 -end-8 animate-float-slow" aria-hidden="true"><span class="icon-chip chip-pink h-16 w-16 text-2xl">📩</span></div>

        <div class="relative mb-8 text-center">
            <div class="icon-chip chip-rainbow mx-auto mb-4 h-20 w-20 animate-pulse-glow text-4xl">
                <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('فراموشی رمز عبور') }}</h1>
            <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('شماره موبایل حساب خود را وارد کنید تا کد بازیابی پیامک شود') }}</p>
        </div>

        <form method="POST" action="{{ route('password.send-otp') }}" class="relative space-y-5">
            @csrf

            <div>
                <label class="glass-label" for="phone">{{ __('شماره موبایل') }}</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required placeholder="09123456789" class="glass-input !rounded-2xl text-start" dir="ltr">
                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <button class="btn-primary w-full !py-3.5 !text-base">{{ __('ارسال کد بازیابی') }} 📲</button>

            <p class="text-center text-sm font-bold text-slate-500 dark:text-slate-400">
                <a href="{{ route('login') }}" class="font-black text-pink-600 hover:underline dark:text-pink-400">{{ __('بازگشت به ورود') }}</a>
            </p>
        </form>
    </div>
</div>
@endsection
