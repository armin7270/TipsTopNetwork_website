<?php

namespace App\Services\Payments;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * پرداخت کریپتو با NOWPayments — ساخت فاکتور + تایید خودکار از طریق IPN
 * مستندات: https://documenter.getpostman.com/view/7907941/S1a32n38
 */
class NowPaymentsService
{
    public static function isEnabled(): bool
    {
        return Setting::get('np_enabled', '0') === '1'
            && trim((string) Setting::get('np_api_key', '')) !== '';
    }

    protected static function apiKey(): string
    {
        return trim((string) Setting::get('np_api_key', ''));
    }

    /**
     * نرخ تبدیل تومان به دلار (قابل تنظیم از پنل)
     */
    public static function tomanToUsd(int $amountToman): float
    {
        $rate = max(1000, (int) Setting::get('np_usd_rate_toman', 100000));

        return round($amountToman / $rate, 2);
    }

    /**
     * ساخت فاکتور پرداخت — برمی‌گرداند ['invoice_id' => ..., 'url' => ...]
     *
     * @throws \RuntimeException
     */
    public static function createInvoice(int $depositId, int $amountToman, string $successUrl, string $cancelUrl): array
    {
        if (! self::isEnabled()) {
            throw new \RuntimeException(__('پرداخت کریپتو فعال نیست.'));
        }

        $usd = self::tomanToUsd($amountToman);

        if ($usd <= 0) {
            throw new \RuntimeException(__('مبلغ پرداخت معتبر نیست.'));
        }

        try {
            $response = Http::timeout(25)
                ->withHeaders(['x-api-key' => self::apiKey()])
                ->post('https://api.nowpayments.io/v1/invoice', [
                    'price_amount' => $usd,
                    'price_currency' => 'usd',
                    'order_id' => (string) $depositId,
                    'order_description' => 'Wallet charge #'.$depositId,
                    'ipn_callback_url' => route('webhooks.nowpayments'),
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'is_fixed_rate' => false,
                    'is_fee_paid_by_user' => false,
                ])
                ->json();

            if (empty($response['id']) || empty($response['invoice_url'])) {
                Log::warning('nowpayments invoice failed', ['response' => $response]);
                throw new \RuntimeException(__('ساخت فاکتور کریپتو ناموفق بود. دوباره تلاش کنید.'));
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('nowpayments invoice error: '.$e->getMessage());
            throw new \RuntimeException(__('ارتباط با درگاه کریپتو برقرار نشد. دوباره تلاش کنید.'));
        }

        return ['invoice_id' => (string) $response['id'], 'url' => (string) $response['invoice_url']];
    }
}
