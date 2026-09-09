<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\DiscountService;
use App\Services\OrderService;
use App\Services\Payments\ZarinPalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuyController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    /**
     * نمایش صفحه تایید خرید (انتخاب روش پرداخت)
     */
    public function show(Request $request, Plan $plan): View
    {
        abort_unless($plan->is_active, 404);

        return view('buy.confirm', [
            'plan' => $plan,
            'walletBalance' => $request->user()->balance,
            'onlineEnabled' => ZarinPalService::isEnabled(),
            'discountCode' => session('discount_code'),
            'discountAmount' => session('discount_amount', 0),
            'finalPrice' => max(0, $plan->price_toman - (int) session('discount_amount', 0)),
        ]);
    }

    /**
     * اعمال کد تخفیف روی پلن (پیش از پرداخت)
     */
    public function applyDiscount(Request $request, Plan $plan, DiscountService $discounts): RedirectResponse
    {
        abort_unless($plan->is_active, 404);

        [$discount, $error] = $discounts->lookup((string) $request->input('code', ''), $plan->price_toman);

        if ($error || ! $discount) {
            return back()->with('error', $error ?? __('کد تخفیف نامعتبر است.'));
        }

        $amount = $discount->discountFor($plan->price_toman);

        session([
            'discount_code_id' => $discount->id,
            'discount_code' => $discount->code,
            'discount_amount' => $amount,
        ]);

        return back()->with('success', __('کد تخفیف اعمال شد: :amount تومان تخفیف 🎉', ['amount' => number_format($amount)]));
    }

    public function removeDiscount(): RedirectResponse
    {
        session()->forget(['discount_code_id', 'discount_code', 'discount_amount']);

        return back()->with('success', __('کد تخفیف حذف شد.'));
    }

    /**
     * ثبت سفارش
     */
    public function store(Request $request, Plan $plan, DiscountService $discounts): RedirectResponse
    {
        abort_unless($plan->is_active, 404);

        $user = $request->user();

        $paymentMethod = $request->input('payment_method', 'card');

        abort_unless(in_array($paymentMethod, ['card', 'wallet', 'online'], true), 400);

        // قیمت نهایی = پلن - تخفیف سشن (در صورت اعتبار)
        $price = $plan->price_toman;
        $discount = null;
        $discountAmount = 0;

        if (session('discount_code_id')) {
            [$discount, $error] = $discounts->lookup((string) session('discount_code'), $price);

            if ($discount && ! $error) {
                $discountAmount = $discount->discountFor($price);
                $price = max(0, $price - $discountAmount);
            } else {
                // کد سشن دیگر معتبر نیست — پاک می‌شود
                session()->forget(['discount_code_id', 'discount_code', 'discount_amount']);
            }
        }

        // ساخت سفارش (تمدید خودکار در صورت وجود سرویس فعال)
        $order = $this->orders->createForPlan($user, $plan, source: 'web', paymentMethod: $paymentMethod);

        if ($discount && $discountAmount > 0) {
            $order->update([
                'discount_code_id' => $discount->id,
                'discount_amount' => $discountAmount,
                'price_toman' => $price,
            ]);

            $discounts->consume($discount);
            session()->forget(['discount_code_id', 'discount_code', 'discount_amount']);
        }

        // پرداخت آنلاین (زرین‌پال) — هدایت به درگاه
        if ($paymentMethod === 'online') {
            if (! ZarinPalService::isEnabled()) {
                return redirect()
                    ->route('orders.show', $order)
                    ->with('error', __('پرداخت آنلاین فعال نیست. روش دیگری انتخاب کنید.'));
            }

            try {
                $payment = ZarinPalService::requestPayment(
                    (int) $order->price_toman,
                    __('خرید :plan — :site', ['plan' => $order->plan_name, 'site' => config('app.name')]),
                    route('payment.callback', ['order' => $order->id]),
                    preg_match('/^09\d{9}$/', (string) $user->phone) ? $user->phone : null,
                );
            } catch (\Throwable $e) {
                return redirect()
                    ->route('orders.show', $order)
                    ->with('error', $e->getMessage());
            }

            return redirect()->away($payment['url']);
        }

        // پرداخت آنی با کیف پول
        if ($paymentMethod === 'wallet') {
            try {
                $this->orders->payWithWallet($order);
            } catch (\Throwable $e) {
                // در صورت خطا (موجودی ناکافی یا خطای پنل) به صفحه سفارش می‌رویم
                return redirect()
                    ->route('orders.show', $order)
                    ->with('error', $e->getMessage());
            }

            return redirect()
                ->route('orders.show', $order)
                ->with('success', $order->isRenewal()
                    ? __('سرویس شما با موفقیت تمدید شد! 🎉')
                    : __('پرداخت با موفقیت انجام شد و سرویس شما فعال شد! 🎉'));
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', __('سفارش شما ایجاد شد. لطفاً مبلغ را واریز کنید و رسید را ثبت نمایید.'));
    }
}
