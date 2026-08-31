@extends('admin.layout')

@section('title', __('پلن‌ها'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('پلن‌های فروش') }}</h1>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="glass-card overflow-x-auto p-2">
            <table class="glass-table">
                <thead>
                    <tr>
                        <th>{{ __('نام') }}</th>
                        <th>{{ __('قیمت') }}</th>
                        <th>{{ __('حجم') }}</th>
                        <th>{{ __('اعتبار') }}</th>
                        <th>{{ __('اینباندها') }}</th>
                        <th>{{ __('وضعیت') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td class="font-black text-slate-800 dark:text-white">{{ $plan->name }}</td>
                            <td>{{ number_format($plan->price_toman) }}</td>
                            <td>{{ $plan->volumeLabel() }}</td>
                            <td>{{ $plan->duration_days }} {{ __('روز') }}</td>
                            <td class="text-slate-400">{{ $plan->inbounds->count() }} {{ __('اینباند') }}</td>
                            <td><span class="{{ $plan->is_active ? 'badge-green' : 'badge-red' }}">{{ $plan->is_active ? __('فعال') : __('غیرفعال') }}</span></td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.plans.index', ['edit' => $plan->id]) }}" class="font-bold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('ویرایش') }}</a>
                                    <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" onsubmit="return confirm('{{ __('حذف شود؟') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs text-slate-400 transition hover:text-red-500">{{ __('حذف') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">{{ __('پلنی تعریف نشده است.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="glass-card p-6">
        <h2 class="font-black text-slate-800 dark:text-white">{{ $edit ? __('ویرایش پلن').': '.$edit->name : __('پلن جدید') }}</h2>
        <form method="POST" action="{{ $edit ? route('admin.plans.update', $edit) : route('admin.plans.store') }}" class="mt-5 space-y-4 text-sm">
            @csrf
            @if ($edit) @method('PUT') @endif

            <div>
                <label class="glass-label">{{ __('نام پلن') }}</label>
                <input type="text" name="name" value="{{ old('name', $edit?->name) }}" required class="glass-input">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="glass-label">{{ __('قیمت (تومان)') }}</label>
                    <input type="number" name="price_toman" value="{{ old('price_toman', $edit?->price_toman) }}" required class="glass-input">
                </div>
                <div>
                    <label class="glass-label">{{ __('حجم (گیگ — صفر = نامحدود)') }}</label>
                    <input type="number" step="0.01" name="volume_gb" value="{{ old('volume_gb', $edit?->volume_gb) }}" required class="glass-input">
                </div>
                <div>
                    <label class="glass-label">{{ __('اعتبار (روز)') }}</label>
                    <input type="number" name="duration_days" value="{{ old('duration_days', $edit?->duration_days ?? 30) }}" required class="glass-input">
                </div>
                <div>
                    <label class="glass-label">{{ __('ترتیب نمایش') }}</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $edit?->sort_order ?? 0) }}" class="glass-input">
                </div>
            </div>
            <div>
                <label class="glass-label">{{ __('توضیحات (اختیاری)') }}</label>
                <textarea name="description" rows="2" class="glass-input">{{ old('description', $edit?->description) }}</textarea>
            </div>
            <div>
                <label class="glass-label">{{ __('اینباندهای این پلن (روی هرکدام یک کلاینت ساخته می‌شود)') }}</label>
                <div class="max-h-44 space-y-2 overflow-y-auto rounded-2xl border border-slate-900/10 bg-white/40 p-3 dark:border-white/10 dark:bg-white/[0.04]">
                    @forelse ($inbounds as $inbound)
                        <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <input type="checkbox" name="inbounds[]" value="{{ $inbound->id }}" {{ in_array($inbound->id, old('inbounds', $edit?->inbounds->pluck('id')->toArray() ?? [])) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 bg-white/50 text-indigo-600 dark:border-white/20 dark:bg-white/10">
                            {{ $inbound->label() }}
                        </label>
                    @empty
                        <p class="text-xs text-slate-400">{{ __('ابتدا از بخش «سرورها و اینباندها» سرور اضافه و اینباند ایمپورت کنید.') }}</p>
                    @endforelse
                </div>
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $edit?->is_active ?? true) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 bg-white/50 text-indigo-600 dark:border-white/20 dark:bg-white/10"> {{ __('فعال باشد (در صفحه اصلی نمایش داده شود)') }}
            </label>
            <button class="btn-primary w-full">{{ $edit ? __('ذخیره تغییرات') : __('ساخت پلن') }}</button>
            @if ($edit)
                <a href="{{ route('admin.plans.index') }}" class="block text-center text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">{{ __('انصراف') }}</a>
            @endif
        </form>
    </div>
</div>
@endsection