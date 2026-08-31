@extends('layouts.app')

@section('title', __('ورود'))

@section('content')
<div class="mx-auto max-w-md" data-reveal>
    <div class="glass-card glow-border relative overflow-hidden p-8">
        <div class="pointer-events-none absolute -top-16 -start-16 h-40 w-40 animate-float rounded-full bg-violet-400/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-16 -end-16 h-40 w-40 animate-float-slow rounded-full bg-cyan-400/20 blur-3xl"></div>

        <div class="relative mb-8 text-center">
            <div class="mx-auto mb-4 grid h-16 w-16 animate-pulse-glow place-items-center rounded-3xl bg-gradient-to-br from-violet-500 via-fuchsia-500 to-cyan-400 text-white shadow-glow">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('ورود به حساب') }}</h1>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('با نام کاربری یا شماره موبایل وارد شوید') }}</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="relative space-y-5">
            @csrf

            <div>
                <label class="glass-label" for="phone">{{ __('نام کاربری / شماره موبایل') }}</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required placeholder="admin یا 09123456789" class="glass-input text-start" dir="ltr">
                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password">{{ __('رمز عبور') }}</label>
                <input id="password" name="password" type="password" required class="glass-input">
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 bg-white/50 text-violet-600 dark:border-white/20 dark:bg-white/10"> {{ __('مرا به خاطر بسپار') }}
            </label>

            <button class="btn-primary w-full">{{ __('ورود') }}</button>

            <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                {{ __('حساب ندارید؟') }}
                <a href="{{ route('register') }}" class="font-bold text-violet-600 hover:underline dark:text-violet-400">{{ __('ثبت‌نام کنید') }}</a>
            </p>
        </form>
    </div>
</div>
@endsection
