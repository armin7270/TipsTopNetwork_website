@extends('layouts.app')

@section('title', __('ثبت‌نام'))

@section('content')
<div class="mx-auto max-w-md" data-reveal>
    <div class="glass-card rainbow-ring relative overflow-hidden p-8">
        <div class="pointer-events-none absolute -top-10 -end-10 animate-float" aria-hidden="true"><span class="icon-chip chip-orange h-20 w-20 text-3xl">🎉</span></div>
        <div class="pointer-events-none absolute -bottom-8 -start-8 animate-float-slow" aria-hidden="true"><span class="icon-chip chip-green h-16 w-16 text-2xl">🌟</span></div>

        <div class="relative mb-8 text-center">
            <div class="icon-chip chip-rainbow mx-auto mb-4 h-20 w-20 animate-pulse-glow text-4xl">
                <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('ساخت حساب جدید') }}</h1>
            <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('کمتر از یک دقیقه — رایگان!') }}</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="relative space-y-5">
            @csrf
            @if (! empty($referralCode))
                <input type="hidden" name="ref" value="{{ $referralCode }}">
                <div class="animate-pop-in rounded-2xl border border-pink-400/40 bg-pink-500/10 p-3 text-center text-sm font-black text-pink-600 dark:text-pink-400">
                    🎁 {{ __('کد معرف اعمال شد:') }} <span dir="ltr">{{ $referralCode }}</span>
                </div>
            @endif

            <div>
                <label class="glass-label" for="name">{{ __('نام و نام خانوادگی') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="glass-input !rounded-2xl">
                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="phone">{{ __('شماره موبایل') }}</label>
                <input id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone') }}" required dir="ltr" placeholder="09123456789" class="glass-input !rounded-2xl text-start">
                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password">{{ __('رمز عبور (حداقل ۸ کاراکتر)') }}</label>
                <input id="password" name="password" type="password" required class="glass-input !rounded-2xl">
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password_confirmation">{{ __('تکرار رمز عبور') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="glass-input !rounded-2xl">
            </div>

            <button class="btn-primary w-full !py-3.5 !text-base">{{ __('ثبت‌نام') }} ✨</button>

            <p class="text-center text-sm font-bold text-slate-500 dark:text-slate-400">
                {{ __('قبلاً ثبت‌نام کرده‌اید؟') }}
                <a href="{{ route('login') }}" class="font-black text-pink-600 hover:underline dark:text-pink-400">{{ __('وارد شوید') }}</a>
            </p>
        </form>
    </div>
</div>
@endsection
