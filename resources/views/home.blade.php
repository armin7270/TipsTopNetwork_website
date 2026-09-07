@extends('layouts.app')

@section('content')
{{-- ================= هیرو ================= --}}
<section class="glass-card rainbow-ring relative overflow-hidden px-6 py-16 text-center md:py-24">
    {{-- حباب‌های شناور شیشه‌ای --}}
    <div class="pointer-events-none absolute end-[8%] top-10 hidden animate-float md:block" aria-hidden="true">
        <span class="icon-chip chip-cyan h-16 w-16 text-2xl">🚀</span>
    </div>
    <div class="pointer-events-none absolute start-[7%] top-24 hidden animate-float-slow md:block" aria-hidden="true">
        <span class="icon-chip chip-pink h-14 w-14 text-2xl">🔐</span>
    </div>
    <div class="pointer-events-none absolute bottom-12 end-[14%] hidden animate-float-slow md:block" aria-hidden="true">
        <span class="icon-chip chip-yellow h-12 w-12 text-xl">⚡</span>
    </div>
    <div class="pointer-events-none absolute bottom-20 start-[12%] hidden animate-float md:block" aria-hidden="true">
        <span class="icon-chip chip-green h-14 w-14 text-2xl">🌍</span>
    </div>
    <span class="twinkle pointer-events-none absolute start-[20%] top-14 text-xl" aria-hidden="true">✨</span>
    <span class="twinkle pointer-events-none absolute end-[24%] top-1/3 text-lg" style="animation-delay: 0.9s" aria-hidden="true">💫</span>
    <span class="twinkle pointer-events-none absolute bottom-16 start-[45%] text-xl" style="animation-delay: 1.7s" aria-hidden="true">⭐</span>

    <div class="relative mx-auto max-w-3xl">
        <p data-reveal class="badge-pink animate-pulse-glow border border-pink-400/40 bg-white/70 px-5 py-2 text-xs backdrop-blur-xl dark:bg-white/10">
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
            <a href="#plans" class="btn-primary !px-10 !py-4 !text-base">{{ __('خرید اشتراک') }} 🚀</a>
            <a href="#guide" class="btn-ghost !px-10 !py-4 !text-base">{{ __('راهنمای اتصال') }} 📖</a>
        </div>

        {{-- شمارنده‌های زنده --}}
        <div data-reveal style="--reveal-delay: 420ms" class="mx-auto mt-12 grid max-w-xl grid-cols-3 gap-4">
            <div class="glass !rounded-3xl px-4 py-5" data-spotlight>
                <div class="mx-auto mb-2 grid h-11 w-11 place-items-center"><span class="icon-chip chip-violet h-11 w-11 text-xl">📦</span></div>
                <div class="text-2xl font-black text-slate-800 dark:text-white" data-counter="{{ max(1, $plans->count()) }}">0</div>
                <div class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('پلن فعال') }}</div>
            </div>
            <div class="glass !rounded-3xl px-4 py-5" data-spotlight>
                <div class="mx-auto mb-2 grid h-11 w-11 place-items-center"><span class="icon-chip chip-green h-11 w-11 text-xl">📶</span></div>
                <div class="text-2xl font-black text-slate-800 dark:text-white"><span data-counter="99.9" data-counter-decimal="1">0</span><span class="text-base">%</span></div>
                <div class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('آپ‌تایم') }}</div>
            </div>
            <div class="glass !rounded-3xl px-4 py-5" data-spotlight>
                <div class="mx-auto mb-2 grid h-11 w-11 place-items-center"><span class="icon-chip chip-cyan h-11 w-11 text-xl">📱</span></div>
                <div class="text-2xl font-black text-slate-800 dark:text-white"><span data-counter="15">0</span><span class="text-base">+</span></div>
                <div class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('اپلیکیشن پشتیبانی‌شده') }}</div>
            </div>
        </div>
    </div>
</section>

{{-- ================= نوار متحرک ================= --}}
<div data-reveal class="marquee mt-6 py-1">
    <div class="marquee-track">
        @foreach (['⚡ سرعت نور', '🔐 رمزنگاری کامل', '🌍 IP اختصاصی', '📱 همه پلتفرم‌ها', '🔄 تحویل آنی', '🎧 پشتیبانی ۲۴/۷', '🛡 بدون لاگ', '🎮 مناسب گیم'] as $item)
            <span class="marquee-pill">{{ $item }}</span>
        @endforeach
        @foreach (['⚡ سرعت نور', '🔐 رمزنگاری کامل', '🌍 IP اختصاصی', '📱 همه پلتفرم‌ها', '🔄 تحویل آنی', '🎧 پشتیبانی ۲۴/۷', '🛡 بدون لاگ', '🎮 مناسب گیم'] as $item)
            <span class="marquee-pill">{{ $item }}</span>
        @endforeach
    </div>
