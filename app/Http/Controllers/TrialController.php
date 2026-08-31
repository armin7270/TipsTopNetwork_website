<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\TrialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * اکانت تست — سمت کاربر
 */
class TrialController extends Controller
{
    public function index(Request $request): View
    {
        return view('trials.index', [
            'trials' => $request->user()->trials()->paginate(10),
            'enabled' => Setting::get('trial_enabled', '0') === '1',
            'limit' => max(1, (int) Setting::get('trial_limit_per_user', 1)),
            'taken' => $request->user()->trial_accounts_taken,
        ]);
    }

    public function request(Request $request, TrialService $trials): RedirectResponse
    {
        try {
            $trials->request($request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('خطا در ساخت اکانت تست. لطفاً بعداً تلاش کنید.'));
        }

        return back()->with('success', __('اکانت تست شما فعال شد! جزئیات در همین صفحه قابل مشاهده است.'));
    }
}
