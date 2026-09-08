@extends('layouts.app')

@section('title', __('تعیین رمز جدید'))

@section('content')
<div class="mx-auto max-w-md" data-reveal>
    <div class="glass-card rainbow-ring relative overflow-hidden p-8">
        <div class="pointer-events-none absolute -top-10 -start-10 animate-float" aria-hidden="true"><span class="icon-chip chip-cyan h-20 w-20 text-3xl">🔑</span></div>
        <div class="pointer-events-none absolute -bottom-8 -end-8 animate-float-slow" aria-hidden="true"><span class="icon-chip chip-cyan h-16 w-16 text-2xl">✅</span></div>

        <div class="relative mb-8 text-center">
            <div class="icon-chip chip-rainbow mx-auto mb-4 h-20 w-20 animate-pulse-glow text-4xl">
                <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('تعیین رمز عبور جدید') }}</h1>
            <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('کد ۶ رقمی ارسال‌شده به :phone را وارد کنید', ['phone' => $phone]) }}</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="relative space-y-5">
            @csrf

            <div>
                <label class="glass-label" for="code">{{ __('کد بازیابی') }}</label>
                <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" required placeholder="۱۲۳۴۵۶" class="glass-input !rounded-2xl text-center !text-lg tracking-[0.4em]" dir="ltr">
                @error('code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password">{{ __('رمز عبور جدید') }}</label>
                <input id="password" name="password" type="password" required class="glass-input !rounded-2xl">
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password_confirmation">{{ __('تکرار رمز عبور جدید') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="glass-input !rounded-2xl">
            </div>

            <button class="btn-primary w-full !py-3.5 !text-base">{{ __('تغییر رمز عبور') }} 🔓</button>

            <p class="text-center text-sm font-bold text-slate-500 dark:text-slate-400">
                <a href="{{ route('password.request') }}" class="font-black text-pink-600 hover:underline dark:text-pink-400">{{ __('دریافت مجدد کد') }}</a>
            </p>
        </form>
    </div>
</div>
@endsection
