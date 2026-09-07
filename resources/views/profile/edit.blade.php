@extends('layouts.app')

@section('title', __('پروفایل'))

@section('content')
<div class="mx-auto max-w-lg" data-reveal>
    <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('پروفایل من') }}</h1>

    <div class="glass-card glow-border mt-6 p-7">
        <h2 class="font-black text-slate-800 dark:text-white">{{ __('مشخصات') }}</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="glass-label" for="name">{{ __('نام و نام خانوادگی') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="glass-input">
                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="glass-label">{{ __('شماره موبایل') }}</label>
                    <input type="text" value="{{ $user->phone }}" readonly dir="ltr" class="glass-input text-start opacity-60">
                </div>
                <div>
                    <label class="glass-label">{{ __('موجودی کیف پول') }}</label>
                    <input type="text" value="{{ $user->balanceLabel() }}" readonly class="glass-input opacity-60">
                </div>
            </div>
            <button class="btn-primary">{{ __('ذخیره مشخصات') }}</button>
        </form>

        <div class="mt-6 border-t border-dashed border-slate-900/10 pt-5 dark:border-white/10">
            <a href="{{ route('profile.password') }}" class="btn-ghost w-full">🔑 {{ __('تغییر رمز عبور') }}</a>
        </div>
    </div>

    <div class="glass-card mt-5 border-red-400/30 p-7">
        <h2 class="font-black text-red-600 dark:text-red-400">{{ __('حذف حساب کاربری') }}</h2>
        <p class="mt-2 text-xs leading-6 text-slate-500 dark:text-slate-400">{{ __('با حذف حساب، ورود شما غیرفعال می‌شود ولی سوابق سفارش‌ها و تراکنش‌ها نزد ما می‌ماند. برای بازگشت باید با پشتیبانی تماس بگیرید.') }}</p>
        <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 space-y-3" onsubmit="return confirm('{{ __('حساب شما غیرفعال شود؟') }}')">
            @csrf
            @method('DELETE')
            <input type="password" name="password" required placeholder="{{ __('رمز عبور برای تایید') }}" class="glass-input">
            @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            <button class="btn-danger w-full">{{ __('غیرفعال‌سازی حساب من') }}</button>
        </form>
    </div>
</div>
@endsection
