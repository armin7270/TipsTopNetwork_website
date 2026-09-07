<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\AdminLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/**
 * ویزارد راه‌اندازی قدم‌به‌قدم برای مدیر تازه‌کار
 */
class SetupController extends Controller
{
    public function index(): View
    {
        $cards = Setting::getJson('cards', []);
        $tgToken = trim((string) Setting::get('tg_bot_token', ''));

        $steps = [
            [
                'title' => __('۱. افزودن سرور 3x-ui'),
                'done' => Server::query()->exists(),
                'hint' => __('اطلاعات API پنل 3x-ui را وارد کنید.'),
                'url' => route('admin.inbounds.index'),
                'cta' => __('مدیریت سرورها'),
            ],
            [
                'title' => __('۲. ایمپورت اینباندها از پنل'),
                'done' => Inbound::query()->where('is_active', true)->exists(),
                'hint' => __('با دکمه «دریافت از پنل» اینباند‌ها را وارد کنید.'),
                'url' => route('admin.inbounds.index'),
                'cta' => __('مدیریت سرورها'),
            ],
            [
                'title' => __('۳. ساخت پلن فروش'),
                'done' => Plan::query()->where('is_active', true)->exists(),
                'hint' => __('قیمت، حجم و اعتبار را مشخص و اینباند‌ها را به پلن وصل کنید.'),
                'url' => route('admin.plans.index'),
                'cta' => __('مدیریت پلن‌ها'),
            ],
            [
                'title' => __('۴. ثبت شماره کارت‌ها'),
                'done' => count($cards) > 0,
                'hint' => __('بدون کارت، مشتری نمی‌تواند وجه را واریز کند.'),
                'url' => route('admin.settings.edit'),
                'cta' => __('تنظیمات'),
            ],
            [
                'title' => __('۵. اتصال ربات تلگرام (اختیاری ولی پیشنهادی)'),
                'done' => $tgToken !== '' && Setting::get('tg_bot_enabled', '0') === '1',
                'hint' => __('توکن ربات را وارد و وبهوک را ست کنید تا فروش در تلگرام هم فعال شود.'),
                'url' => route('admin.settings.edit'),
                'cta' => __('تنظیمات'),
            ],
            [
                'title' => __('۶. بررسی صف پرداخت آزمایشی'),
                'done' => Order::query()->exists() || Transaction::query()->where('type', Transaction::TYPE_DEPOSIT)->exists(),
                'hint' => __('یک خرید آزمایشی انجام دهید و تایید/فعال‌سازی را تست کنید.'),
                'url' => route('admin.payments.index'),
                'cta' => __('صف پرداخت‌ها'),
            ],
            [
                'title' => __('۷. پاسخ به تیکت‌ها'),
                'done' => Ticket::query()->where('status', Ticket::STATUS_OPEN)->doesntExist() || Ticket::query()->exists(),
                'hint' => __('تیکت‌های باز را از این بخش پیگیری کنید.'),
                'url' => route('admin.tickets.index'),
                'cta' => __('تیکت‌ها'),
            ],
        ];

        $doneCount = collect($steps)->where('done', true)->count();

        return view('admin.setup', [
            'steps' => $steps,
            'doneCount' => $doneCount,
            'totalCount' => count($steps),
            'complete' => $doneCount === count($steps),
            'storageLinked' => is_link(public_path('storage')) || is_dir(public_path('storage')),
        ]);
    }

    /**
     * ساخت لینک public/storage (برای هاست‌هایی که SSH ندارند)
     */
    public function linkStorage(Request $request): RedirectResponse
    {
        try {
            Artisan::call('storage:link');
            AdminLog::record($request->user(), 'settings_updated', null, 'storage:link');

            return back()->with('success', __('لینک storage ساخته شد. ✅'));
        } catch (\Throwable $e) {
            return back()->with('error', __('ساخت لینک ناموفق بود: :msg', ['msg' => $e->getMessage()]));
        }
    }
}
