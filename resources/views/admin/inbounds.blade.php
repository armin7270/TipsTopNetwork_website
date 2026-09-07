@extends('admin.layout')

@section('title', __('سرورها و اینباندها'))

@section('content')
<h1 class="text-2xl font-black text-slate-800 dark:text-white">{{ __('سرورها و اینباندها') }}</h1>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="space-y-4">
        <h2 class="font-black text-slate-800 dark:text-white">{{ __('سرورهای پنل (3x-ui)') }}</h2>
        @forelse ($servers as $server)
            <div class="glass-card p-5 text-sm">
                <div class="flex items-center justify-between">
                    <div class="font-black text-slate-800 dark:text-white">{{ $server->name }}</div>
                    <span class="{{ $server->is_active ? 'badge-green' : 'badge-red' }}">{{ $server->is_active ? __('فعال') : __('غیرفعال') }}</span>
                </div>
                @if ($server->last_check_at)
                    <div class="mt-1 text-xs {{ $server->last_check_ok ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $server->last_check_ok ? '●' : '●' }} {{ __('آخرین بررسی') }}: {{ \App\Support\Format::date($server->last_check_at) }}
                        @if (! $server->last_check_ok && $server->last_check_error)— {{ \Illuminate\Support\Str::limit($server->last_check_error, 80) }}@endif
                    </div>
                @endif
                <div dir="ltr" class="mt-2 text-start text-xs text-slate-400">{{ $server->api_scheme }}://{{ $server->api_host }}:{{ $server->api_port }}{{ $server->api_path ? '/'.$server->api_path : '' }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ $server->inbounds->count() }} {{ __('اینباند ثبت شده') }} @if($server->public_host) — {{ __('دامنه عمومی') }}: <span dir="ltr">{{ $server->public_host }}</span>@endif</div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.servers.test', $server) }}">
                        @csrf
                        <button class="btn-ghost px-3 py-1.5 text-xs">🔌 {{ __('تست اتصال') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.servers.import', $server) }}">
                        @csrf
                        <button class="btn-ghost px-3 py-1.5 text-xs">⬇️ {{ __('دریافت از پنل') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.servers.destroy', $server) }}" onsubmit="return confirm('{{ __('سرور و همه اینباندهایش حذف شود؟') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger px-3 py-1.5 text-xs">{{ __('حذف سرور') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="glass-card p-6 text-center text-sm text-slate-400">{{ __('هنوز سروری اضافه نکرده‌اید.') }}</p>
        @endforelse

        <div class="glass-card p-5">
            <h3 class="font-black text-slate-800 dark:text-white">{{ __('افزودن سرور جدید') }}</h3>
            <form method="POST" action="{{ route('admin.servers.store') }}" class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <label class="glass-label">{{ __('نام سرور (مثلاً «آلمان-تانل ایران»)') }}</label>
                    <input type="text" name="name" required class="glass-input">
                </div>
                <div>
                    <label class="glass-label">{{ __('پروتکل API') }}</label>
                    <select name="api_scheme" class="glass-input">
                        <option value="http">http</option>
                        <option value="https">https</option>
                    </select>
                </div>
                <div>
                    <label class="glass-label">{{ __('آدرس API (IP یا دامنه‌ای که پنل باهاش در دسترسه)') }}</label>
                    <input type="text" name="api_host" required dir="ltr" placeholder="127.0.0.1" class="glass-input text-start">
                </div>
                <div>
                    <label class="glass-label">{{ __('پورت API') }}</label>
                    <input type="number" name="api_port" required value="2053" class="glass-input">
                </div>
                <div>
                    <label class="glass-label">{{ __('مسیر مخفی پنل (بدون / — خالی اگر ندارید)') }}</label>
                    <input type="text" name="api_path" dir="ltr" class="glass-input text-start">
                </div>
                <div>
                    <label class="glass-label">{{ __('نام کاربری پنل') }}</label>
                    <input type="text" name="username" required dir="ltr" class="glass-input text-start">
                </div>
                <div>
                    <label class="glass-label">{{ __('رمز عبور پنل') }}</label>
                    <input type="password" name="password" required dir="ltr" class="glass-input text-start">
                </div>
                <div class="sm:col-span-2">
                    <label class="glass-label">{{ __('دامنه/IP عمومی کانفیگ‌ها (اختیاری — خالی = همان آدرس API)') }}</label>
                    <input type="text" name="public_host" dir="ltr" placeholder="your-iran-domain.ir" class="glass-input text-start">
                </div>
                <button class="btn-primary sm:col-span-2">{{ __('افزودن سرور') }}</button>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        <h2 class="font-black text-slate-800 dark:text-white">{{ __('اینباندها') }}</h2>
        <p class="text-xs leading-6 text-slate-400">{{ __('پس از افزودن سرور، دکمه «دریافت از پنل» را بزنید تا اینباند‌ها به‌صورت خودکار ایمپورت شوند. سپس «پورت عمومی» را در صورت تفاوت پورت تانل تنظیم کنید.') }}</p>

        <div class="glass-card overflow-x-auto p-2">
            <table class="glass-table">
                <thead>
                    <tr>
                        <th>{{ __('سرور') }}</th>
                        <th>{{ __('پروتکل') }}</th>
                        <th>{{ __('ID پنل') }}</th>
                        <th>{{ __('پورت پنل') }}</th>
                        <th>{{ __('پورت عمومی') }}</th>
                        <th>{{ __('وضعیت') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inbounds as $inbound)
                        <tr>
                            <td>{{ $inbound->server?->name }}</td>
                            <td>
                                <span dir="ltr" class="rounded-lg bg-indigo-500/10 px-2 py-0.5 text-xs font-bold uppercase text-indigo-600 dark:text-indigo-400">{{ $inbound->protocol }}</span>
                                @if ($inbound->remark)<div class="mt-1 text-xs text-slate-400">{{ $inbound->remark }}</div>@endif
                            </td>
                            <td dir="ltr">{{ $inbound->xui_inbound_id }}</td>
                            <td dir="ltr">{{ $inbound->port }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.inbounds.update', $inbound) }}" class="flex items-center gap-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="public_port" value="{{ $inbound->public_port }}" placeholder="—" class="glass-input w-20 px-2 py-1 text-xs">
                                    <label class="text-xs text-slate-500 dark:text-slate-400"><input type="checkbox" name="is_active" value="1" {{ $inbound->is_active ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 bg-white/50 text-indigo-600 dark:border-white/20 dark:bg-white/10"> {{ __('فعال') }}</label>
                                    <button class="btn-ghost rounded-xl! px-2 py-1 text-xs">{{ __('ذخیره') }}</button>
                                </form>
                            </td>
                            <td class="text-xs">{{ $inbound->panel_data ? '✅ '.__('تنظیمات کامل') : '⚠️ '.__('نیازمند ایمپورت') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.inbounds.destroy', $inbound) }}" onsubmit="return confirm('{{ __('اینباند حذف شود؟') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-slate-400 transition hover:text-red-500">{{ __('حذف') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-slate-400">{{ __('اینباندی ثبت نشده است. سرور را اضافه کنید و «دریافت از پنل» را بزنید.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="glass-card p-5">
            <h3 class="font-black text-slate-800 dark:text-white">{{ __('افزودن دستی اینباند') }}</h3>
            <p class="mt-1 text-xs leading-6 text-slate-400">{{ __('اگر می‌خواهید اینباندی را دستی اضافه کنید. بعد از ذخیره حتماً یک‌بار «دریافت از پنل» را اجرا کنید تا تنظیمات کامل (Reality/TLS و...) ذخیره شود.') }}</p>
            <form method="POST" action="{{ route('admin.inbounds.store') }}" class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                @csrf
                <div>
                    <label class="glass-label">{{ __('سرور') }}</label>
                    <select name="server_id" required class="glass-input">
                        @foreach ($servers as $server)
                            <option value="{{ $server->id }}">{{ $server->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="glass-label">{{ __('شناسه اینباند در پنل (Inbound ID)') }}</label>
                    <input type="number" name="xui_inbound_id" required class="glass-input">
                </div>
                <div>
                    <label class="glass-label">{{ __('پروتکل') }}</label>
                    <select name="protocol" required class="glass-input">
                        <option value="vless">vless</option>
                        <option value="vmess">vmess</option>
                        <option value="trojan">trojan</option>
                        <option value="shadowsocks">shadowsocks</option>
                    </select>
                </div>
                <div>
                    <label class="glass-label">{{ __('پورت') }}</label>
                    <input type="number" name="port" required class="glass-input">
                </div>
                <div>
                    <label class="glass-label">{{ __('پورت عمومی (اختیاری)') }}</label>
                    <input type="number" name="public_port" class="glass-input">
                </div>
                <div>
                    <label class="glass-label">{{ __('برچسب (اختیاری)') }}</label>
                    <input type="text" name="remark" class="glass-input">
                </div>
                <button class="btn-primary sm:col-span-2">{{ __('ذخیره اینباند') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection