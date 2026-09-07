<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\Setting;
use App\Services\AdminLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'siteName' => Setting::get('site_name', 'TipStop Network'),
            'supportTelegram' => Setting::get('support_telegram', ''),
            'subBaseUrl' => Setting::get('sub_base_url', ''),
            'cards' => Setting::getJson('cards', []),
            // تلگرام
            'tgBotEnabled' => Setting::get('tg_bot_enabled', '0'),
            'tgBotToken' => Setting::get('tg_bot_token', ''),            'tgAdminChatId' => Setting::get('tg_admin_chat_id', ''),
            'tgForceChannel' => Setting::get('tg_force_channel', ''),
            'tgWebhookSecret' => Setting::get('tg_webhook_secret', ''),
            'tgDepositAmounts' => Setting::getJson('tg_deposit_amounts', [50000, 100000, 200000, 500000]),
            // درگاه پرداخت آنلاین (زرین‌پال)
            'zpEnabled' => Setting::get('zp_enabled', '0'),
            'zpMerchantId' => Setting::get('zp_merchant_id', ''),
            'zpSandbox' => Setting::get('zp_sandbox', '0'),
            // پرداخت کریپتو (NOWPayments)
            'npEnabled' => Setting::get('np_enabled', '0'),
            'npApiKey' => Setting::get('np_api_key', ''),
            'npIpnSecret' => Setting::get('nowpayments_ipn_secret', ''),
            'npUsdRate' => Setting::get('np_usd_rate_toman', 100000),
            // پیامک (کاوه‌نگار)
            'smsEnabled' => Setting::get('sms_enabled', '0'),
            'smsApiKey' => Setting::get('sms_api_key', ''),
            'smsSender' => Setting::get('sms_sender', ''),
            // کیف پول
            'walletMinDeposit' => Setting::get('wallet_min_deposit', 10000),
            // معرفی (رفرال)
            'referralEnabled' => Setting::get('referral_enabled', '1'),
            'referralWelcomeAmount' => Setting::get('referral_welcome_amount', 0),
            'referralRewardAmount' => Setting::get('referral_reward_amount', 0),
            'referralMinReferrerBalance' => Setting::get('referral_min_referrer_balance', 0),
            // اکانت تست
            'trialEnabled' => Setting::get('trial_enabled', '0'),
            'trialVolumeMb' => Setting::get('trial_volume_mb', 500),
            'trialDurationHours' => Setting::get('trial_duration_hours', 24),
            'trialLimitPerUser' => Setting::get('trial_limit_per_user', 1),
            'trialInboundId' => Setting::get('trial_inbound_id'),
            'inbounds' => Inbound::query()->where('is_active', true)->with('server')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'support_telegram' => ['nullable', 'string', 'max:100'],
            'sub_base_url' => ['nullable', 'string', 'max:255'],
            'card_bank' => ['nullable', 'array'],
            'card_holder' => ['nullable', 'array'],
            'card_number' => ['nullable', 'array'],
            'card_number.*' => ['nullable', 'string', 'max:30'],
            // تلگرام
            'tg_bot_enabled' => ['nullable', 'in:1,0'],
            'tg_bot_token' => ['nullable', 'string', 'max:191'],
            'tg_admin_chat_id' => ['nullable', 'string', 'max:32'],
            'tg_force_channel' => ['nullable', 'string', 'max:100'],
            'tg_webhook_secret' => ['nullable', 'string', 'max:64'],
            'tg_deposit_amounts' => ['nullable', 'string', 'max:500'],
            // درگاه زرین‌پال
            'zp_enabled' => ['nullable', 'in:1,0'],
            'zp_merchant_id' => ['nullable', 'string', 'max:100'],
            'zp_sandbox' => ['nullable', 'in:1,0'],
            // کریپتو
            'np_enabled' => ['nullable', 'in:1,0'],
            'np_api_key' => ['nullable', 'string', 'max:200'],
            'np_ipn_secret' => ['nullable', 'string', 'max:200'],
            'np_usd_rate_toman' => ['nullable', 'integer', 'min:1000'],
            // پیامک
            'sms_enabled' => ['nullable', 'in:1,0'],
            'sms_api_key' => ['nullable', 'string', 'max:200'],
            'sms_sender' => ['nullable', 'string', 'max:30'],
            // کیف پول
            'wallet_min_deposit' => ['required', 'integer', 'min:1000'],
            // رفرال
            'referral_enabled' => ['nullable', 'in:1,0'],
            'referral_welcome_amount' => ['required', 'integer', 'min:0'],
            'referral_reward_amount' => ['required', 'integer', 'min:0'],
            'referral_min_referrer_balance' => ['required', 'integer', 'min:0'],
            // اکانت تست
            'trial_enabled' => ['nullable', 'in:1,0'],
            'trial_volume_mb' => ['required', 'integer', 'min:50'],
            'trial_duration_hours' => ['required', 'integer', 'min:1'],
            'trial_limit_per_user' => ['required', 'integer', 'min:1'],
            'trial_inbound_id' => ['nullable', 'integer'],
        ], [
            'site_name.required' => __('نام سایت الزامی است.'),
        ]);

        Setting::set('site_name', $validated['site_name']);
        Setting::set('support_telegram', $validated['support_telegram'] ?? '');
        Setting::set('sub_base_url', $validated['sub_base_url'] ?? '');

        $cards = [];
        foreach (($validated['card_number'] ?? []) as $index => $number) {
            $number = preg_replace('/[^0-9]/', '', (string) $number);
            if ($number === '') {
                continue;
            }
            $cards[] = [
                'bank' => trim($validated['card_bank'][$index] ?? ''),
                'holder' => trim($validated['card_holder'][$index] ?? ''),
                'number' => $number,
            ];
        }
        Setting::set('cards', $cards);

        // تلگرام
        Setting::set('tg_bot_enabled', $validated['tg_bot_enabled'] ?? '0');
        Setting::set('tg_bot_token', $validated['tg_bot_token'] ?? '');
        Setting::set('tg_admin_chat_id', $validated['tg_admin_chat_id'] ?? '');
        Setting::set('tg_force_channel', $validated['tg_force_channel'] ?? '');
        Setting::set('tg_webhook_secret', $validated['tg_webhook_secret'] ?? '');

        // مبالغ پیش‌فرض شارژ ربات (با کاما جدا شده — مشابه deposit_amounts در vPanel)
        $depositAmounts = array_values(array_filter(array_map(
            fn ($v) => (int) preg_replace('/[^\d]/', '', (string) $v),
            explode(',', (string) ($validated['tg_deposit_amounts'] ?? '')),
        ), fn ($v) => $v > 0));
        Setting::set('tg_deposit_amounts', $depositAmounts ?: [50000, 100000, 200000, 500000]);

        // کیف پول
        Setting::set('wallet_min_deposit', $validated['wallet_min_deposit']);

        // رفرال
        Setting::set('referral_enabled', $validated['referral_enabled'] ?? '0');
        Setting::set('referral_welcome_amount', $validated['referral_welcome_amount']);
        Setting::set('referral_reward_amount', $validated['referral_reward_amount']);
        Setting::set('referral_min_referrer_balance', $validated['referral_min_referrer_balance']);

        // اکانت تست
        Setting::set('trial_enabled', $validated['trial_enabled'] ?? '0');
        Setting::set('trial_volume_mb', $validated['trial_volume_mb']);
        Setting::set('trial_duration_hours', $validated['trial_duration_hours']);
        Setting::set('trial_limit_per_user', $validated['trial_limit_per_user']);
        Setting::set('trial_inbound_id', $validated['trial_inbound_id'] ?? null);

        // درگاه زرین‌پال
        Setting::set('zp_enabled', $validated['zp_enabled'] ?? '0');
        Setting::set('zp_merchant_id', trim($validated['zp_merchant_id'] ?? ''));
        Setting::set('zp_sandbox', $validated['zp_sandbox'] ?? '0');

        // کریپتو
        Setting::set('np_enabled', $validated['np_enabled'] ?? '0');
        Setting::set('np_api_key', trim($validated['np_api_key'] ?? ''));
        Setting::set('nowpayments_ipn_secret', trim($validated['np_ipn_secret'] ?? ''));
        Setting::set('np_usd_rate_toman', max(1000, (int) ($validated['np_usd_rate_toman'] ?? 100000)));

        // پیامک
        Setting::set('sms_enabled', $validated['sms_enabled'] ?? '0');
        Setting::set('sms_api_key', trim($validated['sms_api_key'] ?? ''));
        Setting::set('sms_sender', trim($validated['sms_sender'] ?? ''));

        AdminLog::record($request->user(), 'settings_updated');

        return back()->with('success', __('تنظیمات ذخیره شد.'));
    }
}
