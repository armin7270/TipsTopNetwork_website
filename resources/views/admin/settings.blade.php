@extends('admin.layout')

@section('title', __('تنظیمات'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('تنظیمات سایت') }}</h1>

<form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 max-w-2xl space-y-5">
    @csrf
    @method('PUT')

    <div class="glass-card p-6">
        <h2 class="font-black text-slate-800 dark:text-white">{{ __('عمومی') }}</h2>
        <div class="mt-4 space-y-4 text-sm">
            <div>
                <label class="glass-label">{{ __('نام سایت') }}</label>
                <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" required class="glass-input">
            </div>
            <div>
                <label class="glass-label">{{ __('آیدی تلگرام پشتیبانی (بدون @ اختیاری)') }}</label>
                <input type="text" name="support_telegram" value="{{ old('support_telegram', $supportTelegram) }}" dir="ltr" class="glass-input text-start">
            </div>
            <div>
                <label class="glass-label">{{ __('آدرس پایه لینک اشتراک (مثلاً https://example.ir — خالی بگذارید تا خودکار باشد)') }}</label>
                <input type="text" name="sub_base_url" value="{{ old('sub_base_url', $subBaseUrl) }}" dir="ltr" class="glass-input text-start">
            </div>
        </div>
    </div>

    <div class="glass-card p-6">
        <div class="flex items-center justify-between">
            <h2 class="font-black text-slate-800 dark:text-white">{{ __('شماره کارت‌ها (کارت به کارت)') }}</h2>
            <button type="button" onclick="addCardRow()" class="btn-ghost px-3 py-1.5 text-xs">+ {{ __('افزودن کارت') }}</button>
        </div>
        <p class="mt-1 text-xs text-slate-400">{{ __('فقط شماره‌های پرشده ذخیره می‌شوند.') }}</p>
        <div id="cards" class="mt-3 space-y-3">
            @forelse ($cards as $card)
                <div class="grid gap-2 sm:grid-cols-3" data-card-row>
                    <input type="text" name="card_bank[]" value="{{ $card['bank'] }}" placeholder="{{ __('بانک (مثلاً ملت)') }}" class="glass-input">
                    <input type="text" name="card_holder[]" value="{{ $card['holder'] }}" placeholder="{{ __('به نام') }}" class="glass-input">
                    <input type="text" name="card_number[]" value="{{ $card['number'] }}" dir="ltr" placeholder="{{ __('شماره کارت ۱۶ رقمی') }}" class="glass-input text-start">
                </div>
            @empty
                <div class="grid gap-2 sm:grid-cols-3" data-card-row>
                    <input type="text" name="card_bank[]" value="{{ old('card_bank.0') }}" placeholder="{{ __('بانک (مثلاً ملت)') }}" class="glass-input">
                    <input type="text" name="card_holder[]" value="{{ old('card_holder.0') }}" placeholder="{{ __('به نام') }}" class="glass-input">
                    <input type="text" name="card_number[]" value="{{ old('card_number.0') }}" dir="ltr" placeholder="{{ __('شماره کارت ۱۶ رقمی') }}" class="glass-input text-start">
                </div>
            @endforelse
        </div>
    </div>

    <div class="glass-card p-6">
        <h2 class="font-black text-slate-800 dark:text-white">✈️ {{ __('ربات تلگرام') }}</h2>
        <div class="mt-4 space-y-4 text-sm">
            <div>
                <label class="glass-label">{{ __('فعال بودن ربات') }}</label>
                <select name="tg_bot_enabled" class="glass-input">
                    <option value="1" {{ $tgBotEnabled === '1' ? 'selected' : '' }}>{{ __('فعال') }}</option>
                    <option value="0" {{ $tgBotEnabled !== '1' ? 'selected' : '' }}>{{ __('غیرفعال') }}</option>
                </select>
            </div>
            <div>
                <label class="glass-label">{{ __('توکن ربات (از @BotFather)') }}</label>
                <input type="text" name="tg_bot_token" value="{{ old('tg_bot_token', $tgBotToken) }}" dir="ltr" class="glass-input text-start" placeholder="123456:ABC-DEF...">
            </div>
            <div>
                <label class="glass-label">{{ __('چت‌آیدی مدیر (برای اطلاع‌رسانی سفارش/تیکت/رسید)') }}</label>
                <input type="text" name="tg_admin_chat_id" value="{{ old('tg_admin_chat_id', $tgAdminChatId) }}" dir="ltr" class="glass-input text-start">
            </div>
            <div>
                <label class="glass-label">{{ __('اجبار عضویت در کانال (آیدی بدون @ — خالی = بدون اجبار)') }}</label>
                <input type="text" name="tg_force_channel" value="{{ old('tg_force_channel', $tgForceChannel) }}" dir="ltr" class="glass-input text-start">
            </div>
            <div>
                <label class="glass-label">{{ __('Secret Token وبهوک (اختیاری — امنیت بیشتر)') }}</label>
                <input type="text" name="tg_webhook_secret" value="{{ old('tg_webhook_secret', $tgWebhookSecret) }}" dir="ltr" class="glass-input text-start">
            </div>
            <div>
                <label class="glass-label">{{ __('مبالغ پیش‌فرض شارژ در ربات (با کاما جدا کنید)') }}</label>
                <input type="text" name="tg_deposit_amounts" value="{{ old('tg_deposit_amounts', implode(',', $tgDepositAmounts)) }}" dir="ltr" class="glass-input text-start">
            </div>
            <p class="rounded-2xl border border-slate-900/10 bg-white/40 p-3 text-xs text-slate-500 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-400">
                {{ __('آدرس وبهوک:') }} <b dir="ltr">{{ rtrim(config('app.url'), '/') }}/telegram/webhook</b>
                — {{ __('بعد از تنظیم توکن، دستور') }} <code dir="ltr">php artisan telegram:set-webhook</code> {{ __('را اجرا کنید.') }}
            </p>
        </div>
    </div>

    <div class="glass-card p-6">
        <h2 class="font-black text-slate-800 dark:text-white">🎁 {{ __('دعوت از دوستان (رفرال)') }}</h2>
        <div class="mt-4 space-y-4 text-sm">
            <div>
                <label class="glass-label">{{ __('فعال بودن سیستم معرفی') }}</label>
                <select name="referral_enabled" class="glass-input">
                    <option value="1" {{ $referralEnabled === '1' ? 'selected' : '' }}>{{ __('فعال') }}</option>
                    <option value="0" {{ $referralEnabled !== '1' ? 'selected' : '' }}>{{ __('غیرفعال') }}</option>
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="glass-label">{{ __('هدیه خوش‌آمدگویی (تومان)') }}</label>
                    <input type="number" name="referral_welcome_amount" value="{{ old('referral_welcome_amount', $referralWelcomeAmount) }}" min="0" class="glass-input" dir="ltr">
                </div>
                <div>
                    <label class="glass-label">{{ __('پاداش اولین خرید (تومان)') }}</label>
                    <input type="number" name="referral_reward_amount" value="{{ old('referral_reward_amount', $referralRewardAmount) }}" min="0" class="glass-input" dir="ltr">
                </div>
                <div>
                    <label class="glass-label">{{ __('حداقل موجودی معرف برای هدیه') }}</label>
                    <input type="number" name="referral_min_referrer_balance" value="{{ old('referral_min_referrer_balance', $referralMinReferrerBalance) }}" min="0" class="glass-input" dir="ltr">
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card p-6">
        <h2 class="font-black text-slate-800 dark:text-white">🧪 {{ __('اکانت تست') }}</h2>
        <div class="mt-4 space-y-4 text-sm">
            <div>
                <label class="glass-label">{{ __('فعال بودن اکانت تست') }}</label>
                <select name="trial_enabled" class="glass-input">
                    <option value="1" {{ $trialEnabled === '1' ? 'selected' : '' }}>{{ __('فعال') }}</option>
                    <option value="0" {{ $trialEnabled !== '1' ? 'selected' : '' }}>{{ __('غیرفعال') }}</option>
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="glass-label">{{ __('حجم (مگابایت)') }}</label>
                    <input type="number" name="trial_volume_mb" value="{{ old('trial_volume_mb', $trialVolumeMb) }}" min="50" class="glass-input" dir="ltr">
                </div>
                <div>
                    <label class="glass-label">{{ __('مدت (ساعت)') }}</label>
                    <input type="number" name="trial_duration_hours" value="{{ old('trial_duration_hours', $trialDurationHours) }}" min="1" class="glass-input" dir="ltr">
                </div>
                <div>
                    <label class="glass-label">{{ __('حداکثر به ازای هر کاربر') }}</label>
                    <input type="number" name="trial_limit_per_user" value="{{ old('trial_limit_per_user', $trialLimitPerUser) }}" min="1" class="glass-input" dir="ltr">
                </div>
            </div>
            <div>
                <label class="glass-label">{{ __('اینباند پیش‌فرض اکانت تست') }}</label>
                <select name="trial_inbound_id" class="glass-input">
                    <option value="">{{ __('خودکار (اولین اینباند فعال)') }}</option>
                    @foreach ($inbounds as $inbound)
                        <option value="{{ $inbound->id }}" {{ $trialInboundId == $inbound->id ? 'selected' : '' }}>{{ $inbound->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="glass-card p-6">
        <h2 class="font-black text-slate-800 dark:text-white">⚡ {{ __('درگاه پرداخت آنلاین (زرین‌پال)') }}</h2>
        <div class="mt-4 space-y-4 text-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="glass-label">{{ __('فعال بودن درگاه') }}</label>
                    <select name="zp_enabled" class="glass-input">
                        <option value="1" {{ $zpEnabled === '1' ? 'selected' : '' }}>{{ __('فعال') }}</option>
                        <option value="0" {{ $zpEnabled !== '1' ? 'selected' : '' }}>{{ __('غیرفعال') }}</option>
                    </select>
                </div>
                <div>
                    <label class="glass-label">{{ __('حالت تست (Sandbox)') }}</label>
                    <select name="zp_sandbox" class="glass-input">
                        <option value="1" {{ $zpSandbox === '1' ? 'selected' : '' }}>{{ __('تست') }}</option>
                        <option value="0" {{ $zpSandbox !== '1' ? 'selected' : '' }}>{{ __('واقعی') }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="glass-label">{{ __('مرچنت‌کد (Merchant ID)') }}</label>
                <input type="text" name="zp_merchant_id" value="{{ old('zp_merchant_id', $zpMerchantId) }}" dir="ltr" class="glass-input text-start" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
            </div>
            <p class="text-xs text-slate-400">{{ __('بعد از فعال‌سازی، گزینه «پرداخت آنلاین» در صفحه خرید ظاهر و سفارش به‌صورت خودکار فعال می‌شود.') }}</p>
        </div>
    </div>

    <div class="glass-card p-6">
        <h2 class="font-black text-slate-800 dark:text-white">₿ {{ __('پرداخت کریپتو (NOWPayments)') }}</h2>
        <div class="mt-4 space-y-4 text-sm">
            <div>
                <label class="glass-label">{{ __('فعال بودن پرداخت کریپتو') }}</label>
                <select name="np_enabled" class="glass-input">
                    <option value="1" {{ $npEnabled === '1' ? 'selected' : '' }}>{{ __('فعال') }}</option>
                    <option value="0" {{ $npEnabled !== '1' ? 'selected' : '' }}>{{ __('غیرفعال') }}</option>
                </select>
            </div>
            <div>
                <label class="glass-label">{{ __('کلید API (از داشبورد NOWPayments)') }}</label>
                <input type="text" name="np_api_key" value="{{ old('np_api_key', $npApiKey) }}" dir="ltr" class="glass-input text-start">
            </div>
            <div>
                <label class="glass-label">{{ __('رمز IPN (برای تایید وبهوک)') }}</label>
                <input type="text" name="np_ipn_secret" value="{{ old('np_ipn_secret', $npIpnSecret) }}" dir="ltr" class="glass-input text-start">
            </div>
            <div>
                <label class="glass-label">{{ __('نرخ تبدیل دلار به تومان (برای محاسبه مبلغ فاکتور)') }}</label>
                <input type="number" name="np_usd_rate_toman" value="{{ old('np_usd_rate_toman', $npUsdRate) }}" min="1000" dir="ltr" class="glass-input text-start">
            </div>
            <p class="rounded-2xl border border-slate-900/10 bg-white/40 p-3 text-xs text-slate-500 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-400">
                {{ __('آدرس IPN:') }} <b dir="ltr">{{ rtrim(config('app.url'), '/') }}/webhooks/nowpayments</b>
            </p>
        </div>
    </div>

    <div class="glass-card p-6">
        <h2 class="font-black text-slate-800 dark:text-white">📩 {{ __('اعلان پیامکی (کاوه‌نگار)') }}</h2>
        <div class="mt-4 space-y-4 text-sm">
            <div>
                <label class="glass-label">{{ __('فعال بودن پیامک') }}</label>
                <select name="sms_enabled" class="glass-input">
                    <option value="1" {{ $smsEnabled === '1' ? 'selected' : '' }}>{{ __('فعال') }}</option>
                    <option value="0" {{ $smsEnabled !== '1' ? 'selected' : '' }}>{{ __('غیرفعال') }}</option>
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="glass-label">{{ __('کلید API کاوه‌نگار') }}</label>
                    <input type="text" name="sms_api_key" value="{{ old('sms_api_key', $smsApiKey) }}" dir="ltr" class="glass-input text-start">
                </div>
                <div>
                    <label class="glass-label">{{ __('شماره فرستنده') }}</label>
                    <input type="text" name="sms_sender" value="{{ old('sms_sender', $smsSender) }}" dir="ltr" class="glass-input text-start">
                </div>
            </div>
            <p class="text-xs text-slate-400">{{ __('یادآوری انقضا و تایید سفارش برای کاربرانی که تلگرام ندارند، پیامک می‌شود.') }}</p>
        </div>
    </div>

    <button class="btn-primary">{{ __('ذخیره تنظیمات') }}</button>
</form>

<script>
    function addCardRow() {
        var row = document.querySelector('[data-card-row]').cloneNode(true);
        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
        document.getElementById('cards').appendChild(row);
    }
</script>
@endsection