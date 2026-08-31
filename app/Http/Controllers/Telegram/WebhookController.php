<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\Transaction;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\QrService;
use App\Services\ReferralService;
use App\Services\Telegram\TelegramClient;
use App\Services\TrialService;
use App\Services\WalletService;
use App\Support\Format;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * کنترلر وبهوک ربات تلگرام (مشابه WebhookController در vPanel)
 * دستورات: /start /my_services /wallet /deposit /renew /support /referral /trial /cancel
 */
class WebhookController extends Controller
{
    public function __construct(
        protected TelegramClient $telegram,
        protected OrderService $orders,
        protected ReferralService $referrals,
        protected TrialService $trials,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        // اعتبارسنجی secret token تلگرام (در صورت تنظیم)
        $secret = (string) Setting::get('tg_webhook_secret', '');

        if ($secret !== '' && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            abort(403);
        }

        $update = $request->json()->all();

        try {
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            } elseif (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }

    // ============================================================
    // پیام‌های متنی
    // ============================================================

    protected function handleMessage(array $message): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim((string) ($message['text'] ?? ''));
        $from = $message['from'] ?? [];

        if ($chatId === '' || $text === '') {
            // ممکن است عکس رسید باشد
            if ($chatId !== '' && isset($message['photo'])) {
                $this->handlePhotoMessage($chatId, $message);
            }

            return;
        }

        $user = $this->findOrRegisterUser($chatId, $from, $text);

        if (! $user) {
            return; // در حالت ثبت‌نام، پیام خوش‌آمد ارسال شده است
        }

        // عضویت اجباری در کانال
        if (! $this->ensureChannelMembership($user)) {
            return;
        }

        // حالت‌های انتظار ورودی (state machine)
        if ($user->bot_state && $this->handleBotState($user, $text, $message)) {
            return;
        }

        // دستورات و منوی اصلی
        match (true) {
            $text === '/start' || $text === '🏠 منوی اصلی' => $this->sendMainMenu($user),
            $text === '/my_services' || $text === '🌐 سرویس‌های من' => $this->sendMyServices($user),
            $text === '/wallet' || $text === '💳 کیف پول' => $this->sendWalletMenu($user),
            $text === '/deposit' => $this->sendDepositOptions($user),
            $text === '/renew' => $this->sendMyServices($user),
            $text === '/support' || $text === '🎧 پشتیبانی' => $this->sendSupportMenu($user),
            $text === '/referral' || $text === '🎁 دعوت از دوستان' => $this->sendReferralMenu($user),
            $text === '/trial' || $text === '🧪 اکانت تست' => $this->handleTrialRequest($user),
            $text === '/transactions' => $this->sendTransactions($user),
            $text === '/cancel' => $this->cancelAction($user),
            $text === '🛒 خرید سرویس' => $this->sendPlans($user),
            $text === '📖 راهنمای اتصال' => $this->sendTutorials($user),
            default => $this->sendMainMenu($user, __('برای شروع یکی از گزینه‌های زیر را انتخاب کنید:')),
        };
    }

    /**
     * یافتن کاربر بر اساس chat_id یا شروع فرآیند اتصال/ثبت‌نام
     */
    protected function findOrRegisterUser(string $chatId, array $from, string $text = ''): ?User
    {
        $user = User::query()->where('telegram_chat_id', $chatId)->first();

        if ($user) {
            return $user;
        }

        // ذخیره کد معرف (از لینک /start CODE)
        $referralCode = '';

        if (str_starts_with($text, '/start ')) {
            $referralCode = trim(substr($text, 7));
        }

        if (User::query()->where('telegram_chat_id', $chatId)->doesntExist()) {
            $firstName = $from['first_name'] ?? 'کاربر تلگرام';

            $provisional = User::create([
                'name' => $firstName,
                'phone' => 'tg'.str_pad(substr((string) abs((int) $chatId), -10), 10, '0'),
                'telegram_chat_id' => $chatId,
                'telegram_username' => $from['username'] ?? null,
                'bot_state' => 'awaiting_phone',
                'status' => 'active',
                'password' => Str::password(16),
            ]);

            if ($referralCode !== '') {
                Setting::set('tg_pending_ref:'.$chatId, $referralCode);
            }

            $this->telegram->sendMessage($chatId,
                '👋 <b>به ربات '.e(Setting::get('site_name', 'TipStop Network')).' خوش آمدید!</b>'."\n\n".
                'برای ادامه، لطفاً شماره موبایل خود را ارسال کنید.'."\n".
                'اگر قبلاً در سایت ثبت‌نام کرده‌اید، حساب شما متصل می‌شود؛ در غیر این صورت با همین شماره ثبت‌نام می‌کنید.'."\n\n".
                '📱 مثال: <code>09123456789</code>'
            );

            return null;
        }

        return null;
    }

    /**
     * بررسی اجبار عضویت در کانال (با کش ۶۰ ثانیه‌ای)
     */
    protected function ensureChannelMembership(User $user): bool
    {
        $channel = trim((string) Setting::get('tg_force_channel', ''));

        if ($channel === '' || $user->isAdmin()) {
            return true;
        }

        $cacheKey = 'tg_member_'.$user->id.'_'.md5($channel);

        $isMember = cache()->remember($cacheKey, 60, fn () => $this->telegram->isMemberOfChannel($user->telegram_chat_id, $channel));

        if ($isMember) {
            return true;
        }

        $this->telegram->sendMessage($user->telegram_chat_id,
            '📢 برای استفاده از ربات ابتدا باید عضو کانال ما شوید:'."\n\n".
            '🔗 https://t.me/'.trim($channel, '@')."\n\n".
            'بعد از عضویت، /start را بزنید.'
        );

        return false;
    }

    // ============================================================
    // حالت‌های انتظار ورودی (state machine)
    // ============================================================

