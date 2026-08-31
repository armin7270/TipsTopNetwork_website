@extends('layouts.app')

@section('title', __('تیکت جدید'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('ثبت تیکت جدید') }}</h1>

<form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="glass-card mt-6 grid gap-4 p-7">
    @csrf
    <div>
        <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('موضوع') }}</label>
        <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="190" class="glass-input w-full">
        @error('subject')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('اولویت') }}</label>
            <select name="priority" class="glass-input w-full">
                @foreach ($priorities as $value => $label)
                    <option value="{{ $value }}" {{ old('priority') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('پیوست (اختیاری — jpg/png/pdf/zip حداکثر ۵MB)') }}</label>
            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.zip" class="glass-input w-full">
            @error('attachment')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('متن پیام') }}</label>
        <textarea name="message" rows="6" required maxlength="5000" class="glass-input w-full">{{ old('message') }}</textarea>
        @error('message')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-3">
        <button class="btn-primary">{{ __('ثبت تیکت') }}</button>
        <a href="{{ route('tickets.index') }}" class="btn-ghost">{{ __('انصراف') }}</a>
    </div>
</form>
@endsection
