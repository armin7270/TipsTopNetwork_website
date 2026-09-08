# 🚚 مهاجرت به اکانت جدید Railway (بدون از دست رفتن داده)

> **خلاصه:** تریال Railway هر ۳۰ روز تمام می‌شود. این سیستم کار را به ۳ قدم ساده تبدیل می‌کند:
> ۱) در اکانت قدیمی، Action گیت‌هاب state (دیتابیس + فایل‌ها) را **رمزنگاری‌شده** از سایت می‌گیرد و در ریپو کامیت می‌کند.
> ۲) در اکانت جدید Railway، همان ریپو را دیپلوی می‌کنید.
> ۳) در اولین استارت، سایت **خودش** state را بازمی‌گرداند — همه کاربران، سفارش‌ها، کیف پول و تنظیمات سر جایشان هستند.

---

## این سیستم چطور کار می‌کند؟

```
┌─────────────────────┐        ┌──────────────────┐        ┌─────────────────────┐
│  Railway اکانت قدیمی │  HTTPS │     GitHub        │  git   │  Railway اکانت جدید  │
│                     │───────▶│                  │───────▶│                     │
│ /deploy/state       │ state.bin (رمزنگاری‌شده) │ startCommand:      │
│ (AES-256 با STATE_KEY)│ (deploy-state/state.bin) │ app:restore-state  │
└─────────────────────┘        └──────────────────┘        └─────────────────────┘
```

- **`/deploy/state`** — اندپوینت جدید (محافظت با `DEPLOY_KEY`) که کل دیتابیس SQLite + فایل‌های `storage/app` (رسیدها، پیوست‌ها) را زیپ و با **AES-256-CBC** (کلید از `STATE_KEY` با PBKDF2) رمزنگاری می‌کند. فرمت فایل: `TSSTATE1`.
- **`.github/workflows/state-sync.yml`** — هر شب بکاپ را از سایت می‌گیرد و در برنچ `site-state` کامیت می‌کند (بکاپ اضطراری خارج از Railway). با اجرای دستی با گزینه **promote**، فایل روی `main` می‌رود.
- **`php artisan app:restore-state`** — فایل را رمزگشایی و دیتابیس + فایل‌ها را بازمی‌گرداند و مایگریت‌های جدید را هم اجرا می‌کند.
- **`railway.json` / `Procfile`** — `startCommand` حالا قبل از `migrate`، اگر `deploy-state/state.bin` وجود داشته باشد، بازیابی را انجام می‌دهد. اگر فایل نباشد (استارت عادی) هیچ اتفاقی نمی‌افتد (`|| true`).

### چرا امن است؟
- فایل state در ریپو **رمزنگاری‌شده** است — بدون `STATE_KEY` هیچ‌کس نمی‌تواند بخواندش.
- `STATE_KEY` هرگز از سرویس خارج نمی‌شود؛ در گیت‌هاب فقط `DEPLOY_KEY` (کلید احراز) در Secrets است.
- انتقال state از طریق دانلود با هدر `X-Deploy-Key` انجام می‌شود (بدون کلید در URL/لاگ).

---

## گام ۰ — آماده‌سازی (یک‌بار، همین حالا در اکانت فعلی)

1. **متغیرهای Railway فعلی را کامل کنید** (Railway → سرویس وب → Variables):
   ```
   DEPLOY_KEY=<یک رشته تصادفی طولانی — php -r "echo bin2hex(random_bytes(24));">
   STATE_KEY=<یک رشته تصادفی دیگر — php -r "echo bin2hex(random_bytes(32));">
   SITE_URL=https://YOUR-APP.up.railway.app
   ```
   > `SITE_URL` فقط برای راحتی Action استفاده می‌شود و می‌تواند هر جایی تعریف شود.

2. **Secret های گیت‌هاب را بسازید** (ریپو → Settings → Secrets and variables → Actions → New repository secret):
   | Secret | مقدار |
   |---|---|
   | `SITE_URL` | `https://YOUR-APP.up.railway.app` (آدرس اکانت فعلی) |
   | `DEPLOY_KEY` | همان مقدار `DEPLOY_KEY` بالا |

3. **دستور را یک‌بار دستی تست کنید** (اکشن → State Sync → Run workflow → بدون promote):
   - باید برنچ `site-state` ساخته شود و `deploy-state/state.bin` داخلش باشد. ✅

از این لحظه، **هر شب ساعت ۰۰:۰۰** بکاپ رمزنگاری‌شده در برنچ `site-state` به‌روز می‌شود — حتی اگر Railway یک‌شبه ناپدید شود، داده‌ها در گیت‌هاب هستند.

---

## گام ۱ — هنگام اتمام تریال: خروج نهایی (promote)

چند روز قبل از پایان تریال (تا جدیدترین سفارش‌ها گرفته شوند):

1. در ریپو → **Actions → State Sync → Run workflow**:
   - `promote` را **✔️ true** بزنید.
2. اکشن state جدید را می‌گیرد و **روی `main`** کامیت می‌کند.
3. ⚠️ در Railway فعلی، **Deployments → دکمه Pause** را بزنید تا اکانت قدیمی با کامیت جدید ری‌دپلوی نشود (یا اگر دیر شد، مشکلی نیست — فقط یک ری‌استارت اضافه است).

---

## گام ۲ — راه‌اندازی اکانت جدید Railway

