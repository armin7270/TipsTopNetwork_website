<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('پنل مدیریت')) — {{ $siteName }}</title>

    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                if (t === 'dark') { document.documentElement.classList.add('dark'); }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-slate-800 antialiased dark:text-slate-200">

<div class="aurora" aria-hidden="true">
    <span class="a1"></span>
    <span class="a2"></span>
    <span class="a3"></span>
    <span class="a4"></span>
</div>

<div class="mx-auto flex max-w-7xl">
    <aside class="glass sticky top-4 mx-4 my-4 hidden h-[calc(100vh-2rem)] w-60 shrink-0 rounded-3xl p-4 md:block">
        <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2">
            <span class="grid h-9 w-9 place-items-center rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white shadow-glow">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </span>
            <span class="font-black text-slate-800 dark:text-white">{{ $siteName }}</span>
        </a>

        @php
            $current = collect([
                'payments' => 'admin.payments.*',
                'wallet-deposits' => 'admin.wallet-deposits.*',
                'orders' => 'admin.orders.*',
                'tickets' => 'admin.tickets.*',
                'broadcast' => 'admin.broadcast.*',
                'users' => 'admin.users.*',
                'plans' => 'admin.plans.*',
                'inbounds' => 'admin.inbounds.*',
                'servers' => 'admin.servers.*',
                'settings' => 'admin.settings.*',
            ])->first(fn ($pattern) => request()->routeIs($pattern)) ?? 'dashboard';
        @endphp

        <nav class="space-y-1 text-sm font-medium">
            <a href="{{ route('admin.dashboard') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'dashboard' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">📊 {{ __('داشبورد') }}</a>
            <a href="{{ route('admin.payments.index') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'payments' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">💰 {{ __('پرداخت‌ها') }}</a>
            <a href="{{ route('admin.wallet-deposits.index') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'wallet-deposits' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">💳 {{ __('شارژ کیف پول') }}</a>
            <a href="{{ route('admin.orders.index') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'orders' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">📦 {{ __('سفارش‌ها') }}</a>
            <a href="{{ route('admin.tickets.index') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'tickets' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">🎧 {{ __('تیکت‌ها') }}</a>
            <a href="{{ route('admin.broadcast.index') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'broadcast' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">📣 {{ __('برودکست') }}</a>
            <a href="{{ route('admin.users.index') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'users' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">👥 {{ __('کاربران') }}</a>
            <a href="{{ route('admin.plans.index') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'plans' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">🏷️ {{ __('پلن‌ها') }}</a>
            <a href="{{ route('admin.inbounds.index') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'inbounds' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">🖥️ {{ __('سرورها و اینباندها') }}</a>
            <a href="{{ route('admin.settings.edit') }}" class="block rounded-2xl px-4 py-2.5 transition {{ $current === 'settings' ? 'bg-gradient-to-l from-violet-500/15 to-fuchsia-500/15 font-bold text-violet-600 dark:text-violet-400' : 'text-slate-600 hover:bg-white/40 dark:text-slate-300 dark:hover:bg-white/5' }}">⚙️ {{ __('تنظیمات') }}</a>

            <div class="my-3 border-t border-slate-900/10 dark:border-white/10"></div>
            <a href="{{ route('dashboard') }}" class="block rounded-2xl px-4 py-2.5 text-slate-500 transition hover:bg-white/40 dark:text-slate-400 dark:hover:bg-white/5">👤 {{ __('پنل کاربری') }}</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="block w-full rounded-2xl px-4 py-2.5 text-start text-slate-500 transition hover:bg-red-500/10 hover:text-red-500">{{ __('خروج') }}</button>
            </form>
        </nav>
    </aside>

    <main class="min-w-0 flex-1 p-4 md:p-6">
        <div class="glass mb-6 flex items-center justify-between rounded-3xl px-5 py-3">
            <h1 class="text-lg font-black text-slate-800 dark:text-white">{{ __('پنل مدیریت') }}</h1>
            <div class="flex items-center gap-2">
                <button onclick="toggleTheme()" title="{{ __('تغییر ظاهر روشن/تاریک') }}" class="glass grid h-10 w-10 cursor-pointer place-items-center rounded-2xl text-slate-600 transition hover:scale-105 dark:text-amber-300">
                    <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
                <a href="{{ route('lang.switch', app()->getLocale() === 'fa' ? 'en' : 'fa') }}" title="{{ __('تغییر زبان') }}" class="glass grid h-10 w-10 place-items-center rounded-2xl text-xs font-black text-slate-600 transition hover:scale-105 dark:text-slate-200">
                    {{ app()->getLocale() === 'fa' ? 'EN' : 'فا' }}
                </a>
            </div>
        </div>
        @if (session('success'))
            <div data-flash class="glass animate-pop-in fixed left-1/2 top-6 z-50 -translate-x-1/2 rounded-2xl border-emerald-400/40 px-6 py-3 font-bold text-emerald-600 dark:text-emerald-400">✅ {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div data-flash class="glass animate-pop-in fixed left-1/2 top-6 z-50 -translate-x-1/2 rounded-2xl border-red-400/40 px-6 py-3 font-bold text-red-600 dark:text-red-400">⚠️ {{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    document.querySelectorAll('[data-flash]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s, transform .5s';
            el.style.opacity = '0';
            el.style.transform = 'translate(-50%, -16px)';
            setTimeout(function () { el.remove(); }, 500);
        }, 7000);
    });

    function toggleTheme() {
        var d = document.documentElement;
        d.classList.toggle('dark');
        try { localStorage.setItem('theme', d.classList.contains('dark') ? 'dark' : 'light'); } catch (e) {}
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-copy]');
        if (btn) {
            navigator.clipboard.writeText(btn.getAttribute('data-copy')).then(function () {
                var old = btn.textContent;
                btn.textContent = '✓';
                setTimeout(function () { btn.textContent = old; }, 1500);
            });
        }
    });
</script>
</body>
</html>
