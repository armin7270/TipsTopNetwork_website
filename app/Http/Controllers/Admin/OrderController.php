<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Vpn\ProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $orders = Order::query()
            ->with(['user', 'plan'])
            ->latest()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => Order::STATUSES,
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders_show', [
            'order' => $order->load(['user', 'plan', 'inbounds.server']),
        ]);
    }

    public function destroy(Order $order, ProvisioningService $provisioning): RedirectResponse
    {
        try {
            $provisioning->disable($order);
        } catch (\Throwable) {
        }

        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', __('سفارش حذف شد و کانفیگ آن از پنل حذف گردید.'));
    }
}
