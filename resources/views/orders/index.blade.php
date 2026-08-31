@extends('layouts.app')

@section('title', __('سفارش‌های من'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('سفارش‌های من') }}</h1>

<div class="glass-card mt-6 overflow-x-auto p-2">
    <table class="glass-table">
        <thead>
            <tr>
                <th>{{ __('شناسه') }}</th>
                <th>{{ __('پلن') }}</th>
                <th>{{ __('حجم') }}</th>
                <th>{{ __('قیمت') }}</th>
                <th>{{ __('وضعیت') }}</th>
                <th>{{ __('تاریخ') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td class="text-slate-400">#{{ $order->id }}</td>
                    <td>{{ $order->plan_name }} @if($order->isRenewal())<span class="text-xs text-emerald-500">({{ __('تمدید') }})</span>@endif</td>
                    <td>{{ \App\Support\Format::bytes($order->volume_gb * 1024 ** 3) }}</td>
                    <td>{{ number_format($order->price_toman) }} {{ __('تومان') }}</td>
                    <td><span class="{{ $order->statusColor() === 'green' ? 'badge-green' : ($order->statusColor() === 'yellow' ? 'badge-yellow' : 'badge-red') }}">{{ $order->statusLabel() }}</span></td>
                    <td class="text-slate-400">{{ \App\Support\Format::date($order->created_at, false) }}</td>
                    <td><a href="{{ route('orders.show', $order) }}" class="font-bold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('جزئیات') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">{{ __('سفارشی ثبت نشده است.') }} <a href="{{ route('home') }}#plans" class="font-bold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('مشاهده پلن‌ها') }}</a></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection