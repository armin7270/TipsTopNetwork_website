<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegister(Request $request)
    {
        // ذخیره کد معرف از لینک /register?ref=CODE
        if ($request->query('ref')) {
            $request->session()->put('referral_code', $request->query('ref'));
        }

        return view('auth.register', ['referralCode' => $request->session()->get('referral_code')]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'ref' => ['nullable', 'string', 'max:32'],
        ], [
            'name.required' => __('نام الزامی است.'),
            'phone.required' => __('شماره موبایل الزامی است.'),
            'phone.regex' => __('شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.'),
            'phone.unique' => __('این شماره قبلاً ثبت شده است؛ وارد شوید.'),
            'password.required' => __('رمز عبور الزامی است.'),
            'password.confirmed' => __('تکرار رمز عبور مطابقت ندارد.'),
            'password.min' => __('رمز عبور باید حداقل ۸ کاراکتر باشد.'),
        ]);

        $user = User::create($validated + ['status' => 'active']);

        // سیستم معرفی: اتصال به معرف + هدیه خوش‌آمدگویی
        app(ReferralService::class)->onUserRegistered($user, $validated['ref'] ?? $request->session()->pull('referral_code'));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('success', __('خوش آمدید! حساب شما ساخته شد.'));
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'phone.required' => __('نام کاربری یا شماره موبایل الزامی است.'),
            'password.required' => __('رمز عبور الزامی است.'),
        ]);

        $login = trim($credentials['phone']);

        // ورود با نام کاربری (مخصوص مدیر) یا شماره موبایل
        $user = str_starts_with($login, '09') && ctype_digit($login)
            ? User::where('phone', $login)->first()
            : (User::where('username', $login)->first() ?? User::where('phone', $login)->first());

        if ($user && $user->isBlocked()) {
            return back()
                ->withErrors(['phone' => __('حساب شما مسدود شده است. با پشتیبانی تماس بگیرید.')])
                ->onlyInput('phone');
        }

        if (! Auth::attempt(['phone' => $user?->phone, 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()
                ->withErrors(['phone' => __('نام کاربری یا شماره موبایل یا رمز عبور اشتباه است.')])
                ->onlyInput('phone');
        }

        $request->session()->regenerate();

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
