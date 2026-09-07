<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AdminLog;
use App\Services\Vpn\ProvisioningService;
use App\Support\CsvExport;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $query = Order::query()
            ->with(['user', 'plan'])
            ->latest()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('id', $q)
                ->orWhere('plan_name', 'like', "%{$q}%")
                ->orWhere('bank_reference', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"))));

        if ($request->query('export') === 'csv') {
            return CsvExport::download('orders-'.now()->format('Ymd-Hi').'.csv',
                ['شناسه', 'کاربر', 'موبایل', 'پلن', 'قیمت (تومان)', 'وضعیت', 'روش پرداخت', 'تاریخ ثبت'],
                $query->cursor()->map(fn (Order $o) => [
                    $o->id, $o->user?->name, $o->user?->phone, $o->plan_name,
                    $o->price_toman, $o->statusLabel(), $o->payment_method, Format::date($o->created_at),
                ])->all());
        }

        $orders = $query->paginate(25)->withQueryString();

        return view('admin.orders', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => Order::STATUSES,
            'q' => $q,
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders_show', [
            'order' => $order->load(['user', 'plan', 'inbounds.server']),
        ]);
    }

    public function destroy(Request $request, Order $order, ProvisioningService $provisioning): RedirectResponse
    {
        try {
            $provisioning->disable($order);
        } catch (\Throwable) {
        }

        AdminLog::record($request->user(), 'order_deleted', $order, '#'.$order->id.' — '.$order->plan_name);
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', __('سفارش حذف شد و کانفیگ آن از پنل حذف گردید.'));
    }
}
