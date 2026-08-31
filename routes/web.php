<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BuyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
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

// تغییر زبان (فارسی/انگلیسی)
Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['fa', 'en'], true), 400);

    session(['locale' => $locale]);

    return redirect()->back();
})->name('lang.switch');

// احراز هویت
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
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

    // کیف پول
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/charge', [WalletController::class, 'charge'])->name('wallet.charge');
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

    if ($secret !== '') {
        $payload = $data;
        ksort($payload);

        $expected = hash_hmac('sha512', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);

        if (! hash_equals($expected, (string) $request->header('x-nowpayments-sig', ''))) {
            Log::warning('nowpayments ipn rejected: invalid signature');

            return response()->json(['ok' => false], 403);
        }
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

// پنل مدیریت
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // پرداخت‌ها (صف تایید کارت به کارت)
    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{order}/approve', [Admin\PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{order}/reject', [Admin\PaymentController::class, 'reject'])->name('payments.reject');

    // سفارش‌ها
    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::delete('/orders/{order}', [Admin\OrderController::class, 'destroy'])->name('orders.destroy');

    // شارژهای کیف پول
    Route::get('/wallet-deposits', [Admin\WalletDepositController::class, 'index'])->name('wallet-deposits.index');
    Route::post('/wallet-deposits/{transaction}/approve', [Admin\WalletDepositController::class, 'approve'])->name('wallet-deposits.approve');
    Route::post('/wallet-deposits/{transaction}/reject', [Admin\WalletDepositController::class, 'reject'])->name('wallet-deposits.reject');

    // تیکت‌ها
    Route::get('/tickets', [Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [Admin\TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/close', [Admin\TicketController::class, 'close'])->name('tickets.close');
    Route::post('/tickets/{ticket}/reopen', [Admin\TicketController::class, 'reopen'])->name('tickets.reopen');

    // برودکست تلگرام
    Route::get('/broadcast', [Admin\BroadcastController::class, 'index'])->name('broadcast.index');
    Route::post('/broadcast', [Admin\BroadcastController::class, 'send'])->name('broadcast.send');

    // کاربران
    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/toggle-block', [Admin\UserController::class, 'toggleBlock'])->name('users.toggle-block');
    Route::post('/users/{user}/adjust-wallet', [Admin\UserController::class, 'adjustWallet'])->name('users.adjust-wallet');
    Route::post('/users/{user}/send-telegram', [Admin\UserController::class, 'sendTelegram'])->name('users.send-telegram');

    // پلن‌ها
    Route::get('/plans', [Admin\PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [Admin\PlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [Admin\PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [Admin\PlanController::class, 'destroy'])->name('plans.destroy');

    // سرورها و اینباندها
    Route::get('/inbounds', [Admin\InboundController::class, 'index'])->name('inbounds.index');
    Route::post('/servers', [Admin\InboundController::class, 'storeServer'])->name('servers.store');
    Route::put('/servers/{server}', [Admin\InboundController::class, 'updateServer'])->name('servers.update');
    Route::delete('/servers/{server}', [Admin\InboundController::class, 'destroyServer'])->name('servers.destroy');
    Route::post('/servers/{server}/test', [Admin\InboundController::class, 'testServer'])->name('servers.test');
    Route::post('/servers/{server}/import', [Admin\InboundController::class, 'importInbounds'])->name('servers.import');
    Route::post('/inbounds', [Admin\InboundController::class, 'storeInbound'])->name('inbounds.store');
    Route::put('/inbounds/{inbound}', [Admin\InboundController::class, 'updateInbound'])->name('inbounds.update');
    Route::delete('/inbounds/{inbound}', [Admin\InboundController::class, 'destroyInbound'])->name('inbounds.destroy');

    // تنظیمات
    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
});
