<?php

namespace App\Services\Xui;

use App\Models\Inbound;
use App\Models\Server;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class XuiService
{
    /**
     * طول عمر کش کوکی سشن پنل — جلوگیری از لاگین مجدد در هر درخواست
     */
    protected const SESSION_TTL = 300;

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
            // اکثر پنل‌های 3x-ui گواهی self-signed دارند — اعتبارسنجی SSL همیشه خاموش
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
            return ['ok' => false, 'message' => $this->friendlyError($e->getMessage())];
        }
    }

    /**
     * تبدیل خطاهای فنی (cURL و...) به راهنمای قابل فهم فارسی
     */
    protected function friendlyError(string $message): string
    {
        if (preg_match('/cURL error (\d+)/i', $message, $m)) {
            $hint = match ((int) $m[1]) {
                6 => __('آدرس یا دامنه پیدا نشد (DNS) — هاست/دامنه پنل را بررسی کنید.'),
                7 => __('اتصال رد شد — پورت بسته است یا IP/پورت اشتباه است (فایروال سرور پنل را هم چک کنید).'),
                28 => __('مهلت اتصال تمام شد — پورت مسدود است یا پنل پاسخ نمی‌دهد.'),
                35, 51, 56, 60 => __('خطای SSL — اتصال امن به پنل برقرار نشد. اگر پنل روی https با گواهی ناقص است، با http تست کنید.'),
                default => null,
            };

            if ($hint !== null) {
                return $hint.' (cURL '.$m[1].')';
            }
        }

        return $message;
    }

    /**
     * ورود به پنل و ذخیره کوکی سشن (با استفاده از سشن کش‌شده در صورت وجود)
     */
    public function login(): void
    {
        // تلاش با سشن کش‌شده — اگر منقضی باشد، در call() دوباره لاگین می‌کنیم
        $cached = Cache::get($this->sessionCacheKey());

        if (is_array($cached) && ($cached['value'] ?? '') !== '') {
            $jar = new CookieJar;
            $jar->setCookie(new SetCookie([
                'Name' => (string) ($cached['name'] ?? '3x-ui'),
                'Value' => (string) $cached['value'],
                'Domain' => $this->server->api_host,
                'Path' => '/',
            ]));

            $this->jar = $jar;

            return;
        }

        $this->performLogin();
    }

    /**
     * لاگین واقعی به پنل و کش‌کردن کوکی سشن
     *
     * پنل‌های جدید 3x-ui یک لایه CSRF دارند: قبل از POST /login باید با
     * GET /csrf-token توکن گرفت و آن را در هدر X-CSRF-Token فرستاد —
     * وگرنه پنل 403 Forbidden خالی برمی‌گرداند. پنل‌های قدیمی این endpoint
     * را ندارند (404) و بدون هدر لاگین می‌شوند — هر دو حالت پشتیبانی می‌شود.
     */
    protected function performLogin(): void
    {
        $this->jar = new CookieJar;

        $headers = [];

        $token = $this->fetchCsrfToken();

        if ($token !== null) {
            $headers['X-CSRF-Token'] = $token;
        }

        $response = $this->http->post('login', [
            'form_params' => [
                'username' => $this->server->username,
                'password' => $this->server->password,
            ],
            'cookies' => $this->jar,
            'headers' => $headers,
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (($data['success'] ?? false) !== true) {
            $this->jar = null;
            throw new XuiException('ورود به پنل ناموفق بود: '.($data['msg'] ?? 'نام کاربری یا رمز عبور اشتباه است.'));
        }

        // کش‌کردن کوکی سشن برای چند دقیقه (۵ دقیقه — امن: در انقضا دوباره لاگین می‌شود)
        foreach ($this->jar->toArray() as $cookie) {
            if (($cookie['Value'] ?? '') !== '') {
                Cache::put($this->sessionCacheKey(), $cookie, now()->addSeconds(self::SESSION_TTL));

                return;
            }
        }
    }

    /**
     * گرفتن توکن CSRF از پنل‌های جدید 3x-ui — در پنل‌های قدیمی (404) null
     */
    protected function fetchCsrfToken(): ?string
    {
        try {
            $response = $this->http->get('csrf-token', ['cookies' => $this->jar]);
        } catch (\Throwable) {
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data)) {
            return null;
        }

        $token = $data['obj'] ?? $data['token'] ?? $data['csrf_token'] ?? $data['csrfToken'] ?? null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    protected function sessionCacheKey(): string
    {
        // برای سرورهای ذخیره‌نشده (تست قبل از ذخیره) از اثر اتصال استفاده می‌کنیم
        if ($this->server->id) {
            return 'xui_session_'.$this->server->id;
        }

        return 'xui_session_probe_'.md5(
            $this->server->api_scheme.'://'.$this->server->api_host.':'.$this->server->api_port
            .'/'.trim((string) $this->server->api_path, '/').'@'.$this->server->username
        );
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

            // درخواست‌های نوشتاری (POST/PUT/DELETE) در پنل‌های جدید 3x-ui هدر CSRF می‌خواهند
            if (! in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true)) {
                $token = $this->fetchCsrfToken();

                if ($token !== null) {
                    $options['headers'] = array_merge($options['headers'] ?? [], ['X-CSRF-Token' => $token]);
                }
            }

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
                // سشن (کش‌شده یا جاری) منقضی شده — کش پاک و لاگین واقعی انجام می‌شود
                Cache::forget($this->sessionCacheKey());
                $this->performLogin();

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
