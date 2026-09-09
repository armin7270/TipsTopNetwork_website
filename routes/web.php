<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BuyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Telegram\WebhookController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TrialController;
use App\Http\Controllers\WalletController;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sitemap.xml', function () {
    return response()->view('sitemap')->header('Content-Type', 'application/xml');
})->name('sitemap');

// نقاط کمکی دیپلوی هاست اشتراکی (محافظت با DEPLOY_KEY + محدودیت نرخ)
Route::get('/deploy/migrate', [DeployController::class, 'migrate'])->middleware('throttle:10,1')->name('deploy.migrate');
Route::get('/deploy/cron', [DeployController::class, 'cron'])->middleware('throttle:30,1')->name('deploy.cron');
Route::get('/deploy/status', [DeployController::class, 'status'])->middleware('throttle:10,1')->name('deploy.status');
Route::get('/deploy/state', [DeployController::class, 'state'])->middleware('throttle:10,1')->name('deploy.state');

// تغییر زبان (فارسی/انگلیسی)
Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['fa', 'en'], true), 400);

    session(['locale' => $locale]);

    return redirect()->back();
})->name('lang.switch');

// احراز هویت
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');

    // بازیابی رمز عبور با OTP پیامکی
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp'])->name('password.send-otp')->middleware('throttle:5,1');
    Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// لینک اشتراک (عمومی - در اپلیکیشن‌های VPN استفاده می‌شود)
Route::get('/sub/{code}', [SubscriptionController::class, 'show'])
    ->middleware('throttle:sub')
    ->name('sub.show');

// پنل کاربری
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/receipt', [OrderController::class, 'submitReceipt'])->name('orders.receipt');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/pay-wallet', [OrderController::class, 'payWithWallet'])->name('orders.pay-wallet');

    Route::get('/buy/{plan}', [BuyController::class, 'show'])->name('buy');
    Route::post('/buy/{plan}', [BuyController::class, 'store'])->name('buy.store');
    Route::post('/buy/{plan}/discount', [BuyController::class, 'applyDiscount'])->name('buy.discount.apply');
    Route::post('/buy/discount/remove', [BuyController::class, 'removeDiscount'])->name('buy.discount.remove');

    // بازگشت از درگاه پرداخت آنلاین
    Route::get('/payment/callback', [OnlinePaymentController::class, 'callback'])->name('payment.callback');

    // کیف پول
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/charge', [WalletController::class, 'charge'])->name('wallet.charge');
    Route::post('/wallet/charge-crypto', [WalletController::class, 'chargeCrypto'])->name('wallet.charge-crypto');
    Route::get('/wallet/deposit/{transaction}', [WalletController::class, 'deposit'])->name('wallet.deposit');
    Route::post('/wallet/deposit/{transaction}/receipt', [WalletController::class, 'submitDepositReceipt'])->name('wallet.deposit.receipt');

    // تیکت‌های پشتیبانی
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');

    // دعوت از دوستان (رفرال)
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');

    // نوتیفیکیشن‌ها
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // اکانت تست
    Route::get('/trial', [TrialController::class, 'index'])->name('trial.index');
    Route::post('/trial', [TrialController::class, 'request'])->name('trial.request');

    // پروفایل کاربری
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'showPasswordForm'])->name('profile.password');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// وبهوک ربات تلگرام
Route::post('/telegram/webhook', WebhookController::class)
    ->name('telegram.webhook')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// وبهوک NOWPayments (کریپتو)
