# راهنمای دیپلوی (استقرار) وبسایت TipStop Network

این پروژه یک اپلیکیشن **Laravel 13 + Tailwind 4 + SQLite** است و روی **Railway** و **Vercel** قابل استقرار است.

---

## 🔑 اطلاعات ورود مدیر

| فیلد | مقدار |
|---|---|
| نام کاربری | `admin` |
| رمز عبور | `admin` |

ورود از صفحه `/login` با نام کاربری `admin` یا شماره موبایل `09120000000` انجام می‌شود.
⚠️ **بعد از اولین ورود حتماً رمز را از پنل مدیریت → کاربران تغییر دهید.**

---

## 🚄 استقرار روی Railway (پیشنهادی — ساده‌ترین روش)

Railway سرور PHP کامل اجرا می‌کند و برای این پروژه (که صف، وبهوک تلگرام و پرداخت دارد) بهترین گزینه است.

### مراحل:
1. کد را در گیت‌هاب ریپو جدید push کنید.
2. در [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub repo**.
3. Railway به‌صورت خودکار `railway.json` و `nixpacks.toml` را می‌خواند و بیلد می‌کند.
4. متغیرهای محیطی را در تب **Variables** اضافه کنید:

```env
APP_NAME="TipStop Network"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-DOMAIN.up.railway.app
APP_KEY=          # با دستور php artisan key:generate --show بسازید
APP_LOCALE=fa

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

ADMIN_USERNAME=admin
ADMIN_PHONE=09120000000
ADMIN_PASSWORD=admin
```

5. در تب **Settings**، دامنه عمومی (Domain) بسازید.
6. بعد از اولین اجرا، یک‌بار در ترمینال Railway دستور سیدر را اجرا کنید:
   ```
   php artisan migrate --force && php artisan db:seed --force
   ```

> 💡 دیتابیس SQLite به‌صورت پیش‌فرض روی `/tmp` ممکن است با هر دیپلوی ریست شود؛ برای پایداری، یک Volume به مسیر `/app/database` وصل کنید یا از MySQL دیتابیس سرویس Railway استفاده کنید (`DB_CONNECTION=mysql` + مقادیر `DB_HOST` و...).

---

## ▲ استقرار روی Vercel

Vercel اپ PHP را به‌صورت Serverless اجرا می‌کند (فایل `api/index.php` + `vercel.json` آماده است).

### مراحل:
1. کد را push کنید و در [vercel.com](https://vercel.com) → **Add New Project** → ریپو را انتخاب کنید.
2. Vercel خودکار `vercel.json` را تشخیص می‌دهد (PHP Runtime `vercel-php@0.7.4` = PHP 8.3).
3. متغیرهای محیطی:

```env
APP_NAME="TipStop Network"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-PROJECT.vercel.app
APP_KEY=          # php artisan key:generate --show
APP_LOCALE=fa

DB_CONNECTION=sqlite

APP_STORAGE=/tmp/storage

ADMIN_USERNAME=admin
ADMIN_PHONE=09120000000
ADMIN_PASSWORD=admin

NOWPAYMENTS_IPN_SECRET=    # اختیاری — کلید HMAC وبهوک کریپتو
```

4. Deploy بزنید. بعد از دیپلوی، برای ساخت جداول یک‌بار در ترمینال Vercel (`vercel CLI → vercel env pull` و سپس لوکال) یا از طریق یک Route موقت migrate را اجرا کنید.
   ساده‌تر: قبل از push، دیتابیس `database/database.sqlite` را با جداول آماده در ریپو commit کنید (همین فایل موجود است).

### محدودیت‌های Vercel:
- فایل‌سیستم فقط‌خواندنی است (فقط `/tmp`) — پس آپلود رسید و لاگ‌ها موقت‌اند.
- صف (Queue) و Worker بلندمدت ندارد؛ برودکست تلگرام بهتر است از Railway اجرا شود.
- **برای این پروژه (پنل فروش VPN با صف و پرداخت) گزینه Railway قابل اعتمادتر است.**

---

## 🔧 دستورات مفید

```sh
# نصب لوکال
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed        # اکانت admin/admin می‌سازد
php artisan serve

# بیلد فرانت‌اند
npm run build

# رفرش کامل دیتابیس + اکانت مدیر
php artisan migrate:fresh --seed
```

## 🛡 نکات امنیتی بعد از دیپلوی
1. رمز `admin` را عوض کنید.
2. `APP_DEBUG=false` باشد.
3. `NOWPAYMENTS_IPN_SECRET` را در پنل NOWPayments ست کنید تا وبهوک کریپتو امضاشده باشد.
4. دامنه واقعی را در `APP_URL` و تنظیمات پنل (`sub_base_url`) وارد کنید.
