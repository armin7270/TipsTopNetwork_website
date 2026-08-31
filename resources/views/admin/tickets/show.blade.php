@extends('admin.layout')

@section('title', __('تیکت').' #'.$ticket->id)

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-black text-slate-800 dark:text-white">
        {{ __('تیکت') }} #{{ $ticket->id }} — {{ $ticket->subject }}
    </h1>
    <div class="flex items-center gap-2">
        <span class="{{ $ticket->statusColor() === 'green' ? 'badge-green' : ($ticket->statusColor() === 'yellow' ? 'badge-yellow' : 'badge-red') }}">{{ $ticket->statusLabel() }}</span>
        @if ($ticket->status === \App\Models\Ticket::STATUS_CLOSED)
            <form method="POST" action="{{ route('admin.tickets.reopen', $ticket) }}">
                @csrf
                <button class="btn-ghost text-xs">{{ __('بازگشایی') }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.tickets.close', $ticket) }}">
                @csrf
                <button class="btn-ghost text-xs text-red-400">{{ __('بستن تیکت') }}</button>
            </form>
        @endif
    </div>
</div>

<div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
    {{ __('کاربر') }}: {{ $ticket->user?->name }} (<span dir="ltr">{{ $ticket->user?->phone }}</span>)
    {{ __('اولویت') }}: {{ $ticket->priorityLabel() }}
</div>

<div class="mt-6 space-y-4">
    @foreach ($ticket->replies as $reply)
        <div class="glass-card p-5 {{ $reply->is_staff ? 'border-emerald-400/40' : '' }}">
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <b class="{{ $reply->is_staff ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300' }}">
                    {{ $reply->is_staff ? '🎧 '.__('پشتیبانی') : $reply->user?->name }}
                </b>
                <span class="text-slate-400">{{ \App\Support\Format::date($reply->created_at) }}</span>
            </div>
            <p class="mt-3 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ $reply->message }}</p>
            @if ($reply->attachment)
                <a href="{{ asset('storage/'.$reply->attachment) }}" target="_blank" class="mt-3 inline-block text-xs font-bold text-indigo-600 hover:underline dark:text-indigo-400">📎 {{ __('دانلود پیوست') }}</a>
            @endif
        </div>
    @endforeach
</div>

@if ($ticket->status !== \App\Models\Ticket::STATUS_CLOSED)
    <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="glass-card mt-6 grid gap-4 p-7">
        @csrf
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('پاسخ شما (به کاربر اطلاع داده می‌شود)') }}</label>
            <textarea name="message" rows="4" required maxlength="5000" class="glass-input w-full">{{ old('message') }}</textarea>
            @error('message')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('پیوست (اختیاری)') }}</label>
            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.zip" class="glass-input w-full">
        </div>
        <div>
            <button class="btn-success">{{ __('ارسال پاسخ') }}</button>
        </div>
    </form>
@endif
@endsection