Route::post('/webhooks/nowpayments', function (Request $request) {
    // ثبت لاگ کامل IPN (مشابه vPanel)
    Log::info('nowpayments ipn', $request->json()->all());

    $data = $request->json()->all();

    // تایید امضای IPN (HMAC-SHA512 با کلیدهای مرتب‌شده) — رمز از پنل مدیریت یا .env
    $secret = trim((string) (Setting::get('nowpayments_ipn_secret', env('NOWPAYMENTS_IPN_SECRET', ''))));

    // امنیت: بدون رمز IPN، وبهوک باید رد شود (fail-closed) — وگرنه هرکس می‌تواند کیف پول را شارژ کند
    if ($secret === '') {
        Log::error('nowpayments ipn rejected: no IPN secret configured — set nowpayments_ipn_secret in admin settings or NOWPAYMENTS_IPN_SECRET in .env');

        return response()->json(['ok' => false, 'error' => 'ipn_secret_not_configured'], 403);
    }

    $payload = $data;
    ksort($payload);

    $expected = hash_hmac('sha512', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);

    if (! hash_equals($expected, (string) $request->header('x-nowpayments-sig', ''))) {
        Log::warning('nowpayments ipn rejected: invalid signature');

        return response()->json(['ok' => false], 403);
    }

    // شارژ کیف پول متصل به این پرداخت در صورت status موفق تایید می‌شود
    $depositId = $data['order_id'] ?? null;

    if ($depositId && in_array($data['payment_status'] ?? '', ['finished', 'confirmed', 'sending'], true)) {
        $transaction = Transaction::query()
            ->where('id', $depositId)
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->where('method', 'crypto')
            ->first();

        if ($transaction && $transaction->status !== Transaction::STATUS_COMPLETED) {
            app(WalletService::class)->confirmDeposit($transaction);
        }
    }

    return response()->json(['ok' => true]);
})->name('webhooks.nowpayments')->withoutMiddleware([VerifyCsrfToken::class]);

