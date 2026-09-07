@extends('admin.layout')

@section('title', __('سفارش‌ها'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('سفارش‌ها') }}</h1>

<form method="GET" action="{{ route('admin.orders.index') }}" class="glass-card mt-4 flex flex-wrap gap-2 p-3">
    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('جستجو: شناسه، نام/موبایل کاربر، پلن، کد پیگیری...') }}" class="glass-input flex-1">
    <button class="btn-ghost">{{ __('جستجو') }}</button>
    <a href="{{ route('admin.orders.index', array_filter(['status' => $status, 'q' => $q, 'export' => 'csv'])) }}" class="btn-ghost">📥 {{ __('خروجی CSV') }}</a>
</form>

<div class="glass-card mt-4 inline-flex flex-wrap gap-2 p-2 text-sm">
    <a href="{{ route('admin.orders.index') }}" class="rounded-xl px-4 py-1.5 transition {{ !$status ? 'bg-pink-500/15 font-bold text-pink-600 dark:text-pink-400' : 'text-slate-500 hover:bg-white/40 dark:text-slate-400 dark:hover:bg-white/5' }}">{{ __('همه') }}</a>
    @foreach ($statuses as $key => $label)
        <a href="{{ route('admin.orders.index', ['status' => $key]) }}" class="rounded-xl px-4 py-1.5 transition {{ $status === $key ? 'bg-pink-500/15 font-bold text-pink-600 dark:text-pink-400' : 'text-slate-500 hover:bg-white/40 dark:text-slate-400 dark:hover:bg-white/5' }}">{{ __($label) }}</a>
    @endforeach
</div>

<div class="glass-card mt-4 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>{{ __('شناسه') }}</th>
                <th>{{ __('کاربر') }}</th>
                <th>{{ __('پلن') }}</th>
                <th>{{ __('قیمت') }}</th>
                <th>{{ __('وضعیت') }}</th>
                <th>{{ __('انقضا') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td class="text-slate-400">#{{ $order->id }} @if($order->isRenewal())<span class="text-xs text-emerald-500">({{ __('تمدید') }})</span>@endif</td>
                    <td>{{ $order->user?->name }} <span dir="ltr" class="text-xs text-slate-400">{{ $order->user?->phone }}</span></td>
                    <td>{{ $order->plan_name }}</td>
                    <td>{{ number_format($order->price_toman) }}</td>
                    <td><span class="{{ $order->statusColor() === 'green' ? 'badge-green' : ($order->statusColor() === 'yellow' ? 'badge-yellow' : 'badge-red') }}">{{ $order->statusLabel() }}</span></td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($order->expires_at, false) }}</td>
                    <td>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('جزئیات') }}</a>
                            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('{{ __('سفارش حذف شود؟ کانفیگ آن هم از پنل حذف می‌شود.') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs text-slate-400 transition hover:text-red-500">{{ __('حذف') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">{{ __('سفارشی یافت نشد.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection