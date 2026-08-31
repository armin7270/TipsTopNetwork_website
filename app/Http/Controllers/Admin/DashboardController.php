<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\View\View;
use Morilog\Jalali\Jalalian;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::count(),
            'pending_payments' => Order::where('status', Order::STATUS_AWAITING_VERIFICATION)->count(),
            'active_orders' => Order::where('status', Order::STATUS_ACTIVE)->count(),
            'revenue' => Order::whereIn('status', [Order::STATUS_ACTIVE, Order::STATUS_EXPIRED])->sum('price_toman'),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'month_revenue' => Order::whereIn('status', [Order::STATUS_ACTIVE, Order::STATUS_EXPIRED])
                ->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('price_toman'),
            'open_tickets' => Ticket::where('status', Ticket::STATUS_OPEN)->count(),
            'wallet_deposits' => Transaction::where('type', Transaction::TYPE_DEPOSIT)
                ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION])->count(),
        ];

        $recentOrders = Order::with('user')->latest()->take(8)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'chart' => $this->chartData(),
        ]);
    }

    /**
     * داده نمودار ۳۰ روزه سفارش/درآمد (با تاریخ شمسی — مشابه OrdersChart در vPanel)
     */
    protected function chartData(): array
    {
        $days = collect();

        for ($i = 29; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $days->push([
                'label' => Jalalian::fromDateTime($date)->format('m/d'),
                'count' => 0,
                'revenue' => 0,
                'key' => $date->toDateString(),
            ]);
        }

        $orders = Order::query()
            ->whereIn('status', [Order::STATUS_ACTIVE, Order::STATUS_EXPIRED])
            ->whereBetween('created_at', [today()->subDays(29)->startOfDay(), now()])
            ->get()
            ->groupBy(fn ($order) => $order->created_at->toDateString());

        $days = $days->map(function (array $day) use ($orders): array {
            $group = $orders->get($day['key']);

            unset($day['key']);

            $day['count'] = $group?->count() ?? 0;
            $day['revenue'] = (int) ($group?->sum('price_toman') ?? 0);

            return $day;
        });

        return $days->all();
    }
}