</div>

{{-- ================= ویژگی‌ها ================= --}}
<section class="mt-14 grid gap-5 sm:grid-cols-3">
    <div data-reveal data-tilt data-spotlight class="glass-card glow-border group p-7 text-center">
        <div class="icon-chip chip-orange mx-auto h-20 w-20 text-4xl">⚡</div>
        <h3 class="mt-5 text-lg font-black text-slate-800 dark:text-white">{{ __('سرعت بالا و پایدار') }}</h3>
        <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ __('سرورهای تانل اختصاصی با پینگ مناسب برای گیم، دانلود و تماشای ویدیو.') }}</p>
    </div>
    <div data-reveal style="--reveal-delay: 120ms" data-tilt data-spotlight class="glass-card glow-border group p-7 text-center">
        <div class="icon-chip chip-rainbow mx-auto h-20 w-20 text-4xl">🔄</div>
        <h3 class="mt-5 text-lg font-black text-slate-800 dark:text-white">{{ __('تحویل خودکار') }}</h3>
        <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ __('بعد از تایید پرداخت، لینک اشتراک شما همان لحظه ساخته و فعال می‌شود.') }}</p>
    </div>
    <div data-reveal style="--reveal-delay: 240ms" data-tilt data-spotlight class="glass-card glow-border group p-7 text-center">
        <div class="icon-chip chip-cyan mx-auto h-20 w-20 text-4xl">📱</div>
        <h3 class="mt-5 text-lg font-black text-slate-800 dark:text-white">{{ __('همه دستگاه‌ها') }}</h3>
        <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ __('اندروید، iOS، ویندوز و مک — یک لینک اشتراک برای همه اپلیکیشن‌های معروف.') }}</p>
    </div>
</section>

{{-- ================= پلن‌ها ================= --}}
<section id="plans" class="mt-24 scroll-mt-28">
    <div data-reveal class="mx-auto mb-2 flex w-fit items-center gap-2 rounded-full border border-white/70 bg-white/60 px-5 py-1.5 text-xs font-black text-pink-600 shadow-glass backdrop-blur-xl dark:border-white/15 dark:bg-white/10 dark:text-pink-300">
        <span class="icon-chip chip-pink h-6 w-6 text-xs">💎</span> {{ __('پیشنهادهای ویژه') }}
    </div>
    <h2 data-reveal class="section-title">{{ __('پلن‌های اشتراک') }}</h2>
    <p data-reveal style="--reveal-delay: 100ms" class="mt-3 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('پس از تایید پرداخت (کارت به کارت)، اشتراک شما بلافاصله فعال می‌شود.') }}</p>

    <div class="mt-12 grid items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($plans as $plan)
            <div data-reveal style="--reveal-delay: {{ $loop->index * 120 }}ms" data-tilt data-spotlight
                class="glass-card glow-border relative flex flex-col p-7 transition-all duration-300 hover:-translate-y-2 {{ $loop->first ? 'rainbow-ring lg:scale-[1.04]' : '' }}">
                @if ($loop->first)
                    <span class="absolute -top-3.5 start-6 animate-pulse-glow rounded-full bg-gradient-to-l from-pink-500 via-orange-400 to-violet-500 px-5 py-1.5 text-[11px] font-black text-white">🌟 {{ __('پیشنهاد ویژه') }}</span>
                @endif

                <div class="flex items-center gap-3">
                    <span class="icon-chip {{ ['chip-pink', 'chip-rainbow', 'chip-cyan'][$loop->index % 3] }} h-12 w-12 text-xl">{{ ['🚀', '💎', '👑'][$loop->index % 3] }}</span>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">{{ $plan->name }}</h3>
                </div>
                <div class="mt-4 flex items-baseline gap-1">
                    <span class="title-gradient text-4xl font-black">{{ number_format($plan->price_toman) }}</span>
                    <span class="text-sm font-bold text-slate-500 dark:text-slate-400">{{ __('تومان') }}</span>
                </div>

                <ul class="mt-6 flex-1 space-y-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                    <li class="flex items-center gap-2.5"><span class="icon-chip chip-green h-6 w-6 text-[11px]">✓</span> {{ __('حجم') }}: {{ $plan->volumeLabel() }}</li>
                    <li class="flex items-center gap-2.5"><span class="icon-chip chip-cyan h-6 w-6 text-[11px]">✓</span> {{ __('اعتبار') }}: {{ $plan->durationLabel() }}</li>
                    <li class="flex items-center gap-2.5"><span class="icon-chip chip-violet h-6 w-6 text-[11px]">✓</span> {{ __('همه پروتکل‌ها (VLESS / VMess / Trojan)') }}</li>
                    <li class="flex items-center gap-2.5"><span class="icon-chip chip-orange h-6 w-6 text-[11px]">✓</span> {{ __('اتصال چند دستگاه') }}</li>
                </ul>

                @auth
                    <a href="{{ route('buy', $plan) }}" class="btn-primary mt-7 w-full !py-3.5">{{ __('خرید و فعال‌سازی') }} ⚡</a>
                @else
                    <a href="{{ route('register') }}" class="btn-primary mt-7 w-full !py-3.5">{{ __('ثبت‌نام و خرید') }} ✨</a>
                @endauth
            </div>
        @empty
            <p data-reveal class="glass-card col-span-full p-12 text-center text-slate-400">{{ __('فعلاً پلنی تعریف نشده است.') }}</p>
        @endforelse
    </div>
