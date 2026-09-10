# 🚀 سایت فروش کانفیگ TipStop Network

سایت فروش اشتراک کانفیگ (V2Ray) با اتصال مستقیم به پنل **3x-ui** — ساخته‌شده با Laravel 13، SQLite و Tailwind CSS.

پس از تأیید پرداخت کارت به کارت توسط شما، کانفیگ‌ها **به‌صورت خودکار** در پنل 3x-ui ساخته می‌شوند و مشتری فقط یک «لینک اشتراک» می‌گیرد که شامل همه پروتکل‌ها و همه اینباند‌های پلن خریداری‌شده است.

---

## ۱) اجرای سایت روی کامپیوتر خودتان (همین حالا آماده است)

**راه ساده:** روی فایل **«شروع سایت.bat»** دابل‌کلیک کنید — سرور روشن و سایت در مرورگر باز می‌شود.
برای خاموش‌کردن: **«توقف سایت.bat»** — برای بکاپ دستی: **«بکاپ دیتابیس.bat»**

اگر خواستید دستی اجرا کنید، در PowerShell داخل همین پوشه:

```
C:\Users\ARMIN7270\AppData\Local\php\php.exe artisan serve
```

**اطلاعات ورود مدیر (پیش‌فرض):**

| مورد | مقدار |
|---|---|
| آدرس ورود | `http://127.0.0.1:8000/login` |
| نام کاربری | `admin` |
| رمز عبور | `admin` |
| پنل مدیریت | `http://127.0.0.1:8000/admin` |

ورود با نام کاربری `admin` یا شماره موبایل `09120000000` امکان‌پذیر است.

> ⚠️ بعد از راه‌اندازی واقعی، رمز عبور را حتماً عوض کنید (در فایل `.env` مقادیر `ADMIN_PHONE` و `ADMIN_PASSWORD` را عوض و دیتابیس را دوباره seed کنید).

> 💡 **بکاپ خودکار:** یک تسک ویندوز به نام `TipStopDailyBackup` ثبت شده که هر روز ساعت ۳ صبح از دیتابیس بکاپ می‌گیرد و در پوشه `backups` ذخیره می‌کند.

> 🚀 **دیپلوی روی Railway + مهاجرت به اکانت جدید:** راهنمای کامل قدم‌به‌قدم در [`DEPLOY_RAILWAY.md`](DEPLOY_RAILWAY.md) — راهنمای سایر هاست‌ها در [`DEPLOY.md`](DEPLOY.md).

---

## ۲) راه‌اندازی فروش — قدم به قدم

1. **ورود به پنل مدیریت** → بخش «🖥️ سرورها و اینباندها»
2. **افزودن سرور**: اطلاعات API پنل 3x-ui را وارد کنید:
   - اگر سایت و پنل روی **یک سرور** باشند: آدرس API = `127.0.0.1` (localhost) — سریع‌ترین و امن‌ترین حالت
   - «مسیر مخفی پنل» را همان Secret Path پنل 3x-ui وارد کنید (بدون `/`)
   - «دامنه/IP عمومی کانفیگ‌ها»: همان آدرسی که مشتری‌ها به آن وصل می‌شوند (IP سرور ایران یا دامنه شما) — خالی بگذارید تا از آدرس API استفاده شود
3. دکمه **«تست اتصال»** را بزنید — باید تعداد اینباند‌ها را نشان دهد ✅
4. دکمه **«دریافت از پنل»** را بزنید — همه اینباند‌ها (VLESS/VMess/Trojan و...) خودکار وارد سایت می‌شوند
5. اگر پورت تانل با پورت پنل فرق دارد، **«پورت عمومی»** همان اینباند را در جدول اصلاح و «ذخیره» کنید
6. بخش «🏷️ پلن‌ها» → پلن بسازید (قیمت، حجم، اعتبار) و **اینباند‌های دلخواه را به پلن وصل کنید** — روی هر اینباند یک کلاینت ساخته می‌شود
7. بخش «⚙️ تنظیمات» → **شماره کارت‌های کارت به کارت** را وارد کنید + آیدی تلگرام پشتیبانی
8. تمام! سایت آماده فروش است.

**فلوی مشتری:**
```
ثبت‌نام → انتخاب پلن → واریز کارت به کارت → ثبت کد پیگیری → [شما تأیید می‌کنید] → ساخت خودکار کانفیگ → دریافت لینک اشتراک + QR در داشبورد
```

پیام‌های پرداخت جدید در «💰 پرداخت‌ها» پنل مدیریت می‌آیند. با «تایید و ساخت کانفیگ» اشتراک فوراً فعال می‌شود. تمدید هم خودکار است: حجم باقی‌مانده و روزهای باقی‌مانده قبلی حفظ و به خرید جدید اضافه می‌شود.


