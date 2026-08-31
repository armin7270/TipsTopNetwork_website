@extends('layouts.app')

@section('content')
{{-- ================= هیرو ================= --}}
<section class="glass-card relative overflow-hidden px-6 py-20 text-center md:py-28">
    <div class="pointer-events-none absolute -top-24 start-1/4 h-72 w-72 animate-float rounded-full bg-violet-400/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 end-1/4 h-72 w-72 animate-float-slow rounded-full bg-fuchsia-400/25 blur-3xl"></div>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-400/60 to-transparent"></div>

    <div class="relative mx-auto max-w-3xl">
        <p data-reveal class="badge animate-pulse-glow border border-violet-400/30 bg-violet-500/10 px-5 py-2 text-xs text-violet-600 dark:text-violet-300">
            <span class="live-dot me-2 text-emerald-500"></span>
            {{ __('اتصال امن و پرسرعت — تانل اختصاصی') }}
        </p>

        <h1 data-reveal style="--reveal-delay: 100ms" class="mt-6 text-4xl font-black leading-[1.5] text-slate-900 md:text-6xl md:leading-[1.5] dark:text-white">
            {{ __('اینترنت آزاد و بدون فیلتر با') }}<br>
            <span class="title-gradient">{{ $siteName }}</span>
        </h1>

        <p data-reveal style="--reveal-delay: 200ms" class="mx-auto mt-6 max-w-2xl leading-8 text-slate-600 dark:text-slate-400">
            {{ __('کانفیگ‌های V2Ray با پروتکل‌های VLESS، VMess و Trojan روی سرورهای تانل‌شده اختصاصی. تحویل آنی و خودکار بعد از تایید پرداخت — بدون نیاز به تماس و چت.') }}
        </p>

        <div data-reveal style="--reveal-delay: 300ms" class="mt-10 flex flex-wrap items-center justify-center gap-3">
            <a href="#plans" class="btn-primary px-9 py-4 text-base">{{ __('خرید اشتراک') }} ⚡</a>
            <a href="#guide" class="btn-ghost px-9 py-4 text-base">{{ __('راهنمای اتصال') }}</a>
        </div>

        {{-- شمارنده‌های زنده --}}
        <div data-reveal style="--reveal-delay: 420ms" class="mx-auto mt-14 grid max-w-xl grid-cols-3 gap-4">
            <div class="glass rounded-2xl px-4 py-5" data-spotlight>
                <div class="text-2xl font-black text-violet-600 dark:text-violet-400" data-counter="{{ max(1, $plans->count()) }}">0</div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('پلن فعال') }}</div>
            </div>
            <div class="glass rounded-2xl px-4 py-5" data-spotlight>
                <div class="text-2xl font-black text-fuchsia-600 dark:text-fuchsia-400" data-counter="99.9" data-counter-suffix="%" data-counter-decimal="1">0</div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('آپ‌تایم') }}</div>
            </div>
            <div class="glass rounded-2xl px-4 py-5" data-spotlight>
                <div class="text-2xl font-black text-cyan-600 dark:text-cyan-400" data-counter="15" data-counter-suffix="+">0</div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('اپلیکیشن پشتیبانی‌شده') }}</div>
            </div>
        </div>
    </div>
</section>

{{-- ================= نوار متحرک ویژگی‌ها ================= --}}
<div data-reveal class="marquee mt-6 glass rounded-2xl py-3.5">
    <div class="marquee-track text-sm font-bold text-slate-600 dark:text-slate-300">
        @foreach (['⚡ سرعت نور', '🔐 رمزنگاری کامل', '🌍 IP اختصاصی', '📱 همه پلتفرم‌ها', '🔄 تحویل آنی', '🎧 پشتیبانی ۲۴/۷', '🛡 بدون لاگ', '🎮 مناسب گیم'] as $item)
            <span class="whitespace-nowrap">{{ $item }}</span>
            <span class="text-violet-400">•</span>
        @endforeach
        @foreach (['⚡ سرعت نور', '🔐 رمزنگاری کامل', '🌍 IP اختصاصی', '📱 همه پلتفرم‌ها', '🔄 تحویل آنی', '🎧 پشتیبانی ۲۴/۷', '🛡 بدون لاگ', '🎮 مناسب گیم'] as $item)
            <span class="whitespace-nowrap">{{ $item }}</span>
            <span class="text-violet-400">•</span>
        @endforeach
    </div>
</div>

