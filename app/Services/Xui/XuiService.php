<?php

namespace App\Services\Xui;

use App\Models\Inbound;
use App\Models\Server;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Log;

class XuiService
{
    protected Client $http;

    protected ?CookieJar $jar = null;

    public function __construct(protected Server $server)
    {
        $path = trim((string) $server->api_path, '/');
        $base = $server->api_scheme.'://'.$server->api_host.':'.$server->api_port;
        if ($path !== '') {
            $base .= '/'.$path;
        }

        $this->http = new Client([
            'base_uri' => $base.'/',
            'timeout' => 25,
            'connect_timeout' => 10,
            'verify' => false,
            'http_errors' => false,
        ]);
    }

    /**
     * اتصال به پنل و تست سلامت آن
     */
    public function testConnection(): array
    {
        try {
            $inbounds = $this->inbounds();

            return ['ok' => true, 'message' => 'اتصال موفق بود. تعداد اینباندها: '.count($inbounds)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * ورود به پنل و ذخیره کوکی سشن
     */
    public function login(): void
    {
        $this->jar = new CookieJar;

        $response = $this->http->post('login', [
            'form_params' => [
                'username' => $this->server->username,
                'password' => $this->server->password,
            ],
            'cookies' => $this->jar,
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (($data['success'] ?? false) !== true) {
            $this->jar = null;
            throw new XuiException('ورود به پنل ناموفق بود: '.($data['msg'] ?? 'نام کاربری یا رمز عبور اشتباه است.'));
        }
    }

    /**
     * دریافت لیست کامل اینباندهای پنل
     */
    public function inbounds(): array
    {
        $data = $this->call('GET', 'panel/api/inbounds/list');

        return $data['obj'] ?? [];
    }

    /**
     * ساخت کلاینت جدید روی اینباند
     */
    public function addClient(Inbound $inbound, array $client): void
    {
        $this->call('POST', 'panel/api/inbounds/addClient', [
            'form_params' => [
                'id' => $inbound->xui_inbound_id,
                'settings' => json_encode(['clients' => [$client]], JSON_UNESCAPED_SLASHES),
            ],
        ]);
    }

    /**
     * ویرایش کلاینت موجود
     */
    public function updateClient(Inbound $inbound, string $clientUuid, array $client): void
    {
        $this->call('POST', 'panel/api/inbounds/updateClient/'.$clientUuid, [
            'form_params' => [
                'id' => $inbound->xui_inbound_id,
                'settings' => json_encode(['clients' => [$client]], JSON_UNESCAPED_SLASHES),
            ],
        ]);
    }

    /**
     * حذف کلاینت
     */
    public function deleteClient(string $clientUuid): void
    {
        $this->call('POST', 'panel/api/inbounds/delClient/'.$clientUuid);
    }

    /**
     * دریافت ترافیک مصرفی کلاینت بر اساس ایمیل
     */
    public function clientTraffic(string $email): ?array
    {
        try {
            $data = $this->call('GET', 'panel/api/inbounds/getClientTraffic/'.rawurlencode($email));

            return $data['obj'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('xui clientTraffic failed', ['email' => $email, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ارسال درخواست با ورود مجدد خودکار در صورت منقضی شدن سشن
     */
    protected function call(string $method, string $uri, array $options = []): array
    {
        $lastBody = '';

        for ($attempt = 0; $attempt < 2; $attempt++) {
            if ($this->jar === null) {
                $this->login();
            }

            $options['cookies'] = $this->jar;

            try {
                $response = $this->http->request($method, $uri, $options);
            } catch (\Throwable $e) {
                throw new XuiException('خطا در ارتباط با پنل: '.$e->getMessage());
            }

            $status = $response->getStatusCode();
            $lastBody = (string) $response->getBody();
            $data = json_decode($lastBody, true);

            $sessionExpired = in_array($status, [401, 403], true)
                || ($data === null && stripos($lastBody, 'login') !== false)
                || (($data['success'] ?? null) === false && stripos((string) ($data['msg'] ?? ''), 'login') !== false);

            if ($sessionExpired && $attempt === 0) {
                $this->jar = null;

                continue;
            }

            if ($data === null) {
                throw new XuiException('پاسخ نامعتبر از پنل (کد '.$status.'). مسیر پنل یا آدرس API را بررسی کنید.');
            }

            if (($data['success'] ?? false) !== true) {
                throw new XuiException((string) ($data['msg'] ?? 'خطا در ارتباط با پنل'));
            }

            return $data;
        }

        throw new XuiException('ارتباط با پنل ناموفق بود.');
    }
}
