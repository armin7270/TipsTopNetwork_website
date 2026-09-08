<?php

namespace App\Support;

/**
 * رمزنگاری فایل state مهاجرت (دیتابیس + فایل‌ها)
 *
 * فرمت: TSSTATE1 (8 بایت) + salt (16) + iv (16) + ciphertext (AES-256-CBC)
 * کلید با PBKDF2-SHA256 (100,000 تکرار) از STATE_KEY مشتق می‌شود.
 * سرور هنگام دانلود رمز می‌کند — پس STATE_KEY فقط در خود Railway لازم است، نه در GitHub.
 */
class StateCrypto
{
    public const MAGIC = 'TSSTATE1';

    public static function encrypt(string $plaintext, string $key): string
    {
        $salt = random_bytes(16);
        $iv = random_bytes(16);
        $derived = self::deriveKey($key, $salt);

        $cipher = openssl_encrypt($plaintext, 'aes-256-cbc', $derived, OPENSSL_RAW_DATA, $iv);

        if ($cipher === false) {
            throw new \RuntimeException('رمزنگاری state ناموفق بود.');
        }

        return self::MAGIC.$salt.$iv.$cipher;
    }

    public static function decrypt(string $blob, string $key): string
    {
        if (! str_starts_with($blob, self::MAGIC)) {
            throw new \RuntimeException('فایل state معتبر نیست (امضای TSSTATE1 یافت نشد).');
        }

        $salt = substr($blob, 8, 16);
        $iv = substr($blob, 24, 16);
        $cipher = substr($blob, 40);

        if (strlen($salt) !== 16 || strlen($iv) !== 16 || $cipher === '') {
            throw new \RuntimeException('فایل state ناقص است.');
        }

        $derived = self::deriveKey($key, $salt);
        $plaintext = openssl_decrypt($cipher, 'aes-256-cbc', $derived, OPENSSL_RAW_DATA, $iv);

        if ($plaintext === false) {
            throw new \RuntimeException('رمزگشایی state ناموفق بود — احتمالاً STATE_KEY اشتباه است.');
        }

        return $plaintext;
    }

    protected static function deriveKey(string $key, string $salt): string
    {
        return hash_pbkdf2('sha256', $key, $salt, 100000, 32, true);
    }
}