{{-- ================= ویژگی‌ها ================= --}}
<section class="mt-14 grid gap-5 sm:grid-cols-3">
    <div data-reveal data-tilt data-spotlight class="glass-card glow-border group relative overflow-hidden p-7 text-center transition-all duration-300">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-gradient-to-br from-amber-400 to-orange-500 text-3xl text-white shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6">⚡</div>
        <h3 class="mt-5 font-black text-slate-800 dark:text-white">{{ __('سرعت بالا و پایدار') }}</h3>
        <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ __('سرورهای تانل اختصاصی با پینگ مناسب برای گیم، دانلود و تماشای ویدیو.') }}</p>
    </div>
    <div data-reveal style="--reveal-delay: 120ms" data-tilt data-spotlight class="glass-card glow-border group relative overflow-hidden p-7 text-center transition-all duration-300">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-gradient-to-br from-violet-400 to-fuchsia-600 text-3xl text-white shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:rotate-6">🔄</div>
        <h3 class="mt-5 font-black text-slate-800 dark:text-white">{{ __('تحویل خودکار') }}</h3>
        <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ __('بعد از تایید پرداخت، لینک اشتراک شما همان لحظه ساخته و فعال می‌شود.') }}</p>
    </div>
    <div data-reveal style="--reveal-delay: 240ms" data-tilt data-spotlight class="glass-card glow-border group relative overflow-hidden p-7 text-center transition-all duration-300">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-gradient-to-br from-cyan-400 to-teal-600 text-3xl text-white shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6">📱</div>
        <h3 class="mt-5 font-black text-slate-800 dark:text-white">{{ __('همه دستگاه‌ها') }}</h3>
        <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ __('اندروید، iOS، ویندوز و مک — یک لینک اشتراک برای همه اپلیکیشن‌های معروف.') }}</p>
    </div>
</section>

{{-- ================= پلن‌ها ================= --}}
<section id="plans" class="mt-24 scroll-mt-28">
    <h2 data-reveal class="section-title">{{ __('پلن‌های اشتراک') }}</h2>
    <p data-reveal style="--reveal-delay: 100ms" class="mt-3 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('پس از تایید پرداخت (کارت به کارت)، اشتراک شما بلافاصله فعال می‌شود.') }}</p>

    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($plans as $plan)
            <div data-reveal style="--reveal-delay: {{ $loop->index * 120 }}ms" data-tilt data-spotlight
                class="glass-card glow-border relative flex flex-col p-7 transition-all duration-300 hover:-translate-y-2 {{ $loop->first ? 'ring-2 ring-violet-400/50' : '' }}">
                @if ($loop->first)
                    <span class="absolute -top-3 start-6 animate-pulse-glow rounded-full bg-gradient-to-l from-violet-600 to-fuchsia-600 px-4 py-1 text-[11px] font-black text-white shadow-glow">{{ __('پیشنهاد ویژه') }}</span>
                @endif

                <h3 class="text-lg font-black text-slate-800 dark:text-white">{{ $plan->name }}</h3>
                <div class="mt-3 flex items-baseline gap-1">
                    <span class="text-4xl font-black title-gradient">{{ number_format($plan->price_toman) }}</span>
                    <span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('تومان') }}</span>
                </div>

                <ul class="mt-6 flex-1 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                    <li class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-violet-500/15 text-[10px] text-violet-600 dark:text-violet-400">✓</span> {{ __('حجم') }}: {{ $plan->volumeLabel() }}</li>
                    <li class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-violet-500/15 text-[10px] text-violet-600 dark:text-violet-400">✓</span> {{ __('اعتبار') }}: {{ $plan->durationLabel() }}</li>
                    <li class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-violet-500/15 text-[10px] text-violet-600 dark:text-violet-400">✓</span> {{ __('همه پروتکل‌ها (VLESS / VMess / Trojan)') }}</li>
                    <li class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-violet-500/15 text-[10px] text-violet-600 dark:text-violet-400">✓</span> {{ __('اتصال چند دستگاه') }}</li>
                </ul>

                @auth
                    <a href="{{ route('buy', $plan) }}" class="btn-primary mt-7 w-full">{{ __('خرید و فعال‌سازی') }}</a>
                @else
                    <a href="{{ route('register') }}" class="btn-primary mt-7 w-full">{{ __('ثبت‌نام و خرید') }}</a>
                @endauth
            </div>
        @empty
            <p data-reveal class="glass-card col-span-full p-12 text-center text-slate-400">{{ __('فعلاً پلنی تعریف نشده است.') }}</p>
        @endforelse
    </div>
</section>

