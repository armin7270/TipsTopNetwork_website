<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * مشاهده ردپای فعالیت‌های مدیریتی (فقط مدیرکل)
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $action = $request->query('action', '');

        $logs = AdminActivityLog::query()
            ->with('admin')
            ->latest()
            ->when($action, fn ($query) => $query->where('action', $action))
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('details', 'like', "%{$q}%")
                ->orWhereHas('admin', fn ($a) => $a->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))))
            ->paginate(25)
            ->withQueryString();

        return view('admin.activity-logs', [
            'logs' => $logs,
            'q' => $q,
            'action' => $action,
            'actions' => AdminActivityLog::ACTIONS,
        ]);
    }
}
