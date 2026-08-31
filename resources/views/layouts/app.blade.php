<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('کانفیگ پرسرعت')) | {{ $siteName }}</title>

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

<header id="site-header" class="sticky top-0 z-40 px-4 pt-4 nav-shell">
    <div class="glass mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 rounded-3xl px-5">
        <a href="{{ route('home') }}" class="group flex items-center gap-2.5">
            <span class="relative grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-violet-500 via-fuchsia-500 to-cyan-400 text-white shadow-glow transition-transform duration-300 group-hover:rotate-6 group-hover:scale-105">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="absolute inset-0 rounded-2xl bg-gradient-to-br from-violet-500 to-cyan-400 opacity-0 blur-md transition-opacity duration-300 group-hover:opacity-60"></span>
            </span>
            <span class="text-lg font-black text-slate-800 dark:text-white">{{ $siteName }}</span>
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex dark:text-slate-300">
            <a class="nav-link transition hover:text-violet-600 dark:hover:text-violet-400" href="{{ route('home') }}">{{ __('خانه') }}</a>
            <a class="nav-link transition hover:text-violet-600 dark:hover:text-violet-400" href="{{ route('home') }}#plans">{{ __('پلن‌ها') }}</a>
            <a class="nav-link transition hover:text-violet-600 dark:hover:text-violet-400" href="{{ route('home') }}#guide">{{ __('راهنمای اتصال') }}</a>
            @auth
                <a class="nav-link transition hover:text-violet-600 dark:hover:text-violet-400" href="{{ route('dashboard') }}">{{ __('داشبورد') }}</a>
                <a class="nav-link transition hover:text-violet-600 dark:hover:text-violet-400" href="{{ route('wallet.index') }}">{{ __('کیف پول') }}</a>
                <a class="nav-link transition hover:text-violet-600 dark:hover:text-violet-400" href="{{ route('tickets.index') }}">{{ __('پشتیبانی') }}</a>
                @if (auth()->user()->isAdmin())
                    <a class="flex items-center gap-1.5 font-bold text-fuchsia-600 transition hover:text-fuchsia-500 dark:text-fuchsia-400" href="{{ route('admin.dashboard') }}">
                        <span class="live-dot text-fuchsia-500"></span>{{ __('پنل مدیریت') }}
                    </a>
                @endif
            @endauth
        </nav>

        <div class="flex items-center gap-2">
            <button onclick="toggleTheme()" title="{{ __('تغییر ظاهر روشن/تاریک') }}" class="glass grid h-10 w-10 cursor-pointer place-items-center rounded-2xl text-slate-600 transition hover:scale-105 hover:text-violet-500 dark:text-amber-300">
                <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>

            <a href="{{ route('lang.switch', app()->getLocale() === 'fa' ? 'en' : 'fa') }}" title="{{ __('تغییر زبان') }}" class="glass grid h-10 w-10 place-items-center rounded-2xl text-xs font-black text-slate-600 transition hover:scale-105 dark:text-slate-200">
                {{ app()->getLocale() === 'fa' ? 'EN' : 'فا' }}
            </a>

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-ghost hidden sm:inline-flex">{{ __('خروج') }}</button>
                </form>
            @else
                <a class="btn-ghost hidden sm:inline-flex" href="{{ route('login') }}">{{ __('ورود') }}</a>
                <a class="btn-primary hidden sm:inline-flex" href="{{ route('register') }}">{{ __('ثبت‌نام') }}</a>
            @endauth

            <button id="burger" onclick="toggleMobileMenu()" class="glass flex h-10 w-10 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl md:hidden" aria-label="منو">
                <span class="burger-l1 block h-0.5 w-4.5 rounded-full bg-current transition-all duration-300"></span>
                <span class="burger-l2 block h-0.5 w-4.5 rounded-full bg-current transition-all duration-300"></span>
                <span class="burger-l3 block h-0.5 w-4.5 rounded-full bg-current transition-all duration-300"></span>
            </button>
        </div>
    </div>

    {{-- منوی موبایل --}}
    <div id="mobile-menu" class="mobile-menu md:hidden">
        <div class="glass-card mx-auto mt-3 max-w-6xl p-5">
            <nav class="grid gap-1 text-sm font-bold text-slate-700 dark:text-slate-200">
                <a class="rounded-2xl px-4 py-3 transition hover:bg-violet-500/10" href="{{ route('home') }}">🏠 {{ __('خانه') }}</a>
                <a class="rounded-2xl px-4 py-3 transition hover:bg-violet-500/10" href="{{ route('home') }}#plans">🛒 {{ __('پلن‌ها') }}</a>
                <a class="rounded-2xl px-4 py-3 transition hover:bg-violet-500/10" href="{{ route('home') }}#guide">📖 {{ __('راهنمای اتصال') }}</a>
                @auth
                    <a class="rounded-2xl px-4 py-3 transition hover:bg-violet-500/10" href="{{ route('dashboard') }}">📊 {{ __('داشبورد') }}</a>
                    <a class="rounded-2xl px-4 py-3 transition hover:bg-violet-500/10" href="{{ route('orders.index') }}">📦 {{ __('سفارش‌ها') }}</a>
                    <a class="rounded-2xl px-4 py-3 transition hover:bg-violet-500/10" href="{{ route('wallet.index') }}">👛 {{ __('کیف پول') }}</a>
                    <a class="rounded-2xl px-4 py-3 transition hover:bg-violet-500/10" href="{{ route('tickets.index') }}">🎧 {{ __('پشتیبانی') }}</a>
                    @if (auth()->user()->isAdmin())
                        <a class="rounded-2xl px-4 py-3 font-bold text-fuchsia-600 transition hover:bg-fuchsia-500/10 dark:text-fuchsia-400" href="{{ route('admin.dashboard') }}">⚡ {{ __('پنل مدیریت') }}</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full rounded-2xl px-4 py-3 text-start transition hover:bg-red-500/10">🚪 {{ __('خروج') }}</button>
                    </form>
                @else
                    <a class="btn-ghost mt-2" href="{{ route('login') }}">{{ __('ورود') }}</a>
                    <a class="btn-primary mt-2" href="{{ route('register') }}">{{ __('ثبت‌نام') }}</a>
                @endauth
            </nav>
        </div>
    </div>
