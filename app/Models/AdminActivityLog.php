<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * لاگ فعالیت‌های مدیریتی (ردپای حسابرسی)
 */
class AdminActivityLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'subject_type',
        'subject_id',
        'details',
        'ip',
    ];

    public const ACTIONS = [
        'payment_approved' => 'تایید پرداخت سفارش',
        'payment_rejected' => 'رد پرداخت سفارش',
        'deposit_approved' => 'تایید شارژ کیف پول',
        'deposit_rejected' => 'رد شارژ کیف پول',
        'wallet_adjusted' => 'تنظیم دستی کیف پول',
        'user_blocked' => 'مسدود کردن کاربر',
        'user_unblocked' => 'رفع مسدودی کاربر',
        'user_role_changed' => 'تغییر نقش کاربر',
        'plan_created' => 'ساخت پلن',
        'plan_updated' => 'ویرایش پلن',
        'plan_deleted' => 'حذف پلن',
        'server_created' => 'افزودن سرور',
        'server_updated' => 'ویرایش سرور',
        'server_deleted' => 'حذف سرور',
        'inbound_updated' => 'ویرایش اینباند',
        'inbound_deleted' => 'حذف اینباند',
        'order_deleted' => 'حذف سفارش',
        'ticket_closed' => 'بستن تیکت',
        'ticket_reopened' => 'باز کردن تیکت',
        'broadcast_sent' => 'ارسال برودکست',
        'settings_updated' => 'ویرایش تنظیمات',
        'telegram_sent' => 'پیام تلگرام به کاربر',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function actionLabel(): string
    {
        return __(self::ACTIONS[$this->action] ?? $this->action);
    }
}
