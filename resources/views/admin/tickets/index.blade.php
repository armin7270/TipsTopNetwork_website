@extends('admin.layout')

@section('title', __('تیکت‌های پشتیبانی'))

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('تیکت‌های پشتیبانی') }}</h1>
    <div class="flex gap-2 text-xs">
        <span class="badge-yellow">{{ $openCount }} {{ __('باز') }}</span>
        <span class="badge-green">{{ $answeredCount }} {{ __('پاسخ داده شده') }}</span>
    </div>
</div>

<div class="mt-4 flex flex-wrap gap-2 text-sm font-bold">
    <a href="{{ route('admin.tickets.index') }}" class="glass-card px-4 py-2 {{ $status === '' ? 'text-pink-600 dark:text-pink-400' : 'text-slate-500 dark:text-slate-400' }}">{{ __('همه') }}</a>
    @foreach (\App\Models\Ticket::STATUSES as $value => $label)
        <a href="{{ route('admin.tickets.index', ['status' => $value]) }}" class="glass-card px-4 py-2 {{ $status === $value ? 'text-pink-600 dark:text-pink-400' : 'text-slate-500 dark:text-slate-400' }}">{{ $label }}</a>
    @endforeach
</div>

<form method="GET" action="{{ route('admin.tickets.index') }}" class="glass-card mt-4 flex flex-wrap gap-2 p-3">
    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('جستجو: شناسه، موضوع، نام/موبایل کاربر...') }}" class="glass-input flex-1">
    <button class="btn-ghost">{{ __('جستجو') }}</button>
</form>

<div class="glass-card mt-4 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('کاربر') }}</th>
                <th>{{ __('موضوع') }}</th>
                <th>{{ __('اولویت') }}</th>
                <th>{{ __('وضعیت') }}</th>
                <th>{{ __('آخرین فعالیت') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr>
                    <td class="text-slate-400">#{{ $ticket->id }}</td>
                    <td>{{ $ticket->user?->name }} <span dir="ltr" class="text-xs text-slate-400">{{ $ticket->user?->phone }}</span></td>
                    <td class="font-bold text-slate-800 dark:text-white">{{ \Illuminate\Support\Str::limit($ticket->subject, 40) }}</td>
                    <td>{{ $ticket->priorityLabel() }}</td>
                    <td><span class="{{ $ticket->statusColor() === 'green' ? 'badge-green' : ($ticket->statusColor() === 'yellow' ? 'badge-yellow' : 'badge-red') }}">{{ $ticket->statusLabel() }}</span></td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($ticket->last_reply_at ?? $ticket->created_at, false) }}</td>
                    <td><a href="{{ route('admin.tickets.show', $ticket) }}" class="font-bold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('مشاهده') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">{{ __('تیکتی یافت نشد.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $tickets->links() }}</div>
@endsection
