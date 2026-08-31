<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\OrderService;
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
        ]);
    }

    /**
     * ثبت سفارش
     */
    public function store(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($plan->is_active, 404);

        $user = $request->user();

        $paymentMethod = $request->input('payment_method', 'card');

        abort_unless(in_array($paymentMethod, ['card', 'wallet'], true), 400);

        // ساخت سفارش (تمدید خودکار در صورت وجود سرویس فعال)
        $order = $this->orders->createForPlan($user, $plan, source: 'web', paymentMethod: $paymentMethod);

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