{{-- ================= راهنمای اتصال ================= --}}
<section id="guide" class="mt-24 scroll-mt-28">
    <h2 data-reveal class="section-title">{{ __('راهنمای اتصال') }}</h2>
    <div class="mx-auto mt-12 max-w-3xl space-y-4">
        <details data-reveal class="glass-card glow-border group p-6" open>
            <summary class="flex cursor-pointer list-none items-center gap-3 font-black text-slate-800 marker:hidden dark:text-white">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-sm text-white transition-transform duration-300 group-open:rotate-12">۱</span>
                {{ __('اپلیکیشن مناسب را نصب کنید') }}
                <span class="ms-auto text-violet-400 transition-transform duration-300 group-open:rotate-180">▾</span>
            </summary>
            <div class="mt-5 space-y-1 border-t border-dashed border-slate-900/10 pt-4 text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">
                <p>{{ __('اندروید') }}: <b class="text-slate-700 dark:text-slate-200">V2rayNG</b> {{ __('یا') }} <b class="text-slate-700 dark:text-slate-200">Hiddify</b></p>
                <p>{{ __('آیفون/آیپد') }}: <b class="text-slate-700 dark:text-slate-200">Streisand</b> {{ __('یا') }} <b class="text-slate-700 dark:text-slate-200">Hiddify</b></p>
                <p>{{ __('ویندوز/مک') }}: <b class="text-slate-700 dark:text-slate-200">Hiddify</b> {{ __('یا') }} <b class="text-slate-700 dark:text-slate-200">Nekoray</b></p>
            </div>
        </details>

        <details data-reveal style="--reveal-delay: 120ms" class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none items-center gap-3 font-black text-slate-800 marker:hidden dark:text-white">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-fuchsia-500 to-violet-600 text-sm text-white transition-transform duration-300 group-open:rotate-12">۲</span>
                {{ __('لینک اشتراک را کپی کنید') }}
                <span class="ms-auto text-violet-400 transition-transform duration-300 group-open:rotate-180">▾</span>
            </summary>
            <p class="mt-5 border-t border-dashed border-slate-900/10 pt-4 text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">
                {{ __('بعد از خرید و فعال‌سازی، وارد داشبورد شوید و دکمه «کپی لینک اشتراک» را بزنید.') }}
                <a class="font-bold text-violet-600 hover:underline dark:text-violet-400" href="{{ route('dashboard') }}">{{ __('داشبورد') }}</a>
            </p>
        </details>

        <details data-reveal style="--reveal-delay: 240ms" class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none items-center gap-3 font-black text-slate-800 marker:hidden dark:text-white">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-cyan-500 to-teal-600 text-sm text-white transition-transform duration-300 group-open:rotate-12">۳</span>
                {{ __('در اپلیکیشن ایمپورت کنید') }}
                <span class="ms-auto text-violet-400 transition-transform duration-300 group-open:rotate-180">▾</span>
            </summary>
            <p class="mt-5 border-t border-dashed border-slate-900/10 pt-4 text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">{{ __('در اپلیکیشن، گزینه Import from clipboard یا افزودن از «اشتراک (Subscription)» را بزنید. همه کانفیگ‌ها به‌صورت خودکار اضافه می‌شوند؛ هر کدام که بهتر وصل شد را انتخاب کنید.') }}</p>
        </details>
    </div>
</section>

{{-- ================= سوالات متداول ================= --}}
<section class="mt-24">
    <h2 data-reveal class="section-title">{{ __('سوالات متداول') }}</h2>
    <div class="mx-auto mt-12 max-w-3xl space-y-4">
        <details data-reveal class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none items-center justify-between font-black text-slate-800 marker:hidden dark:text-white">
                {{ __('چطور پرداخت کنم؟') }}
                <span class="text-violet-400 transition-transform duration-300 group-open:rotate-180">▾</span>
            </summary>
            <p class="mt-4 border-t border-dashed border-slate-900/10 pt-4 text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">{{ __('روش پرداخت فعلاً کارت به کارت است. پلن را انتخاب کنید، شماره کارت را می‌بینید، واریز کنید و کد پیگیری را ثبت نمایید. بعد از تایید، اشتراک فعال می‌شود.') }}</p>
        </details>
        <details data-reveal style="--reveal-delay: 120ms" class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none items-center justify-between font-black text-slate-800 marker:hidden dark:text-white">
                {{ __('چقدر طول می‌کشد فعال شود؟') }}
                <span class="text-violet-400 transition-transform duration-300 group-open:rotate-180">▾</span>
            </summary>
            <p class="mt-4 border-t border-dashed border-slate-900/10 pt-4 text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">{{ __('معمولاً کمتر از ۱۵ دقیقه. به‌محض تایید پرداخت، سیستم به‌صورت خودکار کانفیگ شما را می‌سازد.') }}</p>
        </details>
        <details data-reveal style="--reveal-delay: 240ms" class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none items-center justify-between font-black text-slate-800 marker:hidden dark:text-white">
                {{ __('می‌توانم پلنم را تمدید کنم؟') }}
                <span class="text-violet-400 transition-transform duration-300 group-open:rotate-180">▾</span>
            </summary>
            <p class="mt-4 border-t border-dashed border-slate-900/10 pt-4 text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">{{ __('بله؛ اگر اشتراک فعال داشته باشید، خرید جدید به‌صورت تمدید محاسبه می‌شود و حجم و زمان قبلی شما حفظ می‌شود.') }}</p>
        </details>
    </div>
</section>

{{-- ================= CTA پایانی ================= --}}
<section data-reveal class="glass-card relative mt-24 overflow-hidden px-6 py-16 text-center">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-l from-violet-500/10 via-fuchsia-500/10 to-cyan-500/10"></div>
    <div class="relative">
        <h2 class="text-2xl font-black md:text-3xl">
            <span class="title-gradient">{{ __('همین حالا شروع کنید') }}</span>
        </h2>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('کمتر از ۲ دقیقه تا اتصال امن — بدون تماس، بدون چت.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @auth
                <a href="#plans" class="btn-primary px-9 py-4 text-base">{{ __('خرید اشتراک') }} 🚀</a>
            @else
                <a href="{{ route('register') }}" class="btn-primary px-9 py-4 text-base">{{ __('ساخت حساب رایگان') }} 🚀</a>
            @endauth
        </div>
    </div>
</section>
@endsection
