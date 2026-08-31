<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price_toman')
            ->get();

        return view('home', [
            'plans' => $plans,
            'siteName' => Setting::get('site_name', 'TipStop Network'),
            'supportTelegram' => Setting::get('support_telegram', ''),
        ]);
    }
}
