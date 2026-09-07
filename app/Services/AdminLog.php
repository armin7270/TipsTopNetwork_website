<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * ثبت ردپای فعالیت‌های مدیریتی
 */
class AdminLog
{
    public static function record(?User $admin, string $action, Model|string|null $subject = null, ?string $details = null): void
    {
        try {
            $subjectType = null;
            $subjectId = null;

            if ($subject instanceof Model) {
                $subjectType = class_basename($subject);
                $subjectId = $subject->getKey();
            } elseif (is_string($subject)) {
                $details = trim(($details ? $details.' — ' : '').$subject) ?: null;
            }

            AdminActivityLog::create([
                'admin_id' => $admin?->id,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'details' => $details,
                'ip' => Request::ip(),
            ]);
        } catch (\Throwable) {
            // لاگ نباید هیچ‌وقت عملیات اصلی را خراب کند
        }
    }
}
