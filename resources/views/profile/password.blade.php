@extends('layouts.app')

@section('title', __('تغییر رمز عبور'))

@section('content')
<div class="mx-auto max-w-md" data-reveal>
    <div class="glass-card glow-border relative overflow-hidden p-8">
        <div class="pointer-events-none absolute -top-16 -start-16 h-40 w-40 animate-float rounded-full bg-violet-400/25 blur-3xl"></div>

        <div class="relative mb-6 text-center">
            <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-3xl bg-gradient-to-br from-violet-500 via-fuchsia-500 to-cyan-400 text-3xl text-white shadow-glow">🔑</div>
            <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('تغییر رمز عبور') }}</h1>
            @if ($mustChange)
                <p class="mt-2 rounded-2xl border border-amber-400/40 bg-amber-500/10 p-3 text-sm font-bold text-amber-600 dark:text-amber-400">
                    ⚠️ {{ __('به دلایل امنیتی باید رمز عبور خود را تغییر دهید تا به ادامه دسترسی داشته باشید.') }}
                </p>
            @endif
        </div>

        <form method="POST" action="{{ route('profile.password.update') }}" class="relative space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="glass-label" for="current_password">{{ __('رمز فعلی') }}</label>
                <input id="current_password" name="current_password" type="password" required class="glass-input" autocomplete="current-password">
                @error('current_password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password">{{ __('رمز جدید (حداقل ۸ کاراکتر)') }}</label>
                <input id="password" name="password" type="password" required class="glass-input" autocomplete="new-password">
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="glass-label" for="password_confirmation">{{ __('تکرار رمز جدید') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="glass-input" autocomplete="new-password">
            </div>

            <button class="btn-primary w-full">{{ __('تغییر رمز عبور') }}</button>
        </form>
    </div>
</div>
@endsection
