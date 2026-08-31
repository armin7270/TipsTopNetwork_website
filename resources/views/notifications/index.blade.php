@extends('layouts.app')

@section('title', __('نوتیفیکیشن‌ها'))

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('نوتیفیکیشن‌ها') }}</h1>
    @if ($unreadCount)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn-ghost">✓ {{ __('خواندن همه') }} ({{ $unreadCount }})</button>
        </form>
    @endif
</div>

<div class="mt-6 space-y-3">
    @forelse ($notifications as $notification)
        <div class="glass-card flex flex-wrap items-center justify-between gap-3 p-5 {{ $notification->read_at ? '' : 'border-indigo-400/50' }}">
            <div>
                <b class="{{ $notification->read_at ? 'text-slate-600 dark:text-slate-300' : 'text-slate-800 dark:text-white' }}">{{ $notification->title }}</b>
                @if ($notification->body)
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $notification->body }}</p>
                @endif
                <span class="mt-1 block text-xs text-slate-400">{{ \App\Support\Format::date($notification->created_at) }}</span>
            </div>
            <div class="flex gap-2">
                @if ($notification->url)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        <button class="btn-ghost text-xs">{{ __('مشاهده') }}</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}">
                    @csrf @method('DELETE')
                    <button class="btn-ghost text-xs text-red-400">{{ __('حذف') }}</button>
                </form>
            </div>
        </div>
    @empty
        <div class="glass-card p-12 text-center text-slate-400">🔔 {{ __('نوتیفیکیشنی ندارید.') }}</div>
    @endforelse
</div>

<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