## ۳) انتقال سایت به سرور (پیشنهاد: همان سرور ایران تانل)

> چرا سرور ایران؟ چون پرداخت کارت به کارت است و مشتری‌ها داخل ایران هستند؛ ضمناً سایت به پنل 3x-ui از طریق localhost وصل می‌شود — سریع، امن و بدون نیاز به باز کردن پورت پنل روی اینترنت.

### قدم ۱ — آماده‌سازی سرور
روی سرور ایران (Ubuntu/Debian):

```bash
apt update && apt install -y php8.3-fpm php8.3-cli php8.3-sqlite3 php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip php8.3-gd php8.3-intl unzip nginx composer
```

### قدم ۲ — آپلود پروژه
با **WinSCP** کل پوشه پروژه را در `/var/www/tipstop` کپی کنید (پوشه‌های `node_modules` و `public/build` لازم نیستند). سپس:

```bash
cd /var/www/tipstop
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
```

### قدم ۳ — فایل `.env` سرور
```
APP_NAME="TipStop Network"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.ir
ADMIN_PHONE=09xxxxxxxxx
ADMIN_PASSWORD=YourStrongPassword
```

سپس دیتابیس و کش:
```bash
php artisan migrate:fresh --seed --force
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache
```

> نکته: اگر قبلاً روی کامپیوتر خودتان سرور/پلن/تنظیمات ساخته‌اید، به‌جای `migrate:fresh --seed` فقط `php artisan migrate --force` بزنید و فایل `database/database.sqlite` را از سیستم خودتان همراه پروژه کپی کنید.

### قدم ۴ — کانفیگ Nginx
فایل `/etc/nginx/sites-available/tipstop`:

```nginx
server {
    listen 80;
    server_name your-domain.ir;
    root /var/www/tipstop/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
ln -s /etc/nginx/sites-available/tipstop /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### قدم ۵ — HTTPS (اجباری)
```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d your-domain.ir
```

### قدم ۶ — کرون (انقضای خودکار و همگام‌سازی ترافیک)
```bash
crontab -e
```
این خط را اضافه کنید:
```
* * * * * cd /var/www/tipstop && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

### قدم ۷ — تنظیمات نهایی در پنل ادمین سایت
- «⚙️ تنظیمات» → «آدرس پایه لینک اشتراک» = `https://your-domain.ir`
- بخش سرورها → آدرس API = `127.0.0.1` + پورت و مسیر پنل 3x-ui + تست اتصال + دریافت از پنل

---

## ۴) امنیت و بکاپ

- **پنل 3x-ui هرگز نباید روی دامنه اصلی سایت باشد** — پورت عوض‌شده + مسیر مخفی + در صورت امکان محدودیت IP نگه دارید.
- رمز ادمین سایت را عوض کنید و در `.env` مقادیر `ADMIN_PHONE` و `ADMIN_PASSWORD` را تنظیم کنید.
- **بکاپ روزانه** دیتابیس فقط یک فایل است: `database/database.sqlite` (کرون نمونه روی سرور):
  ```
  0 3 * * * cp /var/www/tipstop/database/database.sqlite /root/backups/db-$(date +\%F).sqlite
  ```
- خطاهای سایت در `storage/logs/laravel.log` ثبت می‌شود.
- برای به‌روزرسانی فایل‌ها بعد از تغییرات: `php artisan optimize:clear`

---

## ۵) سوالات رایج

**لینک اشتراک مشتری‌ها چیست؟**
`https://دامنه-شما/sub/{کد اختصاصی هر کاربر}` — کاربر در داشبوردش آن را می‌بیند و در V2rayNG / Hiddify / Streisand ایمپورت می‌کند. این لینک همیشه ثابت است و کانفیگ‌های همه خریدهای کاربر را شامل می‌شود.

**اگر پنل موقتاً در دسترس نباشد چه می‌شود؟**
تأیید پرداخت خطا می‌دهد و سفارش در صف می‌ماند؛ بعد از رفع مشکل دوباره «تایید» را بزنید. سرویس کاربران فعال قطع نمی‌شود.

**چطور پلن را نامحدود تعریف کنم؟**
در ساخت پلن، حجم را صفر بگذارید.

---

## ۶) خلاصه تکنولوژی‌ها

- Laravel 13 + PHP 8.3 + SQLite
- Tailwind CSS 4 + فونت وزیرمتن (RTL کامل)
- chillerlan/php-qrcode (QR اشتراک) • morilog/jalali (تاریخ شمسی) • guzzle (اتصال به API پنل)
- احراز هویت شماره موبایل + رمز، محدودیت تلاش ورود، CSRF، رمزنگاری رمز پنل در دیتابیس
