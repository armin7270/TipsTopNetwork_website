@extends('admin.layout')

@section('title', __('بکاپ و مهاجرت'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">🚚 {{ __('بکاپ و مهاجرت') }}</h1>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('خروجی کامل سایت برای انتقال به هاست یا اکانت جدید — بدون از دست رفتن حتی یک رکورد.') }}</p>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="glass-card lift p-5 text-center">
        <div class="text-xs font-black text-slate-400">{{ __('نوع دیتابیس') }}</div>
        <div class="mt-1 font-black text-slate-800 dark:text-white" dir="ltr">{{ $driver }}</div>
    </div>
    <div class="glass-card lift p-5 text-center">
        <div class="text-xs font-black text-slate-400">{{ __('حجم دیتابیس') }}</div>
        <div class="mt-1 font-black text-slate-800 dark:text-white" dir="ltr">{{ $dbSize }}</div>
    </div>
    <div class="glass-card lift p-5 text-center">
        <div class="text-xs font-black text-slate-400">{{ __('حجم فایل‌های عمومی') }}</div>
        <div class="mt-1 font-black text-slate-800 dark:text-white" dir="ltr">{{ $storageSize }}</div>
    </div>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-2">
    {{-- خروجی --}}
    <div class="glass-card glow-border p-6">
        <h2 class="font-black text-slate-800 dark:text-white">📥 {{ __('قدم ۱ — خروجی از اکانت فعلی') }}</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500 dark:text-slate-400">{{ __('هر دو فایل را دانلود و نگه دارید. بکاپ خودکار هفتگی هم به تلگرام مدیر ارسال می‌شود.') }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.migration.export-db') }}">
                @csrf
                <button class="btn-primary text-xs">💾 {{ __('دانلود دامپ دیتابیس') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.migration.export-files') }}">
                @csrf
                <button class="btn-ghost text-xs">📁 {{ __('دانلود فایل‌های عمومی') }}</button>
            </form>
        </div>
    </div>

    {{-- ورودی --}}
    <div class="glass-card glow-border p-6">
        <h2 class="font-black text-slate-800 dark:text-white">📤 {{ __('قدم ۲ — ورود به اکانت جدید') }}</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500 dark:text-slate-400">{{ __('بعد از دیپلوی تمیز روی اکانت جدید، فایل‌های قدم ۱ را اینجا آپلود کنید.') }}</p>
        <form method="POST" action="{{ route('admin.migration.import-db') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center gap-2" onsubmit="return confirm('{{ __('دیتابیس فعلی کاملاً جایگزین می‌شود. مطمئنید؟') }}')">
            @csrf
            <input type="file" name="dump" accept=".sql,.sqlite,.db" required class="glass-input flex-1 text-xs">
            <button class="btn-primary shrink-0 text-xs">{{ __('ورود دامپ') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.migration.import-files') }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-2">
            @csrf
            <input type="file" name="archive" accept=".zip" required class="glass-input flex-1 text-xs">
            <button class="btn-ghost shrink-0 text-xs">{{ __('ورود فایل‌ها') }}</button>
        </form>
    </div>
</div>

{{-- راهنمای مهاجرت --}}
<div class="glass-card mt-5 p-6">
    <h2 class="font-black text-slate-800 dark:text-white">🧭 {{ __('راهنمای مهاجرت به اکانت جدید Railway (۵ دقیقه)') }}</h2>
    <ol class="mt-4 list-decimal space-y-2.5 ps-5 text-sm leading-7 text-slate-600 dark:text-slate-300">
        <li>{{ __('از همین صفحه، دامپ دیتابیس و فایل‌های عمومی را دانلود کنید.') }}</li>
        <li>{{ __('در اکانت جدید Railway یک پروژه تازه از همین ریپو گیت‌هاب بسازید + پلاگین Postgres اضافه کنید.') }}</li>
        <li>{{ __('متغیرهای محیطی را عیناً کپی کنید — مهم‌ترین:') }} <b dir="ltr">APP_KEY</b> {{ __('(رمزهای سرور با آن قفل شده‌اند!)') }}، <b dir="ltr">DB_URL</b>، <b dir="ltr">ADMIN_*</b> {{ __('و بقیه.') }}</li>
        <li>{{ __('بعد از اولین دیپلوی، وارد پنل ادمین شوید و از همین صفحه فایل‌ها را وارد (Import) کنید.') }}</li>
        <li>{{ __('در سرویس جدید Railway یک Cron بسازید:') }} <b dir="ltr">php artisan schedule:run</b> {{ __('هر دقیقه + سرویس worker برای صف.') }}</li>
        <li>{{ __('وبهوک تلگرام را با دامنه جدید ست کنید و یک خرید تستی انجام دهید.') }}</li>
    </ol>
    <p class="mt-4 rounded-2xl border border-emerald-400/40 bg-emerald-500/10 p-3 text-xs leading-6 text-emerald-700 dark:text-emerald-400">
        💡 {{ __('نکته: اگر APP_KEY جدید با قبلی فرق کند، رمزهای ذخیره‌شده سرورها باز نمی‌شوند و باید دوباره در بخش سرورها وارد شوند. پس حتماً همان کلید قبلی را کپی کنید.') }}
    </p>
</div>
@endsection
