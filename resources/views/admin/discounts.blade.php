@extends('admin.layout')

@section('title', __('کدهای تخفیف'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">🏷️ {{ __('کدهای تخفیف') }}</h1>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('در صفحه خرید پلن به مشتری‌ها نشان داده می‌شود — درصدی یا مبلغ ثابت.') }}</p>

<div class="mt-6 grid gap-5 lg:grid-cols-3">
    <div class="glass-card glow-border p-5 lg:col-span-1">
        <h2 class="font-black text-slate-800 dark:text-white">{{ $edit ? __('ویرایش کد') : __('ساخت کد تخفیف') }}</h2>
        <form method="POST" action="{{ $edit ? route('admin.discounts.update', $edit) : route('admin.discounts.store') }}" class="mt-4 space-y-3 text-sm">
            @csrf
            @if ($edit) @method('PUT') @endif
            <div>
                <label class="glass-label">{{ __('کد (انگلیسی، بدون فاصله)') }}</label>
                <input type="text" name="code" value="{{ old('code', $edit?->code) }}" required dir="ltr" placeholder="WELCOME10" class="glass-input text-start uppercase">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="glass-label">{{ __('نوع') }}</label>
                    <select name="type" class="glass-input">
                        <option value="percent" {{ old('type', $edit?->type) === 'percent' ? 'selected' : '' }}>{{ __('درصدی') }}</option>
                        <option value="fixed" {{ old('type', $edit?->type) === 'fixed' ? 'selected' : '' }}>{{ __('مبلغ ثابت') }}</option>
                    </select>
                </div>
                <div>
                    <label class="glass-label">{{ __('مقدار') }}</label>
                    <input type="number" name="value" value="{{ old('value', $edit?->value) }}" required min="1" class="glass-input" dir="ltr">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="glass-label">{{ __('حداقل خرید (تومان)') }}</label>
                    <input type="number" name="min_amount" value="{{ old('min_amount', $edit?->min_amount) }}" min="0" class="glass-input" dir="ltr">
                </div>
                <div>
                    <label class="glass-label">{{ __('حداکثر استفاده') }}</label>
                    <input type="number" name="max_uses" value="{{ old('max_uses', $edit?->max_uses) }}" min="1" placeholder="{{ __('نامحدود') }}" class="glass-input" dir="ltr">
                </div>
            </div>
            <div>
                <label class="glass-label">{{ __('انقضا (اختیاری)') }}</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $edit?->expires_at?->format('Y-m-d\TH:i')) }}" class="glass-input" dir="ltr">
            </div>
            <label class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $edit?->is_active ?? true) ? 'checked' : '' }} class="h-4 w-4"> {{ __('فعال باشد') }}
            </label>
            <div class="flex gap-2">
                <button class="btn-primary flex-1">{{ $edit ? __('ذخیره تغییرات') : __('ساخت کد') }}</button>
                @if ($edit)
                    <a href="{{ route('admin.discounts.index') }}" class="btn-ghost">{{ __('انصراف') }}</a>
                @endif
            </div>
        </form>
    </div>

    <div class="lg:col-span-2">
        <div class="glass-card overflow-x-auto p-2">
            <table class="glass-table">
                <thead>
                    <tr>
                        <th>{{ __('کد') }}</th>
                        <th>{{ __('تخفیف') }}</th>
                        <th>{{ __('استفاده') }}</th>
                        <th>{{ __('انقضا') }}</th>
                        <th>{{ __('وضعیت') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($discounts as $discount)
                        <tr class="transition hover:bg-pink-500/5">
                            <td><b dir="ltr" class="select-all">{{ $discount->code }}</b></td>
                            <td>
                                {{ $discount->type === 'percent' ? $discount->value.'٪' : number_format($discount->value).' '.__('تومان') }}
                                @if ($discount->min_amount > 0)
                                    <div class="text-xs text-slate-400">{{ __('حداقل') }}: {{ number_format($discount->min_amount) }}</div>
                                @endif
                            </td>
                            <td>{{ $discount->used_count }} @if ($discount->max_uses)/ {{ $discount->max_uses }}@endif</td>
                            <td class="text-xs {{ $discount->expires_at && $discount->expires_at->isPast() ? 'text-red-500' : 'text-slate-400' }}">
                                {{ $discount->expires_at ? \App\Support\Format::date($discount->expires_at, false) : '—' }}
                            </td>
                            <td><span class="{{ $discount->is_active ? 'badge-green' : 'badge-red' }}">{{ $discount->is_active ? __('فعال') : __('غیرفعال') }}</span></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.discounts.index', ['edit' => $discount->id]) }}" class="text-xs font-bold text-violet-600 hover:underline dark:text-violet-400">{{ __('ویرایش') }}</a>
                                    <form method="POST" action="{{ route('admin.discounts.toggle', $discount) }}">
                                        @csrf
                                        <button class="text-xs {{ $discount->is_active ? 'text-amber-600' : 'text-emerald-600' }} hover:underline">{{ $discount->is_active ? __('غیرفعال') : __('فعال') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.discounts.destroy', $discount) }}" onsubmit="return confirm('{{ __('کد حذف شود؟') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs text-red-500 hover:underline">{{ __('حذف') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">{{ __('هنوز کد تخفیفی نساخته‌اید.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