1. با اکانت جدید به [railway.app](https://railway.app) بروید → **New Project → Deploy from GitHub repo** → همین ریپو.
   (گیت‌هاب مشترک است — اکانت جدید همان ریپو را می‌بیند.)
2. **New → Database → PostgreSQL** اضافه کنید و در Variables سرویس وب:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://NEW-APP.up.railway.app
   APP_KEY=          # همان APP_KEY اکانت قدیمی! (مهم برای رمزنگاری session و فیلد password پنل)
   APP_LOCALE=fa

   DB_CONNECTION=pgsql
   DB_URL=${{Postgres.DATABASE_URL}}

   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database

   ADMIN_USERNAME=admin
   ADMIN_PHONE=09120000000
   ADMIN_PASSWORD=<رمز قوی جدید>

   DEPLOY_KEY=<همان مقدار قبلی>
   STATE_KEY=<همان مقدار قبلی — کلید رمزگشایی state!>
   ```
   > ⚠️ **APP_KEY و STATE_KEY باید دقیقاً همان اکانت قبلی باشند** — APP_KEY برای رمزهای ذخیره‌شده (مثل پسورد پنل 3x-ui در دیتابیس) و STATE_KEY برای بازکردن فایل state ضروری‌اند.

3. Deploy را بزنید. در استارت اول، `app:restore-state deploy-state/state.bin --force` اجرا می‌شود:
   ```
   🔒 رمزگشایی state...
   📦 بازیابی دیتابیس...   ✅
   📁 بازیابی فایل‌های storage...  ✅
   🎉 بازیابی state کامل شد.
   ```
4. ⚠️ **نکته مهم دیتابیس:** فایل state شامل دیتابیس **SQLite** اکانت قبلی است و روی Postgres اکانت جدید کپی نمی‌شود. دو حالت دارید:
   - **حالت پیش‌فرض این پروژه (توصیه‌شده):** به‌جای Postgres، یک **Volume** در Railway بسازید و به مسیر `/app/database` وصل کنید و `DB_CONNECTION=sqlite` بگذارید. آنگاه state مستقیماً جایگزین همان فایل می‌شود و **همه داده‌ها برگشت می‌خورند**.
   - اگر Postgres می‌خواهید: در پنل اکانت قدیمی (قبل از خاموشی) از صفحه **«📦 بکاپ و مهاجرت»** → «خروجی دیتابیس» دامپ SQL بگیرید و در اکانت جدید از همان صفحه Import کنید (این مسیر دستی است و برای Postgres لازم می‌شود).

5. سرویس‌های دیگر را هم مثل قبل اضافه کنید:
   - **Worker**: از Settings → Services → New Service → **Duplicate** یا دستور `php artisan queue:work database --sleep=3 --tries=3 --timeout=900` (فایل Procfile از قبل دارد).
   - **Cron**: Railway Cron Job با `php artisan schedule:run` هر دقیقه.
6. دامنه جدید (Settings → Networking → Generate Domain) را در تنظیمات پنل ادمین سایت (بخش «⚙️ تنظیمات» → «آدرس پایه لینک اشتراک») به‌روز کنید و `APP_URL` هم همین باشد.
7. وبهوک تلگرام را دوباره ست کنید (ربات تلگرام → `/start` → از پنل ادمین دکمه تنظیم وبهوک) چون آدرس عوض شده.

---

## گام ۳ — پاک‌سازی (بعد از تایید سلامت سایت جدید)

وقتی مطمئن شدید همه‌چیز روی اکانت جدید کار می‌کند (ورود کاربران، سفارش‌ها، لینک اشتراک):

1. فایل state را از ریپو حذف کنید تا حجم/امنیت نگه‌داری نشود:
   ```
   git rm deploy-state/state.bin && git commit -m "remove migration state"
   ```
2. در Railway جدید، متغیر `STATE_KEY` را بماند (برای مهاجرت بعدی به کار می‌آید) — فقط مطمئن شوید `.gitignore` شامل `deploy-state/` است (اضافه شده ✅).
3. اکانت قدیمی را ببندید.

---

## سناریوی اضطراری — اگر فرصت promote پیدا نکردید

اگر Railway یک‌شبه قطع شد و نتوانستید promote بزنید، نگران نباشید:
- برنچ **`site-state`** بکاپ شب قبل را دارد!
- در اکانت جدید: برنچ `site-state` را merge کنید یا فقط فایل را کپی کنید:
  ```
  git checkout site-state -- deploy-state/state.bin
  git commit -m "restore from last nightly backup"
  git push
  ```
- اکانت جدید با همین استارت، خودش را بازسازی می‌کند (حداکثر یک روز داده از دست می‌رود).

---

## چک‌لیست سریع

- [ ] `DEPLOY_KEY` + `STATE_KEY` + `SITE_URL` در Variables Railway فعلی
- [ ] Secret های `SITE_URL` و `DEPLOY_KEY` در گیت‌هاب
- [ ] یک اجرای دستی State Sync → برنچ `site-state` ساخته شد
- [ ] (قبل از انقضا) Run workflow با `promote=true`
- [ ] اکانت جدید: Deploy from repo + Volume `/app/database` + `DB_CONNECTION=sqlite` + همان `APP_KEY`/`STATE_KEY`
- [ ] بعد از استارت: لاگ باید «🎉 بازیابی state کامل شد» را نشان دهد
- [ ] Worker + Cron + دامنه جدید + وبهوک تلگرام
- [ ] حذف `deploy-state/state.bin` از ریپو