</section>

{{-- ================= راهنمای اتصال ================= --}}
<section id="guide" class="mt-24 scroll-mt-28">
    <div data-reveal class="mx-auto mb-2 flex w-fit items-center gap-2 rounded-full border border-white/70 bg-white/60 px-5 py-1.5 text-xs font-black text-cyan-700 shadow-glass backdrop-blur-xl dark:border-white/15 dark:bg-white/10 dark:text-cyan-300">
        <span class="icon-chip chip-cyan h-6 w-6 text-xs">🗺️</span> {{ __('فقط ۳ قدم تا اتصال') }}
    </div>
    <h2 data-reveal class="section-title">{{ __('راهنمای اتصال') }}</h2>
    <div class="mx-auto mt-12 grid max-w-4xl gap-4 md:grid-cols-3">
        <details data-reveal class="glass-card glow-border group p-6" open>
            <summary class="flex cursor-pointer list-none flex-col items-center gap-3 text-center font-black text-slate-800 marker:hidden dark:text-white">
                <span class="icon-chip chip-pink h-14 w-14 text-2xl transition-transform duration-300 group-open:rotate-12 group-open:scale-110">📲</span>
                {{ __('اپلیکیشن را نصب کنید') }}
            </summary>
            <div class="mt-5 space-y-1 border-t border-dashed border-slate-900/10 pt-4 text-center text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">
                <p>{{ __('اندروید') }}: <b class="text-slate-700 dark:text-slate-200">V2rayNG</b> {{ __('یا') }} <b class="text-slate-700 dark:text-slate-200">Hiddify</b></p>
                <p>{{ __('آیفون/آیپد') }}: <b class="text-slate-700 dark:text-slate-200">Streisand</b> {{ __('یا') }} <b class="text-slate-700 dark:text-slate-200">Hiddify</b></p>
                <p>{{ __('ویندوز/مک') }}: <b class="text-slate-700 dark:text-slate-200">Hiddify</b> {{ __('یا') }} <b class="text-slate-700 dark:text-slate-200">Nekoray</b></p>
            </div>
        </details>

        <details data-reveal style="--reveal-delay: 120ms" class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none flex-col items-center gap-3 text-center font-black text-slate-800 marker:hidden dark:text-white">
                <span class="icon-chip chip-orange h-14 w-14 text-2xl transition-transform duration-300 group-open:rotate-12 group-open:scale-110">🔗</span>
                {{ __('لینک اشتراک را کپی کنید') }}
            </summary>
            <p class="mt-5 border-t border-dashed border-slate-900/10 pt-4 text-center text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">
                {{ __('بعد از خرید و فعال‌سازی، وارد داشبورد شوید و دکمه «کپی لینک اشتراک» را بزنید.') }}
                <a class="font-black text-pink-600 hover:underline dark:text-pink-400" href="{{ route('dashboard') }}">{{ __('داشبورد') }}</a>
            </p>
        </details>

        <details data-reveal style="--reveal-delay: 240ms" class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none flex-col items-center gap-3 text-center font-black text-slate-800 marker:hidden dark:text-white">
                <span class="icon-chip chip-green h-14 w-14 text-2xl transition-transform duration-300 group-open:rotate-12 group-open:scale-110">🎉</span>
                {{ __('در اپلیکیشن ایمپورت کنید') }}
            </summary>
            <p class="mt-5 border-t border-dashed border-slate-900/10 pt-4 text-center text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">{{ __('در اپلیکیشن، گزینه Import from clipboard یا افزودن از «اشتراک (Subscription)» را بزنید. همه کانفیگ‌ها به‌صورت خودکار اضافه می‌شوند؛ هر کدام که بهتر وصل شد را انتخاب کنید.') }}</p>
        </details>
    </div>