    /**
     * @return bool آیا پیام در یک state پردازش شد؟
     */
    protected function handleBotState(User $user, string $text, array $message): bool
    {
        $state = (string) $user->bot_state;

        // انتظار شماره موبایل برای اتصال/ثبت‌نام
        if ($state === 'awaiting_phone') {
            return $this->processPhoneLinking($user, $text);
        }

        // انتظار مبلغ شارژ دلخواه
        if ($state === 'awaiting_deposit_amount') {
            $user->update(['bot_state' => null]);
            $amount = (int) preg_replace('/[^\d]/', '', $text);

            return $this->startDeposit($user, $amount);
        }

        // انتظار تصویر رسید سفارش
        if (str_starts_with($state, 'awaiting_receipt:')) {
            if (isset($message['photo'])) {
                return $this->handleOrderReceiptPhoto($user, (int) substr($state, 17), $message);
            }

            $this->reply($user, '📸 لطفاً <b>عکس رسید واریز</b> را ارسال کنید یا /cancel را بزنید.');

            return true;
        }

        // انتظار تصویر رسید شارژ کیف پول
        if (str_starts_with($state, 'awaiting_deposit_receipt:')) {
            if (isset($message['photo'])) {
                return $this->handleDepositReceiptPhoto($user, (int) substr($state, 25), $message);
            }

            $this->reply($user, '📸 لطفاً <b>عکس رسید واریز</b> را ارسال کنید یا /cancel را بزنید.');

            return true;
        }

        return $this->handleTicketStates($user, $text, $state);
    }

    /**
     * @return bool آیا پیام در یک state تیکت پردازش شد؟
     */
    protected function handleTicketStates(User $user, string $text, string $state): bool
    {
        // انتظار موضوع تیکت جدید
        if ($state === 'awaiting_ticket_subject') {
            $user->update(['bot_state' => null]);

            $ticket = Ticket::create([
                'user_id' => $user->id,
                'subject' => Str::limit($text, 190),
                'priority' => Ticket::PRIORITY_MEDIUM,
                'status' => Ticket::STATUS_OPEN,
                'last_reply_at' => now(),
            ]);

            $user->update(['bot_state' => 'awaiting_ticket_message:'.$ticket->id]);

            $this->reply($user, "✅ تیکت #{$ticket->id} ساخته شد.\n\n✍️ حالا <b>متن پیام</b> خود را ارسال کنید:");

            NotificationService::notifyAdmins(
                'ticket_created',
                __('تیکت جدید از ربات تلگرام'),
                "تیکت #{$ticket->id} با موضوع «{$ticket->subject}» توسط {$user->name} ثبت شد.",
                route('admin.tickets.show', $ticket),
            );

            return true;
        }

        // انتظار متن پیام تیکت جدید یا پاسخ به تیکت
        $replyTicketId = null;

        if (str_starts_with($state, 'awaiting_ticket_message:')) {
            $replyTicketId = (int) substr($state, 24);
        } elseif (str_starts_with($state, 'awaiting_ticket_reply:')) {
            $replyTicketId = (int) substr($state, 22);
        }

        if ($replyTicketId !== null) {
            $user->update(['bot_state' => null]);

            $ticket = Ticket::query()->where('user_id', $user->id)->find($replyTicketId);

            if ($ticket) {
                TicketReply::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'is_staff' => false,
                    'message' => $text,
                ]);

                $ticket->update(['status' => Ticket::STATUS_OPEN, 'last_reply_at' => now()]);

                $this->reply($user, "✅ پیام شما به تیکت #{$ticket->id} ثبت شد. به‌محض پاسخ کارشناس اطلاع می‌دهیم.");

                NotificationService::notifyAdmins(
                    'ticket_replied',
                    __('پاسخ جدید در تیکت'),
                    "کاربر {$user->name} به تیکت #{$ticket->id} پاسخ داد.",
                    route('admin.tickets.show', $ticket),
                );
            }

