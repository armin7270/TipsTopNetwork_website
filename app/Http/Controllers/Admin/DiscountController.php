<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Services\AdminLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * مدیریت کدهای تخفیف — صف فروش YugTaa
 */
class DiscountController extends Controller
{
    public function index(): View
    {
        return view('admin.discounts', [
            'discounts' => DiscountCode::query()->latest()->get(),
            'edit' => request('edit') ? DiscountCode::find(request('edit')) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDiscount($request);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['expires_at'] = $validated['expires_at'] ?: null;
        $validated['max_uses'] = $validated['max_uses'] ?: null;

        DiscountCode::create($validated);

        AdminLog::record($request->user(), 'plan_created', null, 'کد تخفیف: '.$validated['code']);

        return back()->with('success', __('کد تخفیف ساخته شد.'));
    }

    public function update(Request $request, DiscountCode $discount): RedirectResponse
    {
        $validated = $this->validateDiscount($request, ignoreCodeFor: $discount);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');
        $validated['expires_at'] = $validated['expires_at'] ?: null;
        $validated['max_uses'] = $validated['max_uses'] ?: null;

        $discount->update($validated);

        AdminLog::record($request->user(), 'plan_updated', null, 'کد تخفیف: '.$validated['code']);

        return redirect()->route('admin.discounts.index')->with('success', __('کد تخفیف به‌روزرسانی شد.'));
    }

    public function toggle(DiscountCode $discount): RedirectResponse
    {
        $discount->update(['is_active' => ! $discount->is_active]);

        return back()->with('success', __('وضعیت کد تخفیف تغییر کرد.'));
    }

    public function destroy(Request $request, DiscountCode $discount): RedirectResponse
    {
        AdminLog::record($request->user(), 'plan_deleted', null, 'کد تخفیف: '.$discount->code);
        $discount->delete();

        return back()->with('success', __('کد تخفیف حذف شد.'));
    }

    protected function validateDiscount(Request $request, ?DiscountCode $ignoreCodeFor = null): array
    {
        $codeRule = ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'];

        if ($ignoreCodeFor) {
            $codeRule[] = 'unique:discount_codes,code,'.$ignoreCodeFor->id;
        } else {
            $codeRule[] = 'unique:discount_codes,code';
        }

        return $request->validate([
            'code' => $codeRule,
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'integer', 'min:1'],
            'min_amount' => ['nullable', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ], [
            'code.required' => __('کد تخفیف الزامی است.'),
            'code.unique' => __('این کد تخفیف قبلاً ثبت شده است.'),
            'value.required' => __('مقدار تخفیف الزامی است.'),
        ]);
    }
}
