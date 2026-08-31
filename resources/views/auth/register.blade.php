@extends('layouts.app')

@section('title', __('ثبت‌نام'))

@section('content')
<div class="mx-auto max-w-md" data-reveal>
    <div class="glass-card glow-border relative overflow-hidden p-8">
        <div class="pointer-events-none absolute -top-16 -end-16 h-40 w-40 animate-float rounded-full bg-fuchsia-400/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-16 -start-16 h-40 w-40 animate-float-slow rounded-full bg-violet-400/20 blur-3xl"></div>

        <div class="relative mb-8 text-center">
            <div class="mx-auto mb-4 grid h-16 w-16 animate-pulse-glow place-items-center rounded-3xl bg-gradient-to-br from-violet-500 via-fuchsia-500 to-cyan-400 text-white shadow-glow">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('ساخت حساب جدید') }}</h1>
        </div>

        <form method="POST" action="{{ route('register') }}" class="relative space-y-5">
            @csrf
            @if (! empty($referralCode))
                <input type="hidden" name="ref" value="{{ $referralCode }}">
                <div class="animate-pop-in rounded-2xl border border-fuchsia-400/40 bg-fuchsia-500/10 p-3 text-center text-sm font-bold text-fuchsia-600 dark:text-fuchsia-400">
                    🎁 {{ __('کد معرف اعمال شد:') }} <span dir="ltr">{{ $referralCode }}</span>
                </div>
            @endif

            <div>
                <label class="glass-label" for="name">{{ __('نام و نام خانوادگی') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="glass-input">
                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="phone">{{ __('شماره موبایل') }}</label>
                <input id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone') }}" required dir="ltr" placeholder="09123456789" class="glass-input text-start">
                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password">{{ __('رمز عبور (حداقل ۸ کاراکتر)') }}</label>
                <input id="password" name="password" type="password" required class="glass-input">
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password_confirmation">{{ __('تکرار رمز عبور') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="glass-input">
            </div>

            <button class="btn-primary w-full">{{ __('ثبت‌نام') }}</button>

            <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                {{ __('قبلاً ثبت‌نام کرده‌اید؟') }}
                <a href="{{ route('login') }}" class="font-bold text-violet-600 hover:underline dark:text-violet-400">{{ __('وارد شوید') }}</a>
            </p>
        </form>
    </div>
</div>
@endsection
