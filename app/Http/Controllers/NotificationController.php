<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * نوتیفیکیشن‌های درون‌برنامه‌ای کاربر
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
            'unreadCount' => $request->user()->notifications()->unread()->count(),
        ]);
    }

    public function read(Request $request, int $notificationId): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notificationId);

        $notification->update(['read_at' => now()]);

        if ($notification->url) {
            return redirect($notification->url);
        }

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->notifications()->unread()->update(['read_at' => now()]);

        return back()->with('success', __('همه نوتیفیکیشن‌ها خوانده شدند.'));
    }

    public function destroy(Request $request, int $notificationId): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notificationId)->delete();

        return back()->with('success', __('نوتیفیکیشن حذف شد.'));
    }
}
