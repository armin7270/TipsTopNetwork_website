#!/usr/bin/env bash
# اسکریپت بوت TipStop Network برای Railway (Procfile فقط همین را صدا می‌زند)
set -u

cd "$(dirname "$0")/.." || exit 1

echo "== TipStop boot =="

# کش کانفیگ زمان بیلد را پاک کن تا متغیرهای runtime اعمال شوند
php artisan config:clear || true

# لینک storage (idempotent)
if [ ! -e public/storage ]; then
    php artisan storage:link || true
fi

# بازیابی state مهاجرت (اگر فایل state وجود داشته باشد؛ در غیر این صورت بدون اثر)
php artisan app:restore-state deploy-state/state.bin || true

# اطمینان از وجود فایل SQLite
mkdir -p database
touch database/database.sqlite || true

# اگر pgsql انتخاب شده ولی هیچ Postgres متصل نیست، برای این بوت به SQLite برگرد
if [ "${DB_CONNECTION:-}" = "pgsql" ] && [ -z "${DB_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
    echo "!! WARNING: DB_CONNECTION=pgsql but DB_URL and DB_HOST are empty (no Postgres attached). Falling back to SQLite for this boot."
    export DB_CONNECTION=sqlite
fi

# ترمیم دیتابیس ناقص (migrations ثبت شده ولی جداول اصلی غایب)
php artisan app:repair-db || true

php artisan migrate --force || exit 1
php artisan db:seed --force || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "== starting server on port ${PORT:-8080} =="
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
