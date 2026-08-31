<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with('plan')
            ->latest()
            ->paginate(15);

        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        $order->load('inbounds.server');

        return view('orders.show', [
            'order' => $order,
            'cards' => Setting::getJson('cards', []),
        ]);
    }

    public function submitReceipt(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        if (! in_array($order->status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_REJECTED], true)) {
            return back()->with('error', __('برای این سفارش نمی‌توان رسید ثبت کرد.'));
        }

        $validated = $request->validate([
            'paid_amount' => ['required', 'integer', 'min:1000'],
            'bank_reference' => ['required', 'string', 'min:4', 'max:100'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
        ], [
            'paid_amount.required' => __('مبلغ واریزی الزامی است.'),
            'bank_reference.required' => __('کد پیگیری/شماره ارجاع الزامی است.'),
            'paid_at.before_or_equal' => __('زمان پرداخت نمی‌تواند در آینده باشد.'),
        ]);

        $order->update($validated + [
            'status' => Order::STATUS_AWAITING_VERIFICATION,
            'admin_note' => null,
        ]);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', __('رسید شما ثبت شد. پس از بررسی و تایید مدیر، کانفیگ به‌صورت خودکار فعال می‌شود.'));
    }

    public function payWithWallet(Request $request, Order $order, OrderService $orderService): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        if (! in_array($order->status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_REJECTED], true)) {
            return back()->with('error', __('این سفارش در وضعیت قابل پرداخت نیست.'));
        }

        try {
            $orderService->payWithWallet($order);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', $order->isRenewal()
                ? __('سرویس شما با موفقیت تمدید شد! 🎉')
                : __('پرداخت با موفقیت انجام شد و سرویس شما فعال شد! 🎉'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        if (! in_array($order->status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_AWAITING_VERIFICATION, Order::STATUS_REJECTED], true)) {
            return back()->with('error', __('این سفارش قابل لغو نیست.'));
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return redirect()->route('orders.index')->with('success', __('سفارش لغو شد.'));
    }

    protected function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 403);
    }
}
