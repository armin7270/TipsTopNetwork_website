<?php

namespace App\Services;

use App\Models\Inbound;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserTrial;
use App\Services\Vpn\ConfigBuilder;
use App\Services\Xui\XuiService;
use App\Support\Format;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * سرویس اکانت تست: ساخت اکانت رایگان موقت روی اینباند پیش‌فرض
 */
class TrialService
{
    /**
     * درخواست اکانت تست توسط کاربر
     *
     * @throws \RuntimeException
     */
    public function request(User $user): UserTrial
    {
        if (Setting::get('trial_enabled', '0') !== '1') {
            throw new \RuntimeException(__('قابلیت اکانت تست در حال حاضر غیرفعال است.'));
        }

        $limit = max(1, (int) Setting::get('trial_limit_per_user', 1));

        if ($user->trial_accounts_taken >= $limit) {
            throw new \RuntimeException(__('شما قبلاً از اکانت تست خود استفاده کرده‌اید.'));
        }

        $inbound = $this->defaultInbound();

        if (! $inbound || ! $inbound->server) {
            throw new \RuntimeException(__('اینباند پیش‌فرض برای اکانت تست تنظیم نشده است. با پشتیبانی تماس بگیرید.'));
        }

        $volumeMb = max(50, (int) Setting::get('trial_volume_mb', 500));
        $durationHours = max(1, (int) Setting::get('trial_duration_hours', 24));

        $email = 'trial-'.$user->id.'-'.($user->trial_accounts_taken + 1);
        $uuid = (string) Str::uuid();
        $expiresAt = now()->addHours($durationHours);

        $xui = new XuiService($inbound->server);

        $xui->addClient($inbound, [
            'id' => $uuid,
            'flow' => ($inbound->protocol === 'vless' && ConfigBuilder::isReality($inbound)) ? 'xtls-rprx-vision' : '',
            'email' => $email,
            'limitIp' => 1,
            'totalGB' => $volumeMb * 1024 * 1024,
            'expiryTime' => $expiresAt->getTimestampMs(),
            'enable' => true,
            'tgId' => '',
            'subId' => '',
            'reset' => 0,
        ]);

        $label = 'اکانت تست | '.$inbound->server->name;
        $config = ConfigBuilder::build($inbound, $uuid, $email, $label);

        return DB::transaction(function () use ($user, $email, $uuid, $config, $volumeMb, $expiresAt) {
            User::query()->whereKey($user->id)->lockForUpdate()->increment('trial_accounts_taken');

            $trial = UserTrial::create([
                'user_id' => $user->id,
                'email' => $email,
                'uuid' => $uuid,
                'config' => $config,
                'volume_mb' => $volumeMb,
                'expires_at' => $expiresAt,
            ]);

            NotificationService::send(
                $user,
                'trial_created',
                __('اکانت تست شما فعال شد ✅'),
                __('اکانت تست :volume مگابایتی تا :time در دسترس شماست.', [
                    'volume' => number_format($volumeMb),
                    'time' => Format::date($expiresAt, false),
                ]),
                route('trial.index'),
            );

            return $trial;
        });
    }

    /**
     * اینباند پیش‌فرض برای اکانت تست (قابل انتخاب از تنظیمات)
     */
    public function defaultInbound(): ?Inbound
    {
        $inboundId = Setting::get('trial_inbound_id');

        if ($inboundId) {
            $inbound = Inbound::query()->where('id', $inboundId)->where('is_active', true)->with('server')->first();

            if ($inbound) {
                return $inbound;
            }
        }

        // جایگزین: اولین اینباند فعال متصل به اولین پلن فعال
        return Inbound::query()
            ->where('is_active', true)
            ->whereHas('server', fn ($q) => $q->where('is_active', true))
            ->whereHas('plans', fn ($q) => $q->where('is_active', true))
            ->with('server')
            ->first();
    }
}
