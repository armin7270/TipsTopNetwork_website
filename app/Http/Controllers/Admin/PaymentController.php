<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ActivatePaidOrder;
use App\Models\Order;
use App\Services\AdminLog;
use App\Services\NotificationService;
use App\Support\CsvExport;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Order::query()
            ->where('status', Order::STATUS_AWAITING_VERIFICATION)
            ->with(['user', 'plan'])
            ->latest('paid_at')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('id', $q)
                ->orWhere('bank_reference', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"))));

        if ($request->query('export') === 'csv') {
            return CsvExport::download('payments-'.now()->format('Ymd-Hi').'.csv',
                ['شناسه', 'کاربر', 'موبایل', 'پلن', 'مبلغ پرداختی', 'کد پیگیری', 'زمان پرداخت'],
                $query->cursor()->map(fn (Order $o) => [
                    $o->id, $o->user?->name, $o->user?->phone, $o->plan_name,
                    $o->paid_amount, $o->bank_reference, Format::date($o->paid_at),
                ])->all());
        }

        $orders = $query->paginate(20)->withQueryString();

        return view('admin.payments', ['orders' => $orders, 'q' => $q]);
    }

    public function approve(Request $request, Order $order): RedirectResponse
    {
        if ($order->status !== Order::STATUS_AWAITING_VERIFICATION) {
            return back()->with('error', __('این سفارش در وضعیت «در انتظار تایید» نیست.'));
        }

        $order->update(['verified_by' => $request->user()->id]);

        // فعال‌سازی از طریق صف — در صورت خطای موقت پنل، به‌صورت خودکار retry می‌شود.
        // با صف sync (لوکال/تست) اجرای همگام مثل قبل خطا را همین‌جا نشان می‌دهد.
        try {
            ActivatePaidOrder::dispatch($order->id);
        } catch (\Throwable $e) {
            $order->update(['verified_by' => null]);

            return back()->with('error', __('خطا در ساخت کانفیگ').': '.$e->getMessage());
        }

        AdminLog::record($request->user(), 'payment_approved', $order, number_format($order->price_toman).' تومان');

        $order->refresh();

        if ($order->status === Order::STATUS_ACTIVE) {
            return back()->with('success', __('سفارش #:id تایید شد و کانفیگ در پنل ساخته شد. ✅', ['id' => $order->id]));
        }

        return back()->with('success', __('سفارش #:id تایید شد. فعال‌سازی در صف قرار گرفت و به‌محض موفقیت به کاربر اطلاع داده می‌شود. ⏳', ['id' => $order->id]));
    }

    public function reject(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ], [
            'note.required' => __('دلیل رد پرداخت را وارد کنید (برای نمایش به کاربر).'),
        ]);

        if ($order->status !== Order::STATUS_AWAITING_VERIFICATION) {
            return back()->with('error', __('این سفارش در وضعیت «در انتظار تایید» نیست.'));
        }

        $order->update([
            'status' => Order::STATUS_REJECTED,
            'admin_note' => $validated['note'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        NotificationService::send(
            $order->user,
            'order_rejected',
            __('پرداخت سفارش #:id تایید نشد', ['id' => $order->id]),
            __('دلیل: :note', ['note' => $validated['note']]),
            route('orders.show', $order),
        );

        AdminLog::record($request->user(), 'payment_rejected', $order, $validated['note']);

        return back()->with('success', __('پرداخت سفارش #:id رد شد.', ['id' => $order->id]));
    }
}
