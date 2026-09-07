<?php

namespace App\Services\Payments;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * درگاه پرداخت زرین‌پال (پرداخت آنلاین + فعال‌سازی خودکار سفارش)
 * مستندات: https://docs.zarinpal.com
 */
class ZarinPalService
{
    public static function isEnabled(): bool
    {
        return Setting::get('zp_enabled', '0') === '1'
            && trim((string) Setting::get('zp_merchant_id', '')) !== '';
    }

    public static function isSandbox(): bool
    {
        return Setting::get('zp_sandbox', '0') === '1';
    }

    protected static function merchantId(): string
    {
        return trim((string) Setting::get('zp_merchant_id', ''));
    }

    protected static function baseUrl(): string
    {
        return self::isSandbox()
            ? 'https://sandbox.zarinpal.com/pg/rest/WebGate'
            : 'https://api.zarinpal.com/pg/v4/payment';
    }

    public static function startPayUrl(string $authority): string
    {
        return (self::isSandbox() ? 'https://sandbox.zarinpal.com/pg/StartPay/' : 'https://www.zarinpal.com/pg/StartPay/').$authority;
    }

    /**
     * ساخت تراکنش در زرین‌پال — برمی‌گرداند ['authority' => ..., 'url' => ...]
     *
     * @throws \RuntimeException
     */
    public static function requestPayment(int $amountToman, string $description, string $callbackUrl, ?string $mobile = null): array
    {
        if (! self::isEnabled()) {
            throw new \RuntimeException(__('درگاه پرداخت آنلاین فعال نیست.'));
        }

        if ($amountToman < 1000) {
            throw new \RuntimeException(__('مبلغ پرداخت معتبر نیست.'));
        }

        try {
            if (self::isSandbox()) {
                $response = Http::timeout(20)->post(self::baseUrl().'/PaymentRequest.json', [
                    'MerchantID' => self::merchantId(),
                    'Amount' => $amountToman,
                    'Description' => mb_substr($description, 0, 200),
                    'CallbackURL' => $callbackUrl,
                    'Mobile' => $mobile,
                ])->json();

                if (($response['Status'] ?? -1) != 100 || empty($response['Authority'])) {
                    throw new \RuntimeException(__('خطای درگاه (کد :code)', ['code' => $response['Status'] ?? '?']));
                }

                $authority = $response['Authority'];
            } else {
                $response = Http::timeout(20)->post(self::baseUrl().'/request.json', [
                    'merchant_id' => self::merchantId(),
                    'amount' => $amountToman,
                    'callback_url' => $callbackUrl,
                    'description' => mb_substr($description, 0, 200),
                    'metadata' => array_filter(['mobile' => $mobile]),
                ])->json();

                if (($response['data']['code'] ?? -1) != 100 || empty($response['data']['authority'])) {
                    $message = $response['errors']['message'] ?? __('خطای درگاه (کد :code)', ['code' => $response['data']['code'] ?? '?']);
                    throw new \RuntimeException($message);
                }

                $authority = $response['data']['authority'];
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('zarinpal request failed: '.$e->getMessage());
            throw new \RuntimeException(__('ارتباط با درگاه پرداخت برقرار نشد. دوباره تلاش کنید.'));
        }

        return ['authority' => $authority, 'url' => self::startPayUrl($authority)];
    }

    /**
     * تایید تراکنش — برمی‌گرداند کد پیگیری (ref_id)
     *
     * @throws \RuntimeException
     */
    public static function verifyPayment(string $authority, int $amountToman): string
    {
        if (! self::isEnabled()) {
            throw new \RuntimeException(__('درگاه پرداخت آنلاین فعال نیست.'));
        }

        try {
            if (self::isSandbox()) {
                $response = Http::timeout(20)->post(self::baseUrl().'/PaymentVerification.json', [
                    'MerchantID' => self::merchantId(),
                    'Authority' => $authority,
                    'Amount' => $amountToman,
                ])->json();

                if (! in_array($response['Status'] ?? -1, [100, 101], true) || empty($response['RefID'])) {
                    throw new \RuntimeException(__('تایید پرداخت ناموفق بود (کد :code).', ['code' => $response['Status'] ?? '?']));
                }

                return (string) $response['RefID'];
            }

            $response = Http::timeout(20)->post(self::baseUrl().'/verify.json', [
                'merchant_id' => self::merchantId(),
                'authority' => $authority,
                'amount' => $amountToman,
            ])->json();

            if (! in_array($response['data']['code'] ?? -1, [100, 101], true) || empty($response['data']['ref_id'])) {
                $message = $response['errors']['message'] ?? __('تایید پرداخت ناموفق بود.');
                throw new \RuntimeException($message);
            }

            return (string) $response['data']['ref_id'];
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('zarinpal verify failed: '.$e->getMessage());
            throw new \RuntimeException(__('ارتباط با درگاه پرداخت برقرار نشد. اگر وجه کسر شده، با پشتیبانی تماس بگیرید.'));
        }
    }
}
