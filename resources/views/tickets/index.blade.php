@extends('layouts.app')

@section('title', __('پشتیبانی'))

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('تیکت‌های پشتیبانی') }}</h1>
    <a href="{{ route('tickets.create') }}" class="btn-primary">✨ {{ __('تیکت جدید') }}</a>
</div>

<div class="glass-card mt-6 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('موضوع') }}</th>
                <th>{{ __('اولویت') }}</th>
                <th>{{ __('وضعیت') }}</th>
                <th>{{ __('پاسخ‌ها') }}</th>
                <th>{{ __('آخرین فعالیت') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr>
                    <td class="text-slate-400">#{{ $ticket->id }}</td>
                    <td class="font-bold text-slate-800 dark:text-white">{{ $ticket->subject }}</td>
                    <td>{{ $ticket->priorityLabel() }}</td>
                    <td><span class="{{ $ticket->statusColor() === 'green' ? 'badge-green' : ($ticket->statusColor() === 'yellow' ? 'badge-yellow' : 'badge-red') }}">{{ $ticket->statusLabel() }}</span></td>
                    <td class="text-slate-400">{{ $ticket->replies_count }}</td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($ticket->last_reply_at ?? $ticket->created_at, false) }}</td>
                    <td><a href="{{ route('tickets.show', $ticket) }}" class="font-bold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('مشاهده') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">{{ __('هنوز تیکتی ثبت نکرده‌اید.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $tickets->links() }}</div>
@endsection