// پنل مدیریت (دسترسی بخش‌بندی‌شده بر اساس نقش: super / finance / support)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/setup', [Admin\SetupController::class, 'index'])->middleware('admin.section:super')->name('setup');
    Route::post('/setup/link-storage', [Admin\SetupController::class, 'linkStorage'])->middleware('admin.section:super')->name('setup.link-storage');
    Route::get('/activity-logs', [Admin\ActivityLogController::class, 'index'])->middleware('admin.section:super')->name('activity-logs.index');

    // بکاپ و مهاجرت
    Route::get('/migration', [Admin\MigrationController::class, 'index'])->middleware('admin.section:super')->name('migration.index');
    Route::post('/migration/export-db', [Admin\MigrationController::class, 'exportDb'])->middleware('admin.section:super')->name('migration.export-db');
    Route::post('/migration/export-files', [Admin\MigrationController::class, 'exportFiles'])->middleware('admin.section:super')->name('migration.export-files');
    Route::post('/migration/import-db', [Admin\MigrationController::class, 'importDb'])->middleware('admin.section:super')->name('migration.import-db');
    Route::post('/migration/import-files', [Admin\MigrationController::class, 'importFiles'])->middleware('admin.section:super')->name('migration.import-files');

    // پرداخت‌ها (صف تایید کارت به کارت)
    Route::get('/payments', [Admin\PaymentController::class, 'index'])->middleware('admin.section:finance')->name('payments.index');
    Route::post('/payments/{order}/approve', [Admin\PaymentController::class, 'approve'])->middleware('admin.section:finance')->name('payments.approve');
    Route::post('/payments/{order}/reject', [Admin\PaymentController::class, 'reject'])->middleware('admin.section:finance')->name('payments.reject');

    // سفارش‌ها
    Route::get('/orders', [Admin\OrderController::class, 'index'])->middleware('admin.section:finance')->name('orders.index');
    Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->middleware('admin.section:finance')->name('orders.show');
    Route::delete('/orders/{order}', [Admin\OrderController::class, 'destroy'])->middleware('admin.section:super')->name('orders.destroy');

    // شارژهای کیف پول
    Route::get('/wallet-deposits', [Admin\WalletDepositController::class, 'index'])->middleware('admin.section:finance')->name('wallet-deposits.index');
    Route::post('/wallet-deposits/{transaction}/approve', [Admin\WalletDepositController::class, 'approve'])->middleware('admin.section:finance')->name('wallet-deposits.approve');
    Route::post('/wallet-deposits/{transaction}/reject', [Admin\WalletDepositController::class, 'reject'])->middleware('admin.section:finance')->name('wallet-deposits.reject');

    // تیکت‌ها
    Route::get('/tickets', [Admin\TicketController::class, 'index'])->middleware('admin.section:support')->name('tickets.index');
    Route::get('/tickets/{ticket}', [Admin\TicketController::class, 'show'])->middleware('admin.section:support')->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [Admin\TicketController::class, 'reply'])->middleware('admin.section:support')->name('tickets.reply');
    Route::post('/tickets/{ticket}/close', [Admin\TicketController::class, 'close'])->middleware('admin.section:support')->name('tickets.close');
    Route::post('/tickets/{ticket}/reopen', [Admin\TicketController::class, 'reopen'])->middleware('admin.section:support')->name('tickets.reopen');

    // برودکست تلگرام
    Route::get('/broadcast', [Admin\BroadcastController::class, 'index'])->middleware('admin.section:super')->name('broadcast.index');
    Route::post('/broadcast', [Admin\BroadcastController::class, 'send'])->middleware('admin.section:super')->name('broadcast.send');

    // کاربران
    Route::get('/users', [Admin\UserController::class, 'index'])->middleware('admin.section:support')->name('users.index');
    Route::post('/users/{user}/toggle-block', [Admin\UserController::class, 'toggleBlock'])->middleware('admin.section:support')->name('users.toggle-block');
    Route::post('/users/{user}/adjust-wallet', [Admin\UserController::class, 'adjustWallet'])->middleware('admin.section:finance')->name('users.adjust-wallet');
    Route::post('/users/{user}/send-telegram', [Admin\UserController::class, 'sendTelegram'])->middleware('admin.section:support')->name('users.send-telegram');
    Route::post('/users/{user}/role', [Admin\UserController::class, 'setRole'])->middleware('admin.section:super')->name('users.role');

    // پلن‌ها
    Route::get('/plans', [Admin\PlanController::class, 'index'])->middleware('admin.section:super')->name('plans.index');
    Route::post('/plans', [Admin\PlanController::class, 'store'])->middleware('admin.section:super')->name('plans.store');
    Route::put('/plans/{plan}', [Admin\PlanController::class, 'update'])->middleware('admin.section:super')->name('plans.update');
    Route::delete('/plans/{plan}', [Admin\PlanController::class, 'destroy'])->middleware('admin.section:super')->name('plans.destroy');

    // سرورها و اینباندها
    Route::get('/inbounds', [Admin\InboundController::class, 'index'])->middleware('admin.section:super')->name('inbounds.index');
    Route::post('/servers/test-connection', [Admin\InboundController::class, 'testUnsaved'])->middleware('admin.section:super')->name('servers.test-connection');
    Route::post('/servers', [Admin\InboundController::class, 'storeServer'])->middleware('admin.section:super')->name('servers.store');
    Route::put('/servers/{server}', [Admin\InboundController::class, 'updateServer'])->middleware('admin.section:super')->name('servers.update');
    Route::delete('/servers/{server}', [Admin\InboundController::class, 'destroyServer'])->middleware('admin.section:super')->name('servers.destroy');
    Route::post('/servers/{server}/test', [Admin\InboundController::class, 'testServer'])->middleware('admin.section:super')->name('servers.test');
    Route::post('/servers/{server}/import', [Admin\InboundController::class, 'importInbounds'])->middleware('admin.section:super')->name('servers.import');
    Route::post('/inbounds', [Admin\InboundController::class, 'storeInbound'])->middleware('admin.section:super')->name('inbounds.store');
    Route::put('/inbounds/{inbound}', [Admin\InboundController::class, 'updateInbound'])->middleware('admin.section:super')->name('inbounds.update');
    Route::delete('/inbounds/{inbound}', [Admin\InboundController::class, 'destroyInbound'])->middleware('admin.section:super')->name('inbounds.destroy');

    // تنظیمات
    Route::get('/settings', [Admin\SettingController::class, 'edit'])->middleware('admin.section:super')->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->middleware('admin.section:super')->name('settings.update');

    // کدهای تخفیف
    Route::get('/discounts', [Admin\DiscountController::class, 'index'])->middleware('admin.section:super')->name('discounts.index');
    Route::post('/discounts', [Admin\DiscountController::class, 'store'])->middleware('admin.section:super')->name('discounts.store');
    Route::put('/discounts/{discount}', [Admin\DiscountController::class, 'update'])->middleware('admin.section:super')->name('discounts.update');
    Route::post('/discounts/{discount}/toggle', [Admin\DiscountController::class, 'toggle'])->middleware('admin.section:super')->name('discounts.toggle');
    Route::delete('/discounts/{discount}', [Admin\DiscountController::class, 'destroy'])->middleware('admin.section:super')->name('discounts.destroy');
});
