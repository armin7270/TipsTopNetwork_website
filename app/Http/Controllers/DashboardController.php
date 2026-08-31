<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\QrService;
use App\Services\Vpn\SubscriptionService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    public function index(): View
    {
        $user = auth()->user();

        $orders = $user->orders()->with('plan')->latest()->take(10)->get();
        $activeOrder = $user->orders()->where('status', Order::STATUS_ACTIVE)->latest()->first();

        $subscriptionUrl = $this->subscriptionService->urlFor($user);

        try {
            $configs = $this->subscriptionService->configsFor($user);
        } catch (\Throwable) {
            $configs = [];
        }

        return view('dashboard', [
            'orders' => $orders,
            'activeOrder' => $activeOrder,
            'subscriptionUrl' => $subscriptionUrl,
            'subscriptionQr' => QrService::svgDataUri($subscriptionUrl),
            'configs' => $configs,
        ]);
    }
}
