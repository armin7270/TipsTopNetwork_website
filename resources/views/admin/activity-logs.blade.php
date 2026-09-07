@extends('admin.layout')

@section('title', __('لاگ فعالیت‌ها'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">🧾 {{ __('لاگ فعالیت‌های مدیریتی') }}</h1>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('ردپای همه عملیات حساس مدیران: تایید/رد پرداخت، تنظیم کیف پول، مسدودی، تغییرات پلن و سرور.') }}</p>

<form method="GET" action="{{ route('admin.activity-logs.index') }}" class="glass-card mt-6 flex flex-wrap gap-2 p-4">
    <select name="action" class="glass-input w-56">
        <option value="">{{ __('همه عملیات‌ها') }}</option>
        @foreach ($actions as $key => $label)
            <option value="{{ $key }}" {{ $action === $key ? 'selected' : '' }}>{{ __($label) }}</option>
        @endforeach
    </select>
    <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('جستجو در جزئیات یا نام مدیر...') }}" class="glass-input flex-1">
    <button class="btn-ghost">{{ __('جستجو') }}</button>
</form>

<div class="glass-card mt-4 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>{{ __('مدیر') }}</th>
                <th>{{ __('عملیات') }}</th>
                <th>{{ __('موضوع') }}</th>
                <th>{{ __('جزئیات') }}</th>
                <th>{{ __('زمان') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="transition hover:bg-violet-500/5">
                    <td>{{ $log->admin?->name ?? '—' }}</td>
                    <td><span class="badge">{{ $log->actionLabel() }}</span></td>
                    <td class="text-xs text-slate-400">
                        @if ($log->subject_type)#{{ $log->subject_id }} <span dir="ltr">{{ $log->subject_type }}</span>@else—@endif
                    </td>
                    <td class="max-w-xs truncate text-xs text-slate-500 dark:text-slate-400" title="{{ $log->details }}">{{ $log->details ?? '—' }}</td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($log->created_at) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">{{ __('رکوردی ثبت نشده است.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection
