<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * تیکت‌های پشتیبانی — سمت کاربر
 */
class TicketController extends Controller
{
    public function index(Request $request): View
    {
        return view('tickets.index', [
            'tickets' => $request->user()->tickets()->withCount('replies')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', [
            'priorities' => Ticket::PRIORITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'priority' => ['required', 'in:low,medium,high'],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,zip'],
        ], [
            'subject.required' => __('موضوع تیکت الزامی است.'),
            'message.required' => __('متن پیام الزامی است.'),
            'attachment.max' => __('حجم فایل پیوست حداکثر ۵ مگابایت است.'),
        ]);

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'status' => Ticket::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'is_staff' => false,
            'message' => $validated['message'],
            'attachment' => isset($validated['attachment'])
                ? $validated['attachment']->store('ticket-attachments', 'public')
                : null,
        ]);

        NotificationService::notifyAdmins(
            'ticket_created',
            __('تیکت جدید ثبت شد'),
            $request->user()->name.': '.$validated['subject'],
            route('admin.tickets.show', $ticket),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', __('تیکت شما ثبت شد. به‌محض پاسخ کارشناس اطلاع داده می‌شود.'));
    }

    public function show(Request $request, Ticket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        // تیکت‌های دارای پاسخ کارشناس، بعد از مشاهده «خوانده‌شده» می‌شوند
        if ($ticket->status === Ticket::STATUS_ANSWERED) {
            $ticket->update(['status' => Ticket::STATUS_OPEN]);
        }

        $ticket->load('replies.user');

        return view('tickets.show', ['ticket' => $ticket]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);
        abort_unless($ticket->status !== Ticket::STATUS_CLOSED, 403, __('این تیکت بسته شده است.'));

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,zip'],
        ], [
            'message.required' => __('متن پاسخ الزامی است.'),
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'is_staff' => false,
            'message' => $validated['message'],
            'attachment' => isset($validated['attachment'])
                ? $validated['attachment']->store('ticket-attachments', 'public')
                : null,
        ]);

        $ticket->update(['status' => Ticket::STATUS_OPEN, 'last_reply_at' => now()]);

        NotificationService::notifyAdmins(
            'ticket_replied',
            __('پاسخ جدید در تیکت #'.$ticket->id),
            $request->user()->name.' به تیکت پاسخ داد.',
            route('admin.tickets.show', $ticket),
        );

        return back()->with('success', __('پاسخ شما ثبت شد.'));
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);

        $ticket->update(['status' => Ticket::STATUS_CLOSED]);

        return back()->with('success', __('تیکت بسته شد.'));
    }
}