</section>

{{-- ================= سوالات متداول ================= --}}
<section class="mt-24">
    <h2 data-reveal class="section-title">{{ __('سوالات متداول') }}</h2>
    <div class="mx-auto mt-12 max-w-3xl space-y-4">
        <details data-reveal class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none items-center gap-3 font-black text-slate-800 marker:hidden dark:text-white">
                <span class="icon-chip chip-yellow h-9 w-9 shrink-0 text-base">💳</span>
                {{ __('چطور پرداخت کنم؟') }}
                <span class="ms-auto text-pink-400 transition-transform duration-300 group-open:rotate-180">▾</span>
            </summary>
            <p class="mt-4 border-t border-dashed border-slate-900/10 pt-4 text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">{{ __('روش پرداخت فعلاً کارت به کارت است. پلن را انتخاب کنید، شماره کارت را می‌بینید، واریز کنید و کد پیگیری را ثبت نمایید. بعد از تایید، اشتراک فعال می‌شود.') }}</p>
        </details>
        <details data-reveal style="--reveal-delay: 120ms" class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none items-center gap-3 font-black text-slate-800 marker:hidden dark:text-white">
                <span class="icon-chip chip-cyan h-9 w-9 shrink-0 text-base">⏱️</span>
                {{ __('چقدر طول می‌کشد فعال شود؟') }}
                <span class="ms-auto text-pink-400 transition-transform duration-300 group-open:rotate-180">▾</span>
            </summary>
            <p class="mt-4 border-t border-dashed border-slate-900/10 pt-4 text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">{{ __('معمولاً کمتر از ۱۵ دقیقه. به‌محض تایید پرداخت، سیستم به‌صورت خودکار کانفیگ شما را می‌سازد.') }}</p>
        </details>
        <details data-reveal style="--reveal-delay: 240ms" class="glass-card glow-border group p-6">
            <summary class="flex cursor-pointer list-none items-center gap-3 font-black text-slate-800 marker:hidden dark:text-white">
                <span class="icon-chip chip-green h-9 w-9 shrink-0 text-base">🔄</span>
                {{ __('می‌توانم پلنم را تمدید کنم؟') }}
                <span class="ms-auto text-pink-400 transition-transform duration-300 group-open:rotate-180">▾</span>
            </summary>
            <p class="mt-4 border-t border-dashed border-slate-900/10 pt-4 text-sm leading-8 text-slate-500 dark:border-white/10 dark:text-slate-400">{{ __('بله؛ اگر اشتراک فعال داشته باشید، خرید جدید به‌صورت تمدید محاسبه می‌شود و حجم و زمان قبلی شما حفظ می‌شود.') }}</p>
        </details>
    </div>
</section>

{{-- ================= CTA پایانی ================= --}}
<section data-reveal class="glass-card rainbow-ring relative mt-24 overflow-hidden px-6 py-16 text-center">
    <div class="pointer-events-none absolute -top-10 start-10 animate-float" aria-hidden="true"><span class="icon-chip chip-pink h-16 w-16 text-3xl">🎁</span></div>
    <div class="pointer-events-none absolute -bottom-6 end-12 animate-float-slow" aria-hidden="true"><span class="icon-chip chip-cyan h-14 w-14 text-2xl">🚀</span></div>
    <div class="relative">
        <h2 class="text-2xl font-black md:text-4xl">
            <span class="title-gradient">{{ __('همین حالا شروع کنید') }}</span>
        </h2>
        <p class="mt-3 text-sm font-bold text-slate-500 dark:text-slate-400">{{ __('کمتر از ۲ دقیقه تا اتصال امن — بدون تماس، بدون چت.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @auth
                <a href="#plans" class="btn-primary !px-10 !py-4 !text-base">{{ __('خرید اشتراک') }} 🚀</a>
            @else
                <a href="{{ route('register') }}" class="btn-primary !px-10 !py-4 !text-base">{{ __('ساخت حساب رایگان') }} ✨</a>
            @endauth
        </div>
    </div>
</section>
@endsection
