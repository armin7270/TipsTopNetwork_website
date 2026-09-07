<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * پروفایل کاربری: ویرایش نام، تغییر رمز عبور، حذف حساب
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ], [
            'name.required' => __('نام الزامی است.'),
        ]);

        $request->user()->update(['name' => $validated['name']]);

        return back()->with('success', __('مشخصات شما ذخیره شد.'));
    }

    public function showPasswordForm(): View
    {
        return view('profile.password', [
            'mustChange' => is_null(auth()->user()->password_changed_at),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => __('رمز فعلی الزامی است.'),
            'current_password.current_password' => __('رمز فعلی اشتباه است.'),
            'password.required' => __('رمز جدید الزامی است.'),
            'password.confirmed' => __('تکرار رمز جدید مطابقت ندارد.'),
            'password.min' => __('رمز جدید باید حداقل ۸ کاراکتر باشد.'),
        ]);

        $user->update([
            'password' => $validated['password'],
            'password_changed_at' => now(),
        ]);

        return redirect()->route('profile.edit')->with('success', __('رمز عبور شما با موفقیت تغییر کرد. ✅'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ], [
            'password.current_password' => __('رمز عبور اشتباه است.'),
        ]);

        $user = $request->user();

        if ($user->isAdmin()) {
            return back()->with('error', __('حساب مدیر از اینجا حذف نمی‌شود.'));
        }

        // غیرفعال‌سازی حساب + قطع نشست‌ها (حذف فیزیکی نه — سفارش‌ها و تراکنش‌ها باید بمانند)
        $user->update([
            'status' => 'blocked',
            'telegram_chat_id' => null,
            'bot_state' => null,
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('حساب شما غیرفعال شد. برای بازگشت با پشتیبانی تماس بگیرید.'));
    }
}
