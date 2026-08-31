@extends('admin.layout')

@section('title', __('برودکست تلگرام'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">📣 {{ __('پیام همگانی تلگرام') }}</h1>

@if (! $botEnabled)
    <div class="glass-card border-amber-400/40 mt-6 p-5 text-sm text-amber-600 dark:text-amber-400">
        {{ __('ربات تلگرام غیرفعال است. ابتدا از بخش تنظیمات، توکن ربات را وارد و ربات را فعال کنید. آدرس وبهوک:') }}
        <b dir="ltr">{{ rtrim(config('app.url'), '/') }}/telegram/webhook</b>
        {{ __('سپس دستور') }} <code dir="ltr">php artisan telegram:set-webhook</code> {{ __('را اجرا کنید.') }}
    </div>
@endif

<div class="glass-card mt-6 p-7">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-900/10 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="text-xs text-slate-400">{{ __('گیرندگان') }}</div>
            <div class="mt-1 text-2xl font-black text-slate-800 dark:text-white">{{ number_format($usersWithTelegram) }} {{ __('کاربر') }}</div>
        </div>
        <div class="rounded-2xl border border-slate-900/10 bg-white/40 p-4 dark:border-white/10 dark:bg-white/[0.04]">
            <div class="text-xs text-slate-400">{{ __('آخرین برودکست') }}</div>
            <div class="mt-1 text-2xl font-black text-slate-800 dark:text-white">{{ $lastBroadcast ?? '—' }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.broadcast.send') }}" class="mt-6">
        @csrf
        <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('متن پیام (HTML ساده مجاز است)') }}</label>
        <textarea name="message" rows="6" required maxlength="4000" class="glass-input w-full">{{ old('message') }}</textarea>
        @error('message')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        <button class="btn-primary mt-4">📣 {{ __('ارسال به همه (صف‌شده)') }}</button>
    </form>
</div>
@endsection
