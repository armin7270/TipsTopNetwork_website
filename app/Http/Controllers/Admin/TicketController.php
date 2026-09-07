<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\AdminLog;
use App\Services\NotificationService;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * تیکت‌های پشتیبانی — سمت مدیر
 */
class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', '');
        $q = trim((string) $request->query('q', ''));

        $tickets = Ticket::query()
            ->with(['user', 'replies'])
            ->when(in_array($status, [Ticket::STATUS_OPEN, Ticket::STATUS_ANSWERED, Ticket::STATUS_CLOSED], true),
                fn ($query) => $query->where('status', $status))
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->when(is_numeric($q), fn ($w2) => $w2->where('id', $q))
                ->orWhere('subject', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"))))
            ->latest('last_reply_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'status' => $status,
            'q' => $q,
            'openCount' => Ticket::query()->where('status', Ticket::STATUS_OPEN)->count(),
            'answeredCount' => Ticket::query()->where('status', Ticket::STATUS_ANSWERED)->count(),
        ]);
    }

    public function show(Request $request, Ticket $ticket): View
    {
        // بعد از مشاهده توسط مدیر، وضعیت از «باز» به «پاسخ داده شده» تغییر نمی‌کند؛
        // فقط پاسخ کاربر خوانده می‌شود.
        $ticket->load(['user', 'replies.user']);

        return view('admin.tickets.show', ['ticket' => $ticket]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,zip'],
        ], [
            'message.required' => __('متن پاسخ الزامی است.'),
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'is_staff' => true,
            'message' => $validated['message'],
            'attachment' => isset($validated['attachment'])
                ? $validated['attachment']->store('ticket-attachments', 'public')
                : null,
        ]);

        $ticket->update(['status' => Ticket::STATUS_ANSWERED, 'last_reply_at' => now()]);

        NotificationService::send(
            $ticket->user,
            'ticket_answered',
            __('پاسخ جدید به تیکت #'.$ticket->id),
            'موضوع: '.$ticket->subject,
            route('tickets.show', $ticket),
        );

        // اطلاع تلگرامی به کاربر در صورت اتصال ربات (مشابه vPanel)
        if ($ticket->user->telegram_chat_id && Setting::get('tg_bot_enabled') === '1') {
            try {
                app(TelegramClient::class)->sendMessage(
                    $ticket->user->telegram_chat_id,
                    "📩 <b>پاسخ جدید به تیکت #{$ticket->id}</b>\n\n".
                    'موضوع: '.e($ticket->subject)."\n".
                    ('پاسخ: '.e(Str::limit($validated['message'], 400))),
                    [[['text' => '✍️ مشاهده تیکت', 'callback_data' => 'ticketshow:'.$ticket->id]]]
                );
            } catch (\Throwable) {
                // non-blocking
            }
        }

        return back()->with('success', __('پاسخ شما ثبت شد و به کاربر اطلاع داده شد.'));
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        $ticket->update(['status' => Ticket::STATUS_CLOSED]);

        AdminLog::record($request->user(), 'ticket_closed', $ticket);

        return back()->with('success', __('تیکت بسته شد.'));
    }

    public function reopen(Request $request, Ticket $ticket): RedirectResponse
    {
        $ticket->update(['status' => Ticket::STATUS_OPEN]);

        AdminLog::record($request->user(), 'ticket_reopened', $ticket);

        return back()->with('success', __('تیکت مجدداً باز شد.'));
    }
}