            return true;
        }

        return false;
    }

    /**
     * اتصال حساب سایت به تلگرام یا ثبت‌نام جدید
     */
    protected function processPhoneLinking(User $user, string $text): bool
    {
        $phone = preg_replace('/[^\d]/', '', $text);

        if (! preg_match('/^09\d{9}$/', $phone)) {
            $this->reply($user, '❌ شماره موبایل نامعتبر است. لطفاً شماره را با فرمت <code>09123456789</code> ارسال کنید:');

            return true;
        }

        $existing = User::query()->where('phone', $phone)->where('id', '!=', $user->id)->first();

        if ($existing) {
            if ($existing->telegram_chat_id && $existing->telegram_chat_id !== $user->telegram_chat_id) {
                $this->reply($user, '❌ این شماره قبلاً به یک حساب تلگرام دیگر متصل شده است. با پشتیبانی تماس بگیرید.');

                return true;
            }

            // اتصال حساب موجود به این تلگرام و حذف حساب موقت
            $chatId = $user->telegram_chat_id;
            $tgUsername = $user->telegram_username;
            $referralCode = Setting::get('tg_pending_ref:'.$chatId);

            $existing->update([
                'telegram_chat_id' => $chatId,
                'telegram_username' => $tgUsername,
                'bot_state' => null,
            ]);
            $user->delete();

            if ($referralCode) {
                Setting::set('tg_pending_ref:'.$chatId, null);
                $this->referrals->onUserRegistered($existing, (string) $referralCode);
            }

            $this->reply($existing, '✅ حساب سایت شما با موفقیت به ربات متصل شد! 🎉');
            $this->sendMainMenu($existing);

            return true;
        }

        // تکمیل ثبت‌نام حساب جدید
        $user->update(['phone' => $phone, 'bot_state' => null]);

        $referralCode = Setting::get('tg_pending_ref:'.$user->telegram_chat_id);

        if ($referralCode) {
            Setting::set('tg_pending_ref:'.$user->telegram_chat_id, null);
            $this->referrals->onUserRegistered($user, (string) $referralCode);
        }

        $this->reply($user, '🎉 <b>ثبت‌نام شما کامل شد!</b>'."\n\n".'حساب سایت شما با شماره '.$phone.' ساخته شد.');
        $this->sendMainMenu($user);

        return true;
    }

    // ============================================================
    // Callback Queries (دکمه‌های شیشه‌ای)
    // ============================================================

    protected function handleCallbackQuery(array $callback): void
    {
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $messageId = (int) ($callback['message']['message_id'] ?? 0);
        $data = (string) ($callback['data'] ?? '');

        if ($chatId === '' || $data === '') {
            return;
        }

        $user = User::query()->where('telegram_chat_id', $chatId)->first();

        try {
            $this->telegram->answerCallbackQuery($callback['id'] ?? '');
        } catch (\Throwable) {
            // non-blocking
        }

        if (! $user) {
            $this->reply($chatId, 'لطفاً ابتدا با دستور /start شروع کنید.');

            return;
        }

        if (! $this->ensureChannelMembership($user)) {
            return;
        }

        // پیمایش callback data
        if ($data === 'home') {
            $this->sendMainMenu($user, $messageId);

            return;
        }

        if ($data === 'plans') {
            $this->sendPlans($user, $messageId);

            return;
        }

        if (str_starts_with($data, 'plan:')) {
            $this->sendPlanPaymentOptions($user, (int) substr($data, 5), $messageId);

            return;
        }

        if (str_starts_with($data, 'payw:')) {
            $this->payWithWallet($user, (int) substr($data, 5), $messageId);

            return;
        }

        if (str_starts_with($data, 'payc:')) {
            $this->showCardPaymentInstructions($user, (int) substr($data, 5), $messageId);

            return;
        }

        if ($data === 'services') {
            $this->sendMyServices($user, $messageId);

            return;
        }

        if (str_starts_with($data, 'service:')) {
            $this->sendServiceDetail($user, (int) substr($data, 8), $messageId);

            return;
        }

        if (str_starts_with($data, 'qrcode:')) {
            $this->sendServiceQrCode($user, (int) substr($data, 7));

            return;
        }

        if (str_starts_with($data, 'renew:')) {
            $this->startRenewal($user, (int) substr($data, 6));

            return;
        }

        if ($data === 'wallet') {
            $this->sendWalletMenu($user, $messageId);

            return;
        }

        if ($data === 'deposit') {
            $this->sendDepositOptions($user, $messageId);

            return;
        }

        if (str_starts_with($data, 'dep:')) {
            $this->startDeposit($user, (int) substr($data, 4), $messageId);

            return;
        }

        if ($data === 'depcustom') {
            $user->update(['bot_state' => 'awaiting_deposit_amount']);
            $this->edit($chatId, $messageId, '💳 لطفاً مبلغ دلخواه خود را (به تومان، حداقل '.number_format(max(1000, (int) Setting::get('wallet_min_deposit', 10000))).') در یک پیام ارسال کنید:',
                [[['text' => '❌ انصراف', 'callback_data' => 'wallet']]]);

            return;
        }

        if ($data === 'transactions') {
            $this->sendTransactions($user, $messageId);

            return;
        }

        if ($data === 'referral') {
            $this->sendReferralMenu($user, $messageId);

            return;
        }

        if ($data === 'support') {
            $this->sendSupportMenu($user, $messageId);

            return;
        }

        if ($data === 'ticket:new') {
            $user->update(['bot_state' => 'awaiting_ticket_subject']);
            $this->edit($chatId, $messageId, '✍️ لطفاً <b>موضوع تیکت</b> را در یک پیام ارسال کنید:',
                [[['text' => '❌ انصراف', 'callback_data' => 'support']]]);

            return;
        }

        if (str_starts_with($data, 'ticketshow:')) {
            $this->sendTicketDetail($user, (int) substr($data, 11), $messageId);

            return;
        }

        if (str_starts_with($data, 'ticketopen:')) {
            $ticketId = (int) substr($data, 11);
            $user->update(['bot_state' => 'awaiting_ticket_reply:'.$ticketId]);
            $this->edit($chatId, $messageId, "✍️ لطفاً <b>متن پاسخ</b> خود به تیکت #{$ticketId} را ارسال کنید:",
                [[['text' => '❌ انصراف', 'callback_data' => 'support']]]);

            return;
        }

        if (str_starts_with($data, 'ticketclose:')) {
            $this->closeTicketFromBot($user, (int) substr($data, 12), $messageId);

            return;
        }

        if ($data === 'tutorials') {
            $this->sendTutorials($user, $messageId);

            return;
        }

        if (str_starts_with($data, 'tut:')) {
            $this->sendTutorialFor($user, substr($data, 4), $messageId);

            return;
        }

        if ($data === 'trial') {
            $this->handleTrialRequest($user);

            return;
        }

        $this->reply($chatId, 'دستور ناشناخته است. /start را بزنید.');
    }

    // ============================================================
    // منوها
    // ============================================================

    protected function sendMainMenu(User $user, int|string|null $messageIdOrNote = null): void
    {
        $note = is_string($messageIdOrNote) ? $messageIdOrNote."\n\n" : '';
        $messageId = is_int($messageIdOrNote) ? $messageIdOrNote : null;

        $text = $note.
            '🏠 <b>منوی اصلی '.e(Setting::get('site_name', 'TipStop Network')).'</b>'."\n\n".
            '💳 موجودی کیف پول: <b>'.number_format($user->balance).' تومان</b>'."\n\n".
            'یکی از گزینه‌های زیر را انتخاب کنید:';

        $keyboard = [
            [['text' => '🛒 خرید سرویس', 'callback_data' => 'plans'], ['text' => '🌐 سرویس‌های من', 'callback_data' => 'services']],
            [['text' => '💳 کیف پول', 'callback_data' => 'wallet'], ['text' => '🎁 دعوت از دوستان', 'callback_data' => 'referral']],
            [['text' => '🎧 پشتیبانی', 'callback_data' => 'support'], ['text' => '📖 راهنمای اتصال', 'callback_data' => 'tutorials']],
        ];

        if (Setting::get('trial_enabled', '0') === '1') {
            $keyboard[] = [['text' => '🧪 دریافت اکانت تست', 'callback_data' => 'trial']];
        }

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    protected function sendPlans(User $user, ?int $messageId = null): void
    {
        $plans = Plan::query()->where('is_active', true)->orderBy('sort_order')->orderBy('price_toman')->get();

        if ($plans->isEmpty()) {
            $this->reply($user->telegram_chat_id, 'فعلاً پلنی برای فروش موجود نیست.');

            return;
        }

        // دسته‌بندی بر اساس مدت (مشابه vPanel)
        $byDuration = $plans->groupBy('duration_days')->sortKeys();

        $text = "🛒 <b>پلن‌های موجود</b>\n\nپلن موردنظر خود را انتخاب کنید:";

        $keyboard = [];

        foreach ($byDuration as $duration => $group) {
            foreach ($group as $plan) {
                $keyboard[] = [[
                    'text' => $plan->name.' | '.$plan->volumeLabel().' | '.number_format($plan->price_toman).' تومان',
                    'callback_data' => 'plan:'.$plan->id,
                ]];
            }
        }

        $keyboard[] = [['text' => '🏠 منوی اصلی', 'callback_data' => 'home']];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    protected function sendPlanPaymentOptions(User $user, int $planId, ?int $messageId = null): void
    {
        $plan = Plan::query()->where('is_active', true)->find($planId);

        if (! $plan) {
            $this->reply($user->telegram_chat_id, 'این پلن دیگر در دسترس نیست.');

            return;
        }

        // ساخت سفارش (با منطق تمدید خودکار)
        $order = $this->orders->createForPlan($user, $plan, source: 'telegram');

        $text = "🧾 <b>خلاصه سفارش</b>\n\n".
            "📦 پلن: {$plan->name}\n".
            '📦 حجم: '.$plan->volumeLabel()."\n".
            '📅 مدت: '.$plan->durationLabel()."\n".
            '💰 قیمت: <b>'.number_format($plan->price_toman).' تومان</b>'."\n\n".
            'روش پرداخت را انتخاب کنید:';

        $keyboard = [
            [['text' => '💳 پرداخت با کیف پول ('.number_format($user->balance).' تومان)', 'callback_data' => 'payw:'.$order->id]],
            [['text' => '🏦 پرداخت کارت به کارت', 'callback_data' => 'payc:'.$order->id]],
            [['text' => '⬅️ بازگشت به پلن‌ها', 'callback_data' => 'plans']],
        ];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    // ============================================================
    // پرداخت
    // ============================================================

    protected function payWithWallet(User $user, int $orderId, ?int $messageId = null): void
    {
        $order = Order::query()->where('user_id', $user->id)->find($orderId);

        if (! $order || $order->status !== Order::STATUS_PENDING_PAYMENT) {
            $this->reply($user->telegram_chat_id, '❌ این سفارش معتبر نیست.');

            return;
        }

        try {
            $order = $this->orders->payWithWallet($order);
        } catch (\RuntimeException $e) {
            $text = '❌ '.e($e->getMessage())."\n\n".'می‌توانید ابتدا کیف پول خود را شارژ کنید:';

            $keyboard = [
                [['text' => '💳 شارژ کیف پول', 'callback_data' => 'deposit']],
                [['text' => '🏦 پرداخت کارت به کارت', 'callback_data' => 'payc:'.$orderId]],
            ];

            if ($messageId) {
                $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
            } else {
                $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
            }

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->reply($user->telegram_chat_id, '❌ خطا در فعال‌سازی سرویس: '.e($e->getMessage())."\n\nمبلغ به کیف پول شما بازگشت داده شد.");

            return;
        }

        $text = $order->isRenewal()
            ? '✅ <b>سرویس شما با موفقیت تمدید شد!</b>'
            : '✅ <b>سرویس شما با موفقیت فعال شد!</b>';

        $text .= "\n\n💰 مبلغ پرداخت‌شده: ".number_format($order->price_toman)." تومان (کیف پول)\n".
            '💳 موجودی جدید: '.number_format($user->refresh()->balance).' تومان';

        $keyboard = [
            [['text' => '🌐 مشاهده سرویس‌های من', 'callback_data' => 'services']],
            [['text' => '🏠 منوی اصلی', 'callback_data' => 'home']],
        ];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    protected function showCardPaymentInstructions(User $user, int $orderId, ?int $messageId = null): void
    {
        $order = Order::query()->where('user_id', $user->id)->find($orderId);

        if (! $order || ! in_array($order->status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_REJECTED], true)) {
            $this->reply($user->telegram_chat_id, '❌ این سفارش معتبر نیست.');

            return;
        }

        $cards = Setting::getJson('cards', []);

        $text = "🏦 <b>پرداخت کارت به کارت</b>\n\n".
            '💰 مبلغ قابل پرداخت: <b>'.number_format($order->price_toman)." تومان</b>\n\n";

        if ($cards) {
            $text .= "💳 کارت‌های واریزی:\n";

            foreach ($cards as $card) {
                $text .= '━━━━━━━━━━━━━━'."\n".
                    '🏦 بانک: '.e($card['bank'] ?? '-')."\n".
                    '👤 به نام: '.e($card['holder'] ?? '-')."\n".
                    '💳 شماره کارت: <code>'.e($card['number'] ?? '-').'</code>'."\n";
            }
        } else {
            $text .= "⚠️ شماره کارت‌ها در سیستم ثبت نشده است. با پشتیبانی تماس بگیرید.\n";
        }

        $text .= "\n📸 بعد از واریز، <b>عکس رسید</b> را در همین چت ارسال کنید.";

        $user->update(['bot_state' => 'awaiting_receipt:'.$order->id]);

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text);
        }
    }

    /**
     * دریافت عکس رسید سفارش در ربات (مشابه vPanel)
     */
    protected function handleOrderReceiptPhoto(User $user, int $orderId, array $message): bool
    {
        $user->update(['bot_state' => null]);

        $order = Order::query()->where('user_id', $user->id)->find($orderId);

        if (! $order || ! in_array($order->status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_REJECTED], true)) {
            $this->reply($user->telegram_chat_id, '❌ سفارش نامعتبر است یا در انتظار پرداخت نیست.');

            return true;
        }

        $path = $this->saveReceiptPhoto($message);

        $order->update([
            'status' => Order::STATUS_AWAITING_VERIFICATION,
            'receipt_path' => $path,
            'payment_method' => 'card',
            'paid_amount' => $order->price_toman,
            'paid_at' => now(),
        ]);

        $this->reply($user->telegram_chat_id, "✅ رسید شما ثبت شد.\n\nپس از بررسی و تایید مدیر، سرویس شما به‌صورت خودکار فعال می‌شود. 🙏");

        NotificationService::notifyAdmins(
            'order_receipt',
            __('رسید جدید از ربات تلگرام'),
            "سفارش #{$order->id} ({$order->plan_name} - ".number_format($order->price_toman)." تومان) توسط {$user->name} رسید عکس ثبت کرد.",
            route('admin.payments.index'),
        );

        $this->forwardPhotoToAdmin($message, "📸 رسید سفارش #{$order->id} از {$user->name}");

        return true;
    }

    /**
     * دریافت عکس رسید شارژ کیف پول در ربات
     */
    protected function handleDepositReceiptPhoto(User $user, int $transactionId, array $message): bool
    {
        $user->update(['bot_state' => null]);

        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->find($transactionId);

        if (! $transaction || ! in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_AWAITING_VERIFICATION], true)) {
            $this->reply($user->telegram_chat_id, '❌ درخواست شارژ نامعتبر است.');

            return true;
        }

        $path = $this->saveReceiptPhoto($message);

        $transaction->update([
            'status' => Transaction::STATUS_AWAITING_VERIFICATION,
            'meta' => array_merge($transaction->meta ?? [], ['receipt_path' => $path]),
        ]);

        $this->reply($user->telegram_chat_id, "✅ رسید شارژ شما ثبت شد.\n\nپس از تایید مدیر، مبلغ به کیف پول شما اضافه می‌شود. 🙏");

        NotificationService::notifyAdmins(
            'wallet_deposit',
            __('رسید شارژ کیف پول از ربات'),
            "کاربر {$user->name} برای مبلغ ".number_format($transaction->amount).' تومان رسید عکس ثبت کرد.',
            route('admin.wallet-deposits.index'),
        );

        $this->forwardPhotoToAdmin($message, "📸 رسید شارژ {$transaction->id} تومان از {$user->name}");

        return true;
    }

    /**
     * ذخیره فایل عکس رسیده تلگرام در storage
     */
    protected function saveReceiptPhoto(array $message): ?string
    {
        try {
            $fileId = null;

            // بهترین کیفیت عکس را انتخاب کن
            foreach ($message['photo'] ?? [] as $photo) {
                $fileId = $photo['file_id'] ?? $fileId;
            }

            if (! $fileId) {
                return null;
            }

            $file = $this->telegram->call('getFile', ['file_id' => $fileId]);

            $url = 'https://api.telegram.org/file/bot'.$this->botToken().'/'.$file['file_path'];
            $content = @file_get_contents($url);

            if ($content === false) {
                return null;
            }

            $path = 'receipts/tg-'.uniqid().'.jpg';
            Storage::disk('public')->put($path, $content);

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function forwardPhotoToAdmin(array $message, string $caption): void
    {
        $adminChatId = (string) Setting::get('tg_admin_chat_id', '');

        if ($adminChatId === '') {
            return;
        }

        try {
            $fileId = null;

            foreach ($message['photo'] ?? [] as $photo) {
                $fileId = $photo['file_id'] ?? $fileId;
            }

            if ($fileId) {
                $this->telegram->sendPhoto($adminChatId, $fileId, $caption);
            }
        } catch (\Throwable) {
            // non-blocking
        }
    }

    protected function botToken(): string
    {
        return (string) Setting::get('tg_bot_token', '');
    }

    // ============================================================
    // کیف پول
    // ============================================================

    protected function sendWalletMenu(User $user, ?int $messageId = null): void
    {
        $text = "💳 <b>کیف پول</b>\n\n".
            '💰 موجودی فعلی: <b>'.number_format($user->balance)." تومان</b>\n\n".
            'برای شارژ کیف پول یکی از گزینه‌های زیر را انتخاب کنید:';

        $keyboard = [
            [['text' => '➕ شارژ کیف پول', 'callback_data' => 'deposit']],
            [['text' => '🧾 تاریخچه تراکنش‌ها', 'callback_data' => 'transactions']],
            [['text' => '🏠 منوی اصلی', 'callback_data' => 'home']],
        ];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    protected function sendDepositOptions(User $user, ?int $messageId = null): void
    {
        // مبالغ پیش‌فرض قابل تنظیم از پنل ادمین (مشابه deposit_amounts در vPanel)
        $amounts = Setting::getJson('tg_deposit_amounts', [50000, 100000, 200000, 500000]);

        if (! is_array($amounts) || $amounts === []) {
            $amounts = [50000, 100000, 200000, 500000];
        }

        sort($amounts);

        $text = "💳 <b>شارژ کیف پول</b>\n\nلطفاً مبلغ مورد نظر را انتخاب کنید یا مبلغ دلخواه وارد نمایید:";

        $keyboard = [];

        foreach (array_chunk($amounts, 2) as $row) {
            $keyboard[] = array_map(fn ($amount) => [
                'text' => number_format($amount).' تومان',
                'callback_data' => 'dep:'.$amount,
            ], $row);
        }

        $keyboard[] = [['text' => '✍️ ورود مبلغ دلخواه', 'callback_data' => 'depcustom']];
        $keyboard[] = [['text' => '⬅️ بازگشت به کیف پول', 'callback_data' => 'wallet']];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    /**
     * شروع فرآیند شارژ با مبلغ مشخص — در صورت خطا false برمی‌گرداند
     */
    protected function startDeposit(User $user, int $amount, ?int $messageId = null): bool
    {
        $min = max(1000, (int) Setting::get('wallet_min_deposit', 10000));

        if ($amount < $min) {
            $text = '❌ مبلغ نامعتبر است. حداقل شارژ '.number_format($min)." تومان است.\n\nلطفاً مبلغ دیگری انتخاب کنید:";

            if ($messageId) {
                $this->edit($user->telegram_chat_id, $messageId, $text, [[['text' => '⬅️ بازگشت', 'callback_data' => 'deposit']]]);
            } else {
                $this->telegram->sendMessage($user->telegram_chat_id, $text, [[['text' => '⬅️ بازگشت', 'callback_data' => 'deposit']]]);
            }

            return false;
        }

        try {
            $transaction = app(WalletService::class)->createDeposit($user, $amount, 'card');
        } catch (\Throwable $e) {
            $this->reply($user->telegram_chat_id, '❌ '.e($e->getMessage()));

            return false;
        }

        $cards = Setting::getJson('cards', []);

        $text = '🏦 <b>شارژ کیف پول — '.number_format($amount)." تومان</b>\n\n";

        if ($cards) {
            $text .= "💳 واریز به:\n";

            foreach ($cards as $card) {
                $text .= '💳 <code>'.e($card['number'] ?? '-').'</code> — '.e($card['holder'] ?? '-')."\n";
            }
        } else {
            $text .= "⚠️ شماره کارت‌ها ثبت نشده است. با پشتیبانی تماس بگیرید.\n";
        }

        $text .= "\n📸 بعد از واریز، <b>عکس رسید</b> را در همین چت ارسال کنید.";

        $user->update(['bot_state' => 'awaiting_deposit_receipt:'.$transaction->id]);

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text);
        }

        return true;
    }

    protected function sendTransactions(User $user, ?int $messageId = null): void
    {
        $transactions = $user->transactions()->take(10)->get();

        $text = "🧾 <b>۱۰ تراکنش اخیر شما</b>\n\n";

        if ($transactions->isEmpty()) {
            $text .= 'هنوز تراکنشی ثبت نشده است.';
        } else {
            foreach ($transactions as $transaction) {
                $sign = $transaction->isCredit() ? '➕' : '➖';
                $text .= $sign.' '.$transaction->typeLabel().' — '.number_format($transaction->amount).' تومان'."\n".
                    '   '.$transaction->statusLabel().' — '.Format::date($transaction->created_at, false)."\n\n";
            }
        }

        $keyboard = [
            [['text' => '💳 شارژ کیف پول', 'callback_data' => 'deposit']],
            [['text' => '⬅️ بازگشت به کیف پول', 'callback_data' => 'wallet']],
        ];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    // ============================================================
    // سرویس‌های من
    // ============================================================

    protected function sendMyServices(User $user, ?int $messageId = null): void
    {
        $orders = $user->orders()->with('inbounds.server')
            ->whereIn('status', [Order::STATUS_ACTIVE, Order::STATUS_EXPIRED])
            ->latest()->take(10)->get();

        $keyboard = $orders->isEmpty()
            ? [
                [['text' => '🛒 خرید سرویس', 'callback_data' => 'plans']],
                [['text' => '🏠 منوی اصلی', 'callback_data' => 'home']],
            ]
            : [];

        $text = $orders->isEmpty()
            ? "🌐 هنوز سرویسی ندارید.\n\nبرای خرید از دکمه زیر استفاده کنید:"
            : "🌐 <b>سرویس‌های شما</b>\n\nبرای مشاهده جزئیات، روی سرویس بزنید:";

        foreach ($orders as $order) {
            $icon = $order->isActive() ? '✅' : '⛔️';
            $keyboard[] = [[
                'text' => $icon.' '.$order->plan_name.' ('.($order->isActive() ? $order->daysLeft().' روز' : 'منقضی').')',
                'callback_data' => 'service:'.$order->id,
            ]];
        }

        if ($orders->isNotEmpty()) {
            $keyboard[] = [['text' => '🏠 منوی اصلی', 'callback_data' => 'home']];
        }

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    protected function sendServiceDetail(User $user, int $orderId, ?int $messageId = null): void
    {
        $order = $user->orders()->with('inbounds.server')->find($orderId);

        if (! $order) {
            $this->reply($user->telegram_chat_id, '❌ سرویس یافت نشد.');

            return;
        }

        $icon = $order->isActive() ? '✅' : '⛔️';

        $text = "{$icon} <b>{$order->plan_name}</b>\n\n".
            '👤 نام کاربری: <code>'.e($order->xui_email ?: '-').'</code>'."\n".
            '🗓 انقضا: '.($order->expires_at ? Format::date($order->expires_at, false).' — '.$order->daysLeft().' روز' : '-')."\n".
            '📦 حجم: '.$order->totalLabel().' (باقی‌مانده: '.$order->remainingLabel().")\n";

        $subscriptionUrl = (rtrim((string) Setting::get('sub_base_url', ''), '/') ?: rtrim(config('app.url'), '/')).'/sub/'.$user->subscription_code;
        $text .= "\n🔗 لینک اشتراک:\n<code>".e($subscriptionUrl).'</code>';

        $keyboard = [];

        if ($order->isActive()) {
            $keyboard[] = [['text' => '📱 دریافت QR Code', 'callback_data' => 'qrcode:'.$order->id]];
            $keyboard[] = [['text' => '🔄 تمدید سرویس', 'callback_data' => 'renew:'.$order->id]];
        } else {
            $keyboard[] = [['text' => '🔄 خرید مجدد (پلن‌ها)', 'callback_data' => 'plans']];
        }

        $keyboard[] = [['text' => '⬅️ بازگشت به لیست سرویس‌ها', 'callback_data' => 'services']];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    /**
     * ارسال QR کد لینک اشتراک در ربات (مشابه vPanel)
     */
    protected function sendServiceQrCode(User $user, int $orderId): void
    {
        $subscriptionUrl = (rtrim((string) Setting::get('sub_base_url', ''), '/') ?: rtrim(config('app.url'), '/')).'/sub/'.$user->subscription_code;

        try {
            $pngPath = QrService::pngTempFile($subscriptionUrl);

            if ($pngPath) {
                $this->telegram->sendPhoto($user->telegram_chat_id, $pngPath, '📱 برای اتصال، این QR را در اپلیکیشن اسکن کنید.');
                @unlink($pngPath);

                return;
            }
        } catch (\Throwable) {
            // fallback
        }

        $this->reply($user->telegram_chat_id, '🔗 لینک اشتراک شما:'."\n<code>".e($subscriptionUrl).'</code>');
    }

    /**
     * شروع تمدید سرویس (ایجاد سفارش تمدید و نمایش روش‌های پرداخت)
     */
    protected function startRenewal(User $user, int $orderId): void
    {
        $previous = $user->orders()->find($orderId);

        if (! $previous) {
            $this->reply($user->telegram_chat_id, '❌ سرویس یافت نشد.');

            return;
        }

        $plan = $previous->plan;

        if (! $plan || ! $plan->is_active) {
            $this->telegram->sendMessage($user->telegram_chat_id, '⚠️ پلن این سرویس دیگر موجود نیست. از لیست پلن‌ها پلن جدیدی انتخاب کنید:', [[['text' => '🛒 پلن‌ها', 'callback_data' => 'plans']]]);

            return;
        }

        $order = $this->orders->createForPlan($user, $plan, source: 'telegram');

        $text = "🔄 <b>تمدید سرویس {$previous->plan_name}</b>\n\n".
            '💰 قیمت تمدید: <b>'.number_format($plan->price_toman)." تومان</b>\n".
            '📅 مدت: '.$plan->durationLabel()."\n\n".
            'روش پرداخت را انتخاب کنید:';

        $keyboard = [
            [['text' => '💳 پرداخت با کیف پول ('.number_format($user->balance).' تومان)', 'callback_data' => 'payw:'.$order->id]],
            [['text' => '🏦 پرداخت کارت به کارت', 'callback_data' => 'payc:'.$order->id]],
            [['text' => '⬅️ بازگشت', 'callback_data' => 'service:'.$orderId]],
        ];

        $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
    }

    // ============================================================
    // دعوت از دوستان
    // ============================================================

    protected function sendReferralMenu(User $user, ?int $messageId = null): void
    {
        if (Setting::get('referral_enabled', '1') === '0') {
            $this->reply($user->telegram_chat_id, '⚠️ سیستم دعوت از دوستان فعلاً غیرفعال است.');

            return;
        }

        $botUsername = '';

        try {
            $me = $this->telegram->getMe();
            $botUsername = $me['username'] ?? '';
        } catch (\Throwable) {
        }

        $link = $botUsername
            ? "https://t.me/{$botUsername}?start={$user->referral_code}"
            : 'کد معرفی: '.$user->referral_code;

        $stats = $this->referrals->statsFor($user);

        $text = "🎁 <b>دعوت از دوستان</b>\n\n".
            'با معرفی ما به دوستانتان، بعد از اولین خرید آن‌ها پاداش نقدی دریافت کنید!'."\n\n".
            "👥 تعداد دعوت‌شدگان: <b>{$stats['count']}</b>\n".
            '💰 مجموع پاداش دریافتی: <b>'.number_format($stats['earned'])." تومان</b>\n\n".
            "🔗 لینک اختصاصی شما:\n<code>".e($link).'</code>';

        $keyboard = [[['text' => '🏠 منوی اصلی', 'callback_data' => 'home']]];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    // ============================================================
    // پشتیبانی (تیکت‌ها)
    // ============================================================

    protected function sendSupportMenu(User $user, ?int $messageId = null): void
    {
        $tickets = $user->tickets()->take(5)->get();

        $text = "🎧 <b>پشتیبانی</b>\n\nتیکت موردنظر را انتخاب کنید یا تیکت جدید بسازید:";

        $keyboard = [];

        foreach ($tickets as $ticket) {
            $icon = match ($ticket->status) {
                Ticket::STATUS_OPEN => '🟡',
                Ticket::STATUS_ANSWERED => '🟢',
                default => '⚪️',
            };

            $keyboard[] = [['text' => $icon.' #'.$ticket->id.' — '.Str::limit($ticket->subject, 25), 'callback_data' => 'ticketshow:'.$ticket->id]];
        }

        $keyboard[] = [['text' => '✨ تیکت جدید', 'callback_data' => 'ticket:new']];
        $keyboard[] = [['text' => '🏠 منوی اصلی', 'callback_data' => 'home']];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    protected function sendTicketDetail(User $user, int $ticketId, ?int $messageId = null): void
    {
        $ticket = $user->tickets()->with('replies')->find($ticketId);

        if (! $ticket) {
            $this->reply($user->telegram_chat_id, '❌ تیکت یافت نشد.');

            return;
        }

        $text = "🎫 <b>تیکت #{$ticket->id}</b>\n".
            'موضوع: '.e($ticket->subject)."\n".
            'وضعیت: '.$ticket->statusLabel()."\n\n";

        foreach ($ticket->replies as $reply) {
            $who = $reply->is_staff ? '🎧 پشتیبانی' : '👤 شما';
            $text .= '<b>'.$who.':</b>'."\n".e(Str::limit($reply->message, 300))."\n\n";
        }

        $keyboard = [];

        if ($ticket->status !== Ticket::STATUS_CLOSED) {
            $keyboard[] = [['text' => '✍️ پاسخ به تیکت', 'callback_data' => 'ticketopen:'.$ticket->id]];
            $keyboard[] = [['text' => '❌ بستن تیکت', 'callback_data' => 'ticketclose:'.$ticket->id]];
        }

        $keyboard[] = [['text' => '⬅️ بازگشت به پشتیبانی', 'callback_data' => 'support']];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    protected function closeTicketFromBot(User $user, int $ticketId, ?int $messageId = null): void
    {
        $ticket = $user->tickets()->find($ticketId);

        if (! $ticket || $ticket->status === Ticket::STATUS_CLOSED) {
            $this->reply($user->telegram_chat_id, '❌ تیکت معتبر نیست.');

            return;
        }

        $ticket->update(['status' => Ticket::STATUS_CLOSED]);

        $this->reply($user->telegram_chat_id, "✅ تیکت #{$ticket->id} بسته شد.");

        if ($messageId) {
            $this->sendSupportMenu($user, $messageId);
        }
    }

    // ============================================================
    // راهنمای اتصال
    // ============================================================

    protected function sendTutorials(User $user, ?int $messageId = null): void
    {
        $text = "📖 <b>راهنمای اتصال</b>\n\nسیستم‌عامل خود را انتخاب کنید:";

        $keyboard = [
            [['text' => '🤖 اندروید (V2rayNG)', 'callback_data' => 'tut:android']],
            [['text' => '🍎 آیفون (V2Box)', 'callback_data' => 'tut:ios']],
            [['text' => '💻 ویندوز (V2rayN)', 'callback_data' => 'tut:windows']],
            [['text' => '🏠 منوی اصلی', 'callback_data' => 'home']],
        ];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    protected function sendTutorialFor(User $user, string $platform, ?int $messageId = null): void
    {
        $steps = match ($platform) {
            'android' => "1. اپلیکیشن V2rayNG را نصب کنید.\n2. لینک اشتراک را از بخش «سرویس‌های من» کپی کنید.\n3. در اپ: تب Subscription → + → Import from clipboard.\n4. روی کانفیگ بزنید و اتصال را تست کنید.",
            'ios' => "1. اپلیکیشن V2Box را از App Store نصب کنید.\n2. لینک اشتراک را کپی کنید.\n3. در اپ: Config → + → Import from clipboard.\n4. کانفیگ را انتخاب و اتصال را برقرار کنید.",
            'windows' => "1. نرم‌افزار V2rayN را دانلود و اجرا کنید.\n2. لینک اشتراک را کپی کنید.\n3. Subscriptions → Subscription settings → افزودن آدرس.\n4. Update subscriptions بزنید و کانفیگ را فعال کنید.",
            default => null,
        };

        $text = $steps
            ? "📖 <b>راهنمای اتصال</b>\n\n".$steps
            : 'راهنمایی برای این پلتفرم موجود نیست.';

        $keyboard = [[['text' => '⬅️ بازگشت', 'callback_data' => 'tutorials']]];

        if ($messageId) {
            $this->edit($user->telegram_chat_id, $messageId, $text, $keyboard);
        } else {
            $this->telegram->sendMessage($user->telegram_chat_id, $text, $keyboard);
        }
    }

    // ============================================================
    // اکانت تست
    // ============================================================

    protected function handleTrialRequest(User $user): void
    {
        try {
            $trial = $this->trials->request($user);
        } catch (\RuntimeException $e) {
            $this->reply($user->telegram_chat_id, '❌ '.e($e->getMessage()));

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->reply($user->telegram_chat_id, '❌ خطا در ساخت اکانت تست. لطفاً بعداً تلاش کنید.');

            return;
        }

        $text = "🧪 <b>اکانت تست شما فعال شد!</b>\n\n".
            '📦 حجم: '.$trial->volumeLabel()."\n".
            '🗓 انقضا: '.Format::date($trial->expires_at, false)."\n\n";

        if ($trial->config) {
            $text .= "🔗 لینک کانفیگ:\n<code>".e($trial->config).'</code>';
        }

        $this->telegram->sendMessage($user->telegram_chat_id, $text);
    }

    // ============================================================
    // توابع کمکی
    // ============================================================

    protected function cancelAction(User $user): void
    {
        $user->update(['bot_state' => null]);

        $this->reply($user->telegram_chat_id, '✅ عملیات لغو شد.');
        $this->sendMainMenu($user);
    }

    /**
     * پردازش عکس‌های بدون متن (رسید در انتظار)
     */
    protected function handlePhotoMessage(string $chatId, array $message): void
    {
        $user = User::query()->where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            return;
        }

        $state = (string) $user->bot_state;

        if (str_starts_with($state, 'awaiting_receipt:')) {
            $this->handleOrderReceiptPhoto($user, (int) substr($state, 17), $message);
        } elseif (str_starts_with($state, 'awaiting_deposit_receipt:')) {
            $this->handleDepositReceiptPhoto($user, (int) substr($state, 25), $message);
        } else {
            $this->reply($chatId, 'برای ثبت رسید، ابتدا سفارش یا شارژ کیف پول را از منوی اصلی انتخاب کنید. 🏠 /start');
        }
    }

    protected function reply(User|string $userOrChatId, string $html): void
    {
        $chatId = $userOrChatId instanceof User ? $userOrChatId->telegram_chat_id : $userOrChatId;

        try {
            $this->telegram->sendMessage((string) $chatId, $html);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function edit(string $chatId, int $messageId, string $html, ?array $keyboard = null): void
    {
        try {
            $this->telegram->editMessageText($chatId, $messageId, $html, $keyboard);
        } catch (\Throwable) {
            // اگر پیام قابل ویرایش نبود، پیام جدید می‌فرستیم
            try {
                $this->telegram->sendMessage($chatId, $html, $keyboard);
            } catch (\Throwable) {
            }
        }
    }
}
