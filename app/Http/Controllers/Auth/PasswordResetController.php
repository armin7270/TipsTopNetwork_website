<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * بازیابی رمز عبور با کد یک‌بارمصرف پیامکی (OTP):
 * فراموشی رمز → ارسال کد ۶ رقمی به شماره ثبت‌شده → تایید → تعیین رمز جدید
 * کد هش‌شده در کش با انقضای ۱۰ دقیقه نگه‌داری می‌شود (حداکثر ۵ تلاش تایید).
 */
class PasswordResetController extends Controller
{
    protected const CACHE_PREFIX = 'password_otp_';

    protected const CODE_TTL_MINUTES = 10;

    protected const MAX_ATTEMPTS = 5;

    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ], [
            'phone.required' => __('شماره موبایل الزامی است.'),
            'phone.regex' => __('شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.'),
        ]);

        $phone = $validated['phone'];

        // جلوگیری از ارسال مجدد سریع (حداقل فاصله ۶۰ ثانیه)
        if (cache()->has(self::CACHE_PREFIX.'cooldown_'.$phone)) {
            return back()->withErrors(['phone' => __('برای دریافت کد جدید کمی صبر کنید (حداقل یک دقیقه).')]);
        }

        $user = User::query()->where('phone', $phone)->first();

        // پیام یکسان در هر دو حالت — جلوگیری از کشف ثبت‌بودن شماره
        $genericMessage = __('اگر این شماره در سیستم ثبت شده باشد، کد بازیابی برای شما ارسال می‌شود.');

        if (! $user || $user->isBlocked()) {
            Log::warning('password reset requested for unknown/blocked phone', ['phone' => $phone]);

            return back()->with('info', $genericMessage);
        }

        if (! SmsService::isEnabled()) {
            return back()->withErrors(['phone' => __('بازیابی رمز عبور پیامکی فعال نیست. لطفاً با پشتیبانی تماس بگیرید.')]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        cache()->put(self::CACHE_PREFIX.$phone, [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        cache()->put(self::CACHE_PREFIX.'cooldown_'.$phone, true, now()->addSeconds(60));

        $sent = SmsService::send($phone, __(
            ':site: کد بازیابی رمز عبور شما: :code — این کد تا ۱۰ دقیقه اعتبار دارد.',
            ['site' => config('app.name', 'TipStop'), 'code' => $code]
        ));

        if (! $sent) {
            Log::error('password reset otp sms failed', ['phone' => $phone]);

            return back()->withErrors(['phone' => __('ارسال پیامک ناموفق بود. لطفاً چند لحظه بعد دوباره تلاش کنید.')]);
        }

        session(['password_reset_phone' => $phone]);

        return redirect()
            ->route('password.reset')
            ->with('success', __('کد بازیابی به شماره شما پیامک شد. کد تا ۱۰ دقیقه اعتبار دارد.'));
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get('password_reset_phone');

        if (! $phone || ! cache()->has(self::CACHE_PREFIX.$phone)) {
            return redirect()->route('password.request')
                ->withErrors(['phone' => __('ابتدا کد بازیابی دریافت کنید.')]);
        }

        return view('auth.reset-password', ['phone' => $phone]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $phone = $request->session()->get('password_reset_phone');

        if (! $phone) {
            return redirect()->route('password.request')
                ->withErrors(['phone' => __('ابتدا کد بازیابی دریافت کنید.')]);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'code.required' => __('کد بازیابی الزامی است.'),
            'code.digits' => __('کد بازیابی باید ۶ رقم باشد.'),
            'password.required' => __('رمز عبور الزامی است.'),
            'password.confirmed' => __('تکرار رمز عبور مطابقت ندارد.'),
            'password.min' => __('رمز عبور باید حداقل ۸ کاراکتر باشد.'),
        ]);

        $key = self::CACHE_PREFIX.$phone;
        $otp = cache()->get($key);

        if (! is_array($otp)) {
            return redirect()->route('password.request')
                ->withErrors(['phone' => __('کد بازیابی منقضی شده است. لطفاً کد جدید دریافت کنید.')]);
        }

        if ($otp['attempts'] >= self::MAX_ATTEMPTS) {
            cache()->forget($key);

            return back()->withErrors(['code' => __('تعداد تلاش‌های ناموفق بیش از حد مجاز است. لطفاً کد جدید دریافت کنید.')]);
        }

        if (! Hash::check((string) $validated['code'], (string) $otp['hash'])) {
            $otp['attempts']++;
            cache()->put($key, $otp, now()->addMinutes(self::CODE_TTL_MINUTES));

            $remaining = self::MAX_ATTEMPTS - $otp['attempts'];

            return back()->withErrors(['code' => __('کد بازیابی اشتباه است. (:n تلاش باقی‌مانده)', ['n' => $remaining])]);
        }

        $user = User::query()->where('phone', $phone)->first();

        if (! $user || $user->isBlocked()) {
            cache()->forget($key);

            return redirect()->route('login')->withErrors(['phone' => __('حساب شما قابل بازیابی نیست. با پشتیبانی تماس بگیرید.')]);
        }

        $user->update([
            'password' => $validated['password'],
            'password_changed_at' => now(),
        ]);

        cache()->forget($key);
        $request->session()->forget('password_reset_phone');
        $request->session()->regenerate();

        Log::info('password reset completed via sms otp', ['user_id' => $user->id]);

        return redirect()
            ->route('login')
            ->with('success', __('رمز عبور شما با موفقیت تغییر کرد. حالا با رمز جدید وارد شوید.'));
    }
}
