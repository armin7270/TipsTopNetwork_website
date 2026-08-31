<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\Server;
use App\Services\Xui\XuiService;
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

    public function storeServer(Request $request): RedirectResponse
    {
        $validated = $this->validateServer($request);

        Server::create($validated);

        return back()->with('success', __('سرور اضافه شد. حالا با دکمه «دریافت از پنل» اینباند‌ها را ایمپورت کنید.'));
    }

    public function updateServer(Request $request, Server $server): RedirectResponse
    {
        $validated = $this->validateServer($request, nullablePassword: true);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $server->update($validated);

        return back()->with('success', __('سرور به‌روزرسانی شد.'));
    }

    public function destroyServer(Server $server): RedirectResponse
    {
        $server->delete();

        return back()->with('success', __('سرور و اینباندهای آن حذف شدند.'));
    }

    public function testServer(Server $server): RedirectResponse
    {
        $result = (new XuiService($server))->testConnection();

        return back()->with($result['ok'] ? 'success' : 'error', __('تست اتصال «:name»: :msg', ['name' => $server->name, 'msg' => $result['message']]));
    }

    public function importInbounds(Server $server): RedirectResponse
    {
        try {
            $list = (new XuiService($server))->inbounds();
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

        return back()->with('success', __('اینباند به‌روزرسانی شد.'));
    }

    public function destroyInbound(Inbound $inbound): RedirectResponse
    {
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
        ], [
            'name.required' => __('نام سرور الزامی است.'),
            'api_host.required' => __('آدرس API سرور الزامی است.'),
            'username.required' => __('نام کاربری پنل الزامی است.'),
            'password.required' => __('رمز عبور پنل الزامی است.'),
        ]);
    }
}
