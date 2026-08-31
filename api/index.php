<?php

/**
 * Vercel PHP serverless entrypoint — پل اتصال به Laravel
 * در محیط serverless فقط پوشه /tmp قابل نوشتن است؛
 * مسیر storage از طریق متغیر APP_STORAGE=/tmp/storage در Vercel تنظیم می‌شود.
 */

// ساخت ساختار پوشه‌های storage در /tmp قبل از بوت Laravel
if (($storage = getenv('APP_STORAGE')) !== false && $storage !== '' && ! is_dir($storage.'/framework/views')) {
    foreach (['framework/views', 'framework/sessions', 'framework/cache/data', 'app', 'logs'] as $dir) {
        @mkdir($storage.'/'.$dir, 0755, true);
    }
}

require_once __DIR__.'/../public/index.php';