</header>

<main class="mx-auto max-w-6xl px-4 py-10">
    @if (session('success'))
        <div data-flash class="glass animate-pop-in fixed left-1/2 top-24 z-50 -translate-x-1/2 rounded-2xl border-emerald-400/40 px-6 py-3 font-bold text-emerald-600 dark:text-emerald-400">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div data-flash class="glass animate-pop-in fixed left-1/2 top-24 z-50 -translate-x-1/2 rounded-2xl border-red-400/40 px-6 py-3 font-bold text-red-600 dark:text-red-400">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    @yield('content')
</main>

<footer class="mt-10 px-4 pb-8">
    <div class="glass mx-auto max-w-6xl rounded-3xl py-8 text-center text-sm text-slate-500 dark:text-slate-400">
        <div class="mb-3 flex items-center justify-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-cyan-400 text-white">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </span>
            <span class="font-black text-slate-700 dark:text-slate-200">{{ $siteName }}</span>
        </div>
        <p>© {{ date('Y') }} {{ $siteName }} — {{ __('تمامی حقوق محفوظ است.') }}</p>
        @if ($supportTelegram)
            <p class="mt-2">{{ __('پشتیبانی') }}: <a class="font-bold text-violet-600 hover:underline dark:text-violet-400" href="https://t.me/{{ trim($supportTelegram, '@') }}" target="_blank" rel="noopener">{{ $supportTelegram }}</a></p>
        @endif
    </div>
</footer>

<script>
    // سایه هدر هنگام اسکرول
    (function () {
        var header = document.getElementById('site-header');
        if (!header) return;
        var onScroll = function () {
            header.classList.toggle('nav-scrolled', window.scrollY > 12);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();
</script>
</body>
</html>
