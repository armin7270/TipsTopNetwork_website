@extends('layouts.app')

@section('title', __('ورود'))

@section('content')
<div class="mx-auto max-w-md" data-reveal>
    <div class="glass-card rainbow-ring relative overflow-hidden p-8">
        <div class="pointer-events-none absolute -top-10 -start-10 animate-float" aria-hidden="true"><span class="icon-chip chip-pink h-20 w-20 text-3xl">👋</span></div>
        <div class="pointer-events-none absolute -bottom-8 -end-8 animate-float-slow" aria-hidden="true"><span class="icon-chip chip-cyan h-16 w-16 text-2xl">🔑</span></div>

        <div class="relative mb-8 text-center">
            <div class="icon-chip chip-rainbow mx-auto mb-4 h-20 w-20 animate-pulse-glow text-4xl">
                <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('ورود به حساب') }}</h1>
            <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('با نام کاربری یا شماره موبایل وارد شوید') }}</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="relative space-y-5">
            @csrf

            <div>
                <label class="glass-label" for="phone">{{ __('نام کاربری / شماره موبایل') }}</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required placeholder="admin یا 09123456789" class="glass-input !rounded-2xl text-start" dir="ltr">
                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password">{{ __('رمز عبور') }}</label>
                <input id="password" name="password" type="password" required class="glass-input !rounded-2xl">
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-between text-sm font-bold text-slate-500 dark:text-slate-400">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded-full border-slate-300 bg-white/50 text-pink-500 dark:border-white/20 dark:bg-white/10"> {{ __('مرا به خاطر بسپار') }}
                </label>
                <a href="{{ route('password.request') }}" class="hover:underline">{{ __('رمز را فراموش کرده‌اید؟') }}</a>
            </div>

            <button class="btn-primary w-full !py-3.5 !text-base">{{ __('ورود') }} 🚀</button>

            <p class="text-center text-sm font-bold text-slate-500 dark:text-slate-400">
                {{ __('حساب ندارید؟') }}
                <a href="{{ route('register') }}" class="font-black text-pink-600 hover:underline dark:text-pink-400">{{ __('ثبت‌نام کنید') }}</a>
            </p>
        </form>
    </div>
</div>
@endsection
