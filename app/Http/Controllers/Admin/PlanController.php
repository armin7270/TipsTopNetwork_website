<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::query()->with('inbounds')->orderBy('sort_order')->orderBy('id')->get();
        $inbounds = Inbound::query()->with('server')->where('is_active', true)->get();
        $edit = request('edit') ? Plan::with('inbounds')->find(request('edit')) : null;

        return view('admin.plans', ['plans' => $plans, 'inbounds' => $inbounds, 'edit' => $edit]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePlan($request);

        $plan = Plan::create($validated);
        $plan->inbounds()->sync($request->input('inbounds', []));

        return redirect()->route('admin.plans.index')->with('success', __('پلن ساخته شد.'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $this->validatePlan($request);

        $plan->update($validated);
        $plan->inbounds()->sync($request->input('inbounds', []));

        return redirect()->route('admin.plans.index')->with('success', __('پلن به‌روزرسانی شد.'));
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->orders()->exists()) {
            $plan->update(['is_active' => false]);

            return back()->with('success', __('این پلن سفارش دارد و حذف نشد؛ به‌جای آن غیرفعال شد.'));
        }

        $plan->delete();

        return back()->with('success', __('پلن حذف شد.'));
    }

    protected function validatePlan(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price_toman' => ['required', 'integer', 'min:0'],
            'volume_gb' => ['required', 'numeric', 'min:0', 'max:100000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'inbounds' => ['nullable', 'array'],
            'inbounds.*' => ['exists:inbounds,id'],
        ], [
            'name.required' => __('نام پلن الزامی است.'),
            'price_toman.required' => __('قیمت پلن الزامی است.'),
            'duration_days.required' => __('مدت اعتبار الزامی است.'),
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }
}
