@extends('admin.layout')

@section('title', __('راه‌اندازی قدم‌به‌قدم'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">🧭 {{ __('راه‌اندازی قدم‌به‌قدم فروشگاه') }}</h1>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('این چک‌لیست را کامل کنید تا سایت آماده فروش شود.') }}</p>

<div class="glass-card mt-6 p-6">
    <div class="flex items-center justify-between text-sm font-bold">
        <span>{{ __('پیشرفت راه‌اندازی') }}</span>
        <span class="title-gradient text-lg">{{ $doneCount }} / {{ $totalCount }}</span>
    </div>
    <div class="progress-neon mt-3">
        <div style="width: {{ $totalCount ? round($doneCount / $totalCount * 100) : 0 }}%"></div>
    </div>
    @if ($complete)
        <p class="animate-pop-in mt-4 rounded-2xl border border-emerald-400/40 bg-emerald-500/10 p-4 text-center font-black text-emerald-600 dark:text-emerald-400">
            🎉 {{ __('تبریک! فروشگاه شما آماده فروش است.') }}
        </p>
    @endif
</div>

<div class="glass-card mt-6 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="font-black text-slate-800 dark:text-white">🔗 {{ __('لینک فایل‌های عمومی (storage)') }}</div>
            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('برای نمایش رسیدها و فایل‌های پیوست لازم است. اگر روی هاست SSH ندارید، از اینجا بسازید.') }}</div>
        </div>
        @if ($storageLinked)
            <span class="badge-green">{{ __('متصل است') }}</span>
        @else
            <form method="POST" action="{{ route('admin.setup.link-storage') }}">
                @csrf
                <button class="btn-primary text-xs">{{ __('ساخت لینک storage') }}</button>
            </form>
        @endif
    </div>
</div>

<div class="mt-5 space-y-3">
    @foreach ($steps as $step)
        <div class="glass-card flex flex-wrap items-center gap-4 p-5 {{ $step['done'] ? 'opacity-90' : 'glow-border' }}">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl text-xl {{ $step['done'] ? 'bg-gradient-to-br from-emerald-400 to-teal-600 text-white' : 'bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white' }}">
                {{ $step['done'] ? '✓' : '•' }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="font-black text-slate-800 dark:text-white">{{ $step['title'] }}</div>
                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $step['hint'] }}</div>
            </div>
            @if (! $step['done'])
                <a href="{{ $step['url'] }}" class="btn-primary shrink-0 text-xs">{{ $step['cta'] }}</a>
            @else
                <span class="badge-green">{{ __('انجام شد') }}</span>
            @endif
        </div>
    @endforeach
</div>
@endsection
