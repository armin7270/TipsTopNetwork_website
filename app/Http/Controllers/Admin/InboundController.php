<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\Server;
use App\Services\AdminLog;
use App\Services\Xui\XuiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboundController extends Controller
{
    public function index(): View
    {
        $servers = Server::query()->with('inbounds')->orderBy('id')->get();
        $inbounds = Inbound::query()->with('server')->orderBy('server_id')->orderBy('xui_inbound_id')->get();

        return view('admin.inbounds', ['servers' => $servers, 'inbounds' => $inbounds]);
    }

    /**
     * تست اتصال با اطلاعات فرم (بدون ذخیره) — برای دکمه «بررسی اتصال» قبل از ذخیره
     */
    public function testUnsaved(Request $request): JsonResponse
    {
        $data = $request->validate([
            'server_id' => ['nullable', 'integer'],
            'api_scheme' => ['required', 'in:http,https'],
            'api_host' => ['required', 'string', 'max:200'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'api_path' => ['nullable', 'string', 'max:190'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'max:200'],
        ]);

        if (! empty($data['server_id']) && ($base = Server::find($data['server_id']))) {
            // ویرایش سرور موجود: رمز خالی یعنی از رمز ذخیره‌شده استفاده شود
            $transient = clone $base;
        } else {
            if (($data['password'] ?? '') === '') {
                return response()->json(['ok' => false, 'message' => __('برای تست اتصال، رمز عبور پنل را وارد کنید.')]);
            }

            $transient = new Server(['id' => 0]);
        }

        $transient->fill(collect($data)->only(['api_scheme', 'api_host', 'api_port', 'api_path', 'username', 'password'])->all());

        $result = app(XuiService::class, ['server' => $transient])->testConnection();

        return response()->json($result);
    }

    public function storeServer(Request $request): RedirectResponse
    {
        $validated = $this->validateServer($request);

        $validated['is_active'] = $request->boolean('is_active', true);

        // الزام بررسی موفق اتصال قبل از ذخیره
        $transient = new Server($validated + ['id' => 0]);
        $result = app(XuiService::class, ['server' => $transient])->testConnection();

        if (! ($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->with('error', __('ذخیره نشد — ابتدا اتصال به پنل را با موفقیت تست کنید').': '.$result['message']);
        }

        $server = Server::create($validated);

        AdminLog::record($request->user(), 'server_created', $server, $server->name.' — '.$result['message']);

        return back()->with('success', __('سرور ذخیره شد (:test). حالا با دکمه «دریافت از پنل» اینباند‌ها را ایمپورت کنید.', ['test' => $result['message']]));
    }

    public function updateServer(Request $request, Server $server): RedirectResponse
    {
        $validated = $this->validateServer($request, nullablePassword: true);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        // الزام بررسی موفق اتصال قبل از ذخیره (با اطلاعات جدید + رمز قبلی اگر خالی بود)
        $transient = clone $server;
        $transient->fill($validated);
        $result = app(XuiService::class, ['server' => $transient])->testConnection();

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', __('ذخیره نشد — اتصال با اطلاعات جدید برقرار نشد').': '.$result['message']);
        }

        $server->update($validated);

        AdminLog::record($request->user(), 'server_updated', $server, $server->name.' — '.$result['message']);

        return back()->with('success', __('سرور ذخیره شد (:test).', ['test' => $result['message']]));
    }

    public function destroyServer(Request $request, Server $server): RedirectResponse
    {
        AdminLog::record($request->user(), 'server_deleted', $server, $server->name);
        $server->delete();

        return back()->with('success', __('سرور و اینباندهای آن حذف شدند.'));
    }

    public function testServer(Server $server): RedirectResponse
    {
        $result = app(XuiService::class, ['server' => $server])->testConnection();

        return back()->with($result['ok'] ? 'success' : 'error', __('تست اتصال «:name»: :msg', ['name' => $server->name, 'msg' => $result['message']]));
    }

    public function importInbounds(Server $server): RedirectResponse
    {
        try {
            $list = app(XuiService::class, ['server' => $server])->inbounds();
        } catch (\Throwable $e) {
            return back()->with('error', __('دریافت اینباندها ناموفق بود: :msg', ['msg' => $e->getMessage()]));
        }

        $imported = 0;

        foreach ($list as $in) {
            if (empty($in['id'])) {
                continue;
            }

            Inbound::query()->updateOrCreate(
                ['server_id' => $server->id, 'xui_inbound_id' => $in['id']],
                [
                    'protocol' => $in['protocol'] ?? '',
                    'port' => (int) ($in['port'] ?? 0),
                    'remark' => $in['remark'] ?? null,
                    'panel_data' => $in,
                    'is_active' => true,
                ]
            );

            $imported++;
        }

        return back()->with('success', __(':count اینباند از پنل ایمپورت/به‌روزرسانی شد. حالا اینباند‌ها را به پلن‌ها وصل کنید.', ['count' => $imported]));
    }

    public function storeInbound(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'server_id' => ['required', 'exists:servers,id'],
            'xui_inbound_id' => ['required', 'integer', 'min:1'],
            'protocol' => ['required', 'in:vless,vmess,trojan,shadowsocks'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'public_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'remark' => ['nullable', 'string', 'max:100'],
        ]);

        Inbound::query()->updateOrCreate(
            ['server_id' => $validated['server_id'], 'xui_inbound_id' => $validated['xui_inbound_id']],
            $validated + ['panel_data' => null]
        );

        return back()->with('success', __('اینباند ذخیره شد. برای ساخت لینک کانفیگ، حتماً یک‌بار «دریافت از پنل» را اجرا کنید تا تنظیمات کامل اینباند ذخیره شود.'));
    }

    public function updateInbound(Request $request, Inbound $inbound): RedirectResponse
    {
        $validated = $request->validate([
            'public_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'remark' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['public_port'] = $validated['public_port'] ?: null;

        $inbound->update($validated);

        AdminLog::record($request->user(), 'inbound_updated', $inbound);

        return back()->with('success', __('اینباند به‌روزرسانی شد.'));
    }

    public function destroyInbound(Request $request, Inbound $inbound): RedirectResponse
    {
        AdminLog::record($request->user(), 'inbound_deleted', $inbound);
        $inbound->delete();

        return back()->with('success', __('اینباند حذف شد.'));
    }

    protected function validateServer(Request $request, bool $nullablePassword = false): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'api_scheme' => ['required', 'in:http,https'],
            'api_host' => ['required', 'string', 'max:200'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'api_path' => ['nullable', 'string', 'max:190'],
            'username' => ['required', 'string', 'max:100'],
            'password' => [$nullablePassword ? 'nullable' : 'required', 'string', 'max:200'],
            'public_host' => ['nullable', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => __('نام سرور الزامی است.'),
            'api_host.required' => __('آدرس API سرور الزامی است.'),
            'username.required' => __('نام کاربری پنل الزامی است.'),
            'password.required' => __('رمز عبور پنل الزامی است.'),
        ]);
    }
}
