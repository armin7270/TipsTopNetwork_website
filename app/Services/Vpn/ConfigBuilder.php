<?php

namespace App\Services\Vpn;

use App\Models\Inbound;

class ConfigBuilder
{
    /**
     * آیا اینباند VLESS از Reality استفاده می‌کند؟ (برای تعیین flow کلاینت)
     */
    public static function isReality(Inbound $inbound): bool
    {
        return ($inbound->panel_data['streamSettings']['security'] ?? 'none') === 'reality';
    }

    /**
     * ساخت لینک کانفیگ (vless/vmess/trojan/ss) از روی اطلاعات اینباند
     */
    public static function build(Inbound $inbound, string $uuid, string $email, string $label): ?string
    {
        $data = $inbound->panel_data;
        if (empty($data)) {
            return null;
        }

        $server = $inbound->server;
        if (! $server) {
            return null;
        }

        $host = $server->publicHost();
        $port = $inbound->publicPort();
        $settings = $data['settings'] ?? [];
        $stream = $data['streamSettings'] ?? [];
        $network = $stream['network'] ?? 'tcp';
        $security = $stream['security'] ?? 'none';
        $remark = rawurlencode($label);

        $params = self::transportParams($stream, $network, $security);

        return match ($inbound->protocol) {
            'vless' => self::vless($uuid, $host, $port, $params, $remark),
            'vmess' => self::vmess($uuid, $host, $port, $params, $label, $network, $security),
            'trojan' => self::trojan($uuid, $host, $port, $params, $remark),
            'shadowsocks' => self::shadowsocks($settings, $email, $host, $port, $remark),
            default => null,
        };
    }

    protected static function vless(string $uuid, string $host, int $port, array $params, string $remark): string
    {
        $params['encryption'] = 'none';

        return "vless://{$uuid}@{$host}:{$port}?".http_build_query($params).'#'.$remark;
    }

    protected static function vmess(string $uuid, string $host, int $port, array $params, string $label, string $network, string $security): string
    {
        $json = [
            'v' => '2',
            'ps' => $label,
            'add' => $host,
            'port' => (string) $port,
            'id' => $uuid,
            'aid' => '0',
            'scy' => 'auto',
            'net' => $network,
            'type' => ($params['type'] ?? '') === 'http' ? 'http' : 'none',
            'host' => $params['host'] ?? '',
            'path' => $params['path'] ?? ($params['serviceName'] ?? ''),
            'tls' => in_array($security, ['tls', 'reality'], true) ? $security : '',
            'sni' => $params['sni'] ?? '',
            'fp' => $params['fp'] ?? '',
            'alpn' => $params['alpn'] ?? '',
        ];

        if ($security === 'reality') {
            $json['pbk'] = $params['pbk'] ?? '';
            $json['sid'] = $params['sid'] ?? '';
        }

        return 'vmess://'.base64_encode(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    protected static function trojan(string $uuid, string $host, int $port, array $params, string $remark): string
    {
        return "trojan://{$uuid}@{$host}:{$port}?".http_build_query($params).'#'.$remark;
    }

    protected static function shadowsocks(array $settings, string $email, string $host, int $port, string $remark): string
    {
        $method = $settings['method'] ?? 'aes-128-gcm';

        // در پنل 3x-ui رمز عبور کلاینت Shadowsocks معمولاً برابر email است
        $password = $email;
        foreach ($settings['clients'] ?? [] as $client) {
            if (($client['email'] ?? '') === $email) {
                $password = $client['password'] ?? $client['id'] ?? $email;
                break;
            }
        }

        $userInfo = base64_encode($method.':'.$password);

        return "ss://{$userInfo}@{$host}:{$port}#{$remark}";
    }

    /**
     * استخراج پارامترهای ترنسپورت و امنیت از تنظیمات اینباند
     */
    protected static function transportParams(array $stream, string $network, string $security): array
    {
        $params = ['type' => $network, 'security' => $security];

        if ($security === 'reality') {
            $rs = $stream['realitySettings'] ?? [];
            $params['pbk'] = $rs['settings']['publicKey'] ?? $rs['publicKey'] ?? '';
            $params['sid'] = $rs['shortIds'][0] ?? '';
            $params['sni'] = $rs['serverNames'][0] ?? '';
            $params['fp'] = $rs['settings']['fingerprint'] ?? 'chrome';
        } elseif ($security === 'tls') {
            $ts = $stream['tlsSettings'] ?? [];
            $params['sni'] = $ts['serverName'] ?? '';
            $params['fp'] = $ts['fingerprint'] ?? '';
            if (! empty($ts['alpn'])) {
                $params['alpn'] = is_array($ts['alpn']) ? implode(',', $ts['alpn']) : $ts['alpn'];
            }
        }

        switch ($network) {
            case 'ws':
                $ws = $stream['wsSettings'] ?? [];
                $params['path'] = $ws['path'] ?? '/';
                if ($wsHost = ($ws['headers']['Host'] ?? $ws['host'] ?? null)) {
                    $params['host'] = $wsHost;
                }
                break;

            case 'httpupgrade':
                $hu = $stream['httpupgradeSettings'] ?? [];
                $params['path'] = $hu['path'] ?? '/';
                if ($huHost = ($hu['host'] ?? null)) {
                    $params['host'] = $huHost;
                }
                break;

            case 'grpc':
                $g = $stream['grpcSettings'] ?? [];
                $params['serviceName'] = $g['serviceName'] ?? '';
                $params['mode'] = ! empty($g['multiMode']) ? 'multi' : 'gun';
                break;

            case 'xhttp':
            case 'splithttp':
                $params['type'] = 'xhttp';
                $x = $stream['xhttpSettings'] ?? $stream['splithttpSettings'] ?? [];
                $params['path'] = $x['path'] ?? '/';
                if ($xHost = ($x['host'] ?? null)) {
                    $params['host'] = $xHost;
                }
                $params['mode'] = $x['mode'] ?? 'auto';
                break;

            case 'tcp':
                $t = $stream['tcpSettings'] ?? [];
                if (($t['header']['type'] ?? 'none') === 'http') {
                    $params['type'] = 'http';
                    if ($tHost = ($t['header']['request']['headers']['Host'][0] ?? null)) {
                        $params['host'] = $tHost;
                    }
                    if ($tPath = ($t['header']['request']['path'][0] ?? null)) {
                        $params['path'] = $tPath;
                    }
                }
                break;
        }

        return $params;
    }
}
