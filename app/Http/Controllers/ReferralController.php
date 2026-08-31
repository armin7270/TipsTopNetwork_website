<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * سیستم دعوت از دوستان (رفرال) — سمت کاربر
 */
class ReferralController extends Controller
{
    public function index(Request $request, ReferralService $referrals): View
    {
        $user = $request->user();

        $link = rtrim(config('app.url'), '/').'/register?ref='.$user->referral_code;

        return view('referrals.index', [
            'link' => $link,
            'code' => $user->referral_code,
            'stats' => $referrals->statsFor($user),
            'referrals' => $user->referrals()->latest()->take(20)->get(),
            'welcomeAmount' => (int) Setting::get('referral_welcome_amount', 0),
            'rewardAmount' => (int) Setting::get('referral_reward_amount', 0),
        ]);
    }
}
