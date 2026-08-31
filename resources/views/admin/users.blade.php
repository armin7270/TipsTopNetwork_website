@extends('admin.layout')

@section('title', __('کاربران'))

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('کاربران') }}</h1>
    <form method="GET" class="flex gap-2">
        <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('جستجوی نام یا شماره...') }}" class="glass-input">
        <button class="btn-ghost">{{ __('جستجو') }}</button>
    </form>
</div>

<div class="glass-card mt-6 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>{{ __('نام') }}</th>
                <th>{{ __('موبایل') }}</th>
                <th>{{ __('سفارش‌ها') }}</th>
                <th>{{ __('موجودی') }}</th>
                <th>{{ __('نقش') }}</th>
                <th>{{ __('وضعیت') }}</th>
                <th>{{ __('عضویت') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td dir="ltr">{{ $user->phone }}</td>
                    <td>{{ $user->orders_count }}</td>
                    <td class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($user->balance) }}</td>
                    <td>{{ $user->isAdmin() ? __('مدیر') : __('کاربر') }}</td>
                    <td><span class="{{ $user->isBlocked() ? 'badge-red' : 'badge-green' }}">{{ $user->isBlocked() ? __('مسدود') : __('فعال') }}</span></td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($user->created_at, false) }}</td>
                    <td>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('admin.users.toggle-block', $user) }}">
                                @csrf
                                <button class="text-xs font-bold {{ $user->isBlocked() ? 'text-emerald-600 hover:underline dark:text-emerald-400' : 'text-slate-400 transition hover:text-red-500' }}">{{ $user->isBlocked() ? __('رفع مسدودی') : __('مسدود کردن') }}</button>
                            </form>

                            @if ($user->telegram_chat_id)
                                <details>
                                    <summary class="cursor-pointer list-none text-xs font-bold text-indigo-600 dark:text-indigo-400">✈️ {{ __('پیام') }}</summary>
                                    <form method="POST" action="{{ route('admin.users.send-telegram', $user) }}" class="mt-2 flex gap-2">
                                        @csrf
                                        <input type="text" name="message" required maxlength="4000" placeholder="{{ __('متن پیام تلگرام') }}" class="glass-input w-48">
                                        <button class="btn-primary text-xs">{{ __('ارسال') }}</button>
                                    </form>
                                </details>
                            @endif

                            <details>
                                <summary class="cursor-pointer list-none text-xs font-bold text-amber-600 dark:text-amber-400">💰 {{ __('کیف پول') }}</summary>
                                <form method="POST" action="{{ route('admin.users.adjust-wallet', $user) }}" class="mt-2 flex flex-wrap gap-2">
                                    @csrf
                                    <input type="number" name="amount" required placeholder="مبلغ (+/-)" class="glass-input w-28" dir="ltr">
                                    <input type="text" name="reason" required maxlength="200" placeholder="{{ __('دلیل') }}" class="glass-input w-32">
                                    <button class="btn-primary text-xs">{{ __('ثبت') }}</button>
                                </form>
                            </details>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">{{ __('کاربری یافت نشد.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection