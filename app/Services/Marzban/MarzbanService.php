<?php

namespace App\Services\Marzban;

use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * سرویس اتصال به پنل Marzban — رقیب 3x-ui
 * مستندات: https://github.com/Gozargah/Marzban-docs
 *
 * نکته: از Http facade لاراول استفاده می‌کند (قابل fake در تست‌ها)
 */
class MarzbanService
{
    protected ?string $token = null;

    public function __construct(protected Server $server)
    {
        // base = scheme://host:port/path — مسیر اختیاری است
    }

    protected function base(): string
    {
        $path = trim((string) $this->server->api_path, '/');

        return $this->server->api_scheme.'://'.$this->server->api_host.':'.$this->server->api_port
            .($path !== '' ? '/'.$path : '');
    }

    /**
     * تست اتصال + سلامت پنل
     */
    public function testConnection(): array
    {
        try {
            $inbounds = $this->listInbounds();

            return ['ok' => true, 'message' => 'اتصال موفق بود. تعداد اینباندها: '.count($inbounds)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $this->friendlyError($e->getMessage())];
        }
    }

    /**
     * تبدیل خطاهای فنی به راهنمای فارسی
     */
    protected function friendlyError(string $message): string
    {
        if (preg_match('/cURL error (\d+)/i', $message, $m)) {
            $hint = match ((int) $m[1]) {
                6 => __('آدرس یا دامنه پیدا نشد (DNS) — هاست/دامنه پنل را بررسی کنید.'),
                7 => __('اتصال رد شد — پورت بسته است یا IP/پورت اشتباه است.'),
                28 => __('مهلت اتصال تمام شد — پورت مسدود است یا پنل پاسخ نمی‌دهد.'),
                35, 51, 56, 60 => __('خطای SSL — اتصال امن به پنل برقرار نشد. با http یا دامنه دارای گواهی معتبر تست کنید.'),
                default => null,
            };

            if ($hint !== null) {
                return $hint.' (cURL '.$m[1].')';
            }
        }

        return $message;
    }

    /**
     * لاگین و گرفتن توکن JWT
     *
     * @throws \RuntimeException
     */
    public function login(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $response = Http::timeout(25)
            ->acceptJson()
            ->asForm()
            ->post($this->base().'/api/admin/token', [
                'username' => $this->server->username,
                'password' => $this->server->password,
            ]);

        if ($response->failed() || ! ($response->json('access_token'))) {
            Log::warning('marzban login failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 200)]);

            throw new \RuntimeException(__('ورود به پنل Marzban ناموفق بود. آدرس/نام کاربری/رمز را بررسی کنید.').($response->status() !== 200 ? ' (HTTP '.$response->status().')' : ''));
        }

        return $this->token = (string) $response->json('access_token');
    }

    /**
     * درخواست احراز هویت‌شده
     *
     * @throws \RuntimeException
     */
    protected function request(string $method, string $path, ?array $json = null): array
    {
        $this->login();

        $request = Http::timeout(25)
            ->acceptJson()
            ->withToken($this->token);

        $response = $json === null
            ? $request->{strtolower($method)}($this->base().$path)
            : $request->{strtolower($method)}($this->base().$path, $json);

        if ($response->status() === 401) {
            // توکن منقضی — یک‌بار تازه می‌شود
            $this->token = null;
            $this->login();

            $request = Http::timeout(25)->acceptJson()->withToken($this->token);

            $response = $json === null
                ? $request->{strtolower($method)}($this->base().$path)
                : $request->{strtolower($method)}($this->base().$path, $json);
        }

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();

            throw new \RuntimeException(__('خطا از پنل Marzban (کد :code): :msg', [
                'code' => $response->status(),
                'msg' => mb_substr((string) (is_array($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE) : $detail), 0, 200),
            ]));
        }

        return (array) $response->json();
    }

    /**
     * لیست اینباندها — نرمال‌شده مثل 3x-ui: id/protocol/port/remark(tag)
     */
    public function listInbounds(): array
    {
        $data = $this->request('GET', '/api/inbounds');

        $list = $data['inbounds'] ?? (isset($data[0]) ? $data : []);

        return collect($list)
            ->map(fn ($in) => [
                'id' => $in['id'] ?? null,
                'protocol' => $in['protocol'] ?? '',
                'port' => $in['port'] ?? 0,
                'remark' => $in['tag'] ?? ($in['remark'] ?? null),
                'raw' => $in,
            ])
            ->filter(fn ($in) => ! empty($in['id']))
            ->values()
            ->all();
    }

    /**
     * ساخت کاربر (کلاینت) در Marzban — برمی‌گرداند username و لینک اشتراک
     *
     * @param  array<string, array<int,string>>  $tagsByProtocol  گروه تگ‌های اینباند بر اساس پروتکل (خالی = همه اینباندها)
     * @return array{username: string, sub_url: string}
     *
     * @throws \RuntimeException
     */
    public function createUser(string $username, int $totalBytes, int $expiryTimestamp, array $tagsByProtocol = []): array
    {
        $payload = [
            'username' => $username,
            'proxies' => ['vless' => [], 'vmess' => [], 'trojan' => []],
            'expire' => $expiryTimestamp > 0 ? $expiryTimestamp : 0,
            'data_limit' => $totalBytes > 0 ? $totalBytes : 0,
            'data_limit_reset_strategy' => 'no_reset',
        ];

        if ($tagsByProtocol !== []) {
            $payload['inbounds'] = $tagsByProtocol;
        }

        $data = $this->request('POST', '/api/user', $payload);

        if (empty($data['username'])) {
            throw new \RuntimeException(__('ساخت کاربر در Marzban ناموفق بود.'));
        }

        return [
            'username' => (string) $data['username'],
            'sub_url' => $this->subscriptionUrl($data['subscription_url'] ?? null),
        ];
    }

    /**
     * ویرایش کاربر (تمدید/افزایش حجم/فعال‌سازی)
     */
    public function updateUser(string $username, int $totalBytes, int $expiryTimestamp, bool $enable = true): void
    {
        $this->request('PUT', '/api/user/'.rawurlencode($username), [
            'expire' => $expiryTimestamp > 0 ? $expiryTimestamp : 0,
            'data_limit' => $totalBytes > 0 ? $totalBytes : 0,
            'enable' => $enable,
        ]);
    }

    /**
     * غیرفعال‌سازی کاربر (به‌جای حذف — تاریخچه حفظ می‌شود)
     */
    public function disableUser(string $username): void
    {
        try {
            $this->updateUser($username, 0, 0, false);
        } catch (\Throwable $e) {
            // کاربر ممکن است حذف شده باشد — بی‌خطر
            Log::warning('marzban disableUser failed', ['user' => $username, 'error' => $e->getMessage()]);
        }
    }

    /**
     * آمار مصرف کاربر — خروجی هم‌شکل 3x-ui: up/down/total
     */
    public function userTraffic(string $username): ?array
    {
        try {
            $data = $this->request('GET', '/api/user/'.rawurlencode($username));
        } catch (\Throwable) {
            return null;
        }

        if (empty($data['username'])) {
            return null;
        }

        return [
            // Marzban مجموع مصرف را یکجا می‌دهد — در syncTraffic جمع up+down استفاده می‌شود
            'up' => 0,
            'down' => (int) ($data['used_traffic'] ?? 0),
            'total' => (int) ($data['data_limit'] ?? 0),
            'enable' => (bool) ($data['enable'] ?? true),
        ];
    }

    /**
     * لینک اشتراک کامل — مسیر نسبی پنل + دامنه عمومی (node hostname)
     */
    protected function subscriptionUrl(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $host = $this->server->publicHost();

        // دامنه عمومی ممکن است پورت سفارشی داشته باشد
        $base = $this->server->api_scheme.'://'.$host;

        return rtrim($base, '/').'/'.ltrim((string) $path, '/');
    }
}
