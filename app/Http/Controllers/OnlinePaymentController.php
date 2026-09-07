<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\ZarinPalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * بازگشت از درگاه پرداخت آنلاین (زرین‌پال) — تایید خودکار و فعال‌سازی سفارش
 */
class OnlinePaymentController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function callback(Request $request): RedirectResponse
    {
        $order = Order::query()->whereKey($request->query('order'))->first();

        if (! $order || $order->user_id !== $request->user()->id) {
            return redirect()->route('dashboard')->with('error', __('سفارش یافت نشد.'));
        }

        // قبلاً فعال شده (مثلاً رفرش صفحه بعد از پرداخت موفق)
        if ($order->status === Order::STATUS_ACTIVE) {
            return redirect()->route('orders.show', $order)
                ->with('success', $order->isRenewal()
                    ? __('سرویس شما با موفقیت تمدید شد! 🎉')
                    : __('پرداخت با موفقیت انجام شد و سرویس شما فعال شد! 🎉'));
        }

        $authority = (string) $request->query('Authority', '');
        $status = (string) $request->query('Status', '');

        if ($authority === '' || strtoupper($status) !== 'OK') {
            return redirect()->route('orders.show', $order)
                ->with('error', __('پرداخت لغو شد یا ناموفق بود. می‌توانید دوباره تلاش کنید.'));
        }

        try {
            $refId = ZarinPalService::verifyPayment($authority, (int) $order->price_toman);
        } catch (\Throwable $e) {
            Log::warning('online payment verify failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return redirect()->route('orders.show', $order)->with('error', $e->getMessage());
        }

        try {
            DB::transaction(function () use ($order, $refId) {
                $order->update([
                    'status' => Order::STATUS_AWAITING_VERIFICATION,
                    'paid_amount' => $order->price_toman,
                    'paid_at' => now(),
                    'bank_reference' => 'ZP-'.$refId,
                ]);

                $this->orders->activatePaidOrder($order);
            });
        } catch (\Throwable $e) {
            Log::error('online payment activation failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return redirect()->route('orders.show', $order)
                ->with('error', __('پرداخت تایید شد ولی فعال‌سازی ناموفق بود (کد پیگیری: :ref). با پشتیبانی تماس بگیرید.', ['ref' => $refId]));
        }

        return redirect()->route('orders.show', $order->refresh())
            ->with('success', $order->isRenewal()
                ? __('سرویس شما با موفقیت تمدید شد! 🎉')
                : __('پرداخت با موفقیت انجام شد و سرویس شما فعال شد! 🎉'));
    }
}
