<?php

namespace Tests\Feature;

use App\Support\StateCrypto;
use Tests\TestCase;

/**
 * این تست‌ها به دیتابیس اپ نیاز ندارند (دیتابیس‌های موقت جداگانه می‌سازند)
 * — از RefreshDatabase استفاده نمی‌کنیم تا با تغییر کانکشن sqlite در دستور restore تداخل نکند.
 */
class StateSyncTest extends TestCase
{
    public function test_state_crypto_roundtrip(): void
    {
        $key = 'test-key-123';
        $plaintext = 'some-sensitive-data-'.random_bytes(16);

        $encrypted = StateCrypto::encrypt($plaintext, $key);

        $this->assertStringStartsWith(StateCrypto::MAGIC, $encrypted);
        $this->assertNotSame($plaintext, $encrypted);

        $this->assertSame($plaintext, StateCrypto::decrypt($encrypted, $key));
    }

    public function test_state_crypto_rejects_wrong_key(): void
    {
        $encrypted = StateCrypto::encrypt('secret', 'key-one');

        $this->expectException(\RuntimeException::class);
        StateCrypto::decrypt($encrypted, 'key-two');
    }

    public function test_state_endpoint_is_hidden_without_state_key(): void
    {
        putenv('DEPLOY_KEY=test-secret-key');
        putenv('STATE_KEY');

        try {
            $this->get('/deploy/state?key=test-secret-key')->assertNotFound();
        } finally {
            putenv('DEPLOY_KEY');
        }
    }

    public function test_state_endpoint_requires_deploy_key(): void
    {
        putenv('DEPLOY_KEY=test-secret-key');
        putenv('STATE_KEY=state-secret-key');

        try {
            $this->get('/deploy/state?key=wrong')->assertForbidden();
        } finally {
            putenv('DEPLOY_KEY');
            putenv('STATE_KEY');
        }
    }

    public function test_state_endpoint_returns_encrypted_zip_with_database(): void
    {
        putenv('DEPLOY_KEY=test-secret-key');
        putenv('STATE_KEY=state-secret-key');

        // در محیط تست DB درون‌حافظه‌ای است — فایل دیتابیس واقعی موقت می‌سازیم تا داخل zip برود
        $tmpDb = tempnam(sys_get_temp_dir(), 'tsdbtest').'.sqlite';

        try {
            $pdo = new \PDO('sqlite:'.$tmpDb);
            $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY)');

            config(['database.connections.sqlite.database' => $tmpDb]);

            $response = $this->get('/deploy/state?key=test-secret-key');

            $response->assertOk();
            $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));

            $body = $response->getContent();
            $this->assertStringStartsWith(StateCrypto::MAGIC, $body);

            $plain = StateCrypto::decrypt($body, 'state-secret-key');

            // ZIP signature (PK\x03\x04)
            $this->assertStringStartsWith("PK\x03\x04", $plain);

            $zip = new \ZipArchive;
            $tmp = tempnam(sys_get_temp_dir(), 'tszip').'.zip';
            file_put_contents($tmp, $plain);

            $this->assertTrue($zip->open($tmp) === true);
            $this->assertNotFalse($zip->locateName('database.sqlite'));

            $zip->close();
            @unlink($tmp);
        } finally {
            putenv('DEPLOY_KEY');
            putenv('STATE_KEY');
            @unlink($tmpDb);
        }
    }

    public function test_restore_state_restores_database_and_storage(): void
    {
        // ساخت فایل state معتبر با یک دیتابیس واقعی (جدول users + یک کاربر) و فایل نمونه
        $key = 'restore-test-key';
        $tmpDb = tempnam(sys_get_temp_dir(), 'tsrdb').'.sqlite';

        $pdo = new \PDO('sqlite:'.$tmpDb);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO users (name) VALUES ('seeded-user')");

        $zipPath = tempnam(sys_get_temp_dir(), 'tsrzip').'.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($tmpDb, 'database.sqlite');
        $zip->addFromString('storage/receipts/test-receipt.txt', 'hello-receipt');
        $zip->close();

        $statePath = tempnam(sys_get_temp_dir(), 'tsrstate').'.bin';
        file_put_contents($statePath, StateCrypto::encrypt((string) file_get_contents($zipPath), $key));

        putenv('STATE_KEY='.$key);
        $target = null;

        try {
            // دیتابیس هدف: یک فایل sqlite واقعی موقت
            $target = tempnam(sys_get_temp_dir(), 'tsrtarget').'.sqlite';
            config(['database.connections.sqlite.database' => $target]);

            $this->artisan('app:restore-state', ['file' => $statePath, '--force' => true])
                ->expectsOutputToContain('بازیابی state کامل شد')
                ->assertSuccessful();

            $this->assertFileExists($target);
            $this->assertFileExists(storage_path('app/receipts/test-receipt.txt'));

            // دیتابیس واردشده همان کاربر seed را دارد
            $pdoTarget = new \PDO('sqlite:'.$target);
            $this->assertSame('1', (string) $pdoTarget->query('SELECT COUNT(*) FROM users')->fetchColumn());
            $this->assertSame('seeded-user', (string) $pdoTarget->query('SELECT name FROM users LIMIT 1')->fetchColumn());
        } finally {
            putenv('STATE_KEY');
            @unlink($tmpDb);
            @unlink($zipPath);
            @unlink($statePath);
            @unlink($target ?? '');
            @unlink(storage_path('app/receipts/test-receipt.txt'));
            @rmdir(storage_path('app/receipts'));
        }
    }

    public function test_restore_state_refuses_to_overwrite_live_database_without_force(): void
    {
        // دیتابیس هدف حاوی کاربر (زنده) است — بدون --force نباید جایگزین شود
        $key = 'restore-test-key';
        $tmpDb = tempnam(sys_get_temp_dir(), 'tsrdb').'.sqlite';

        $pdo = new \PDO('sqlite:'.$tmpDb);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO users (name) VALUES ('from-state')");

        $zipPath = tempnam(sys_get_temp_dir(), 'tsrzip').'.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($tmpDb, 'database.sqlite');
        $zip->close();

        $statePath = tempnam(sys_get_temp_dir(), 'tsrstate').'.bin';
        file_put_contents($statePath, StateCrypto::encrypt((string) file_get_contents($zipPath), $key));

        putenv('STATE_KEY='.$key);
        $target = null;

        try {
            // دیتابیس هدف «زنده»: یک جدول users با یک ردیف
            $target = tempnam(sys_get_temp_dir(), 'tsrtarget').'.sqlite';
            $pdoTarget = new \PDO('sqlite:'.$target);
            $pdoTarget->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
            $pdoTarget->exec("INSERT INTO users (name) VALUES ('live-user')");

            config(['database.connections.sqlite.database' => $target]);

            $this->artisan('app:restore-state', ['file' => $statePath])
                ->expectsOutputToContain('داده‌های زنده محافظت می‌شوند')
                ->assertSuccessful();

            // دیتابیس زنده دست‌نخورده ماند
            $this->assertSame('live-user', (string) (new \PDO('sqlite:'.$target))->query('SELECT name FROM users LIMIT 1')->fetchColumn());
        } finally {
            putenv('STATE_KEY');
            @unlink($tmpDb);
            @unlink($zipPath);
            @unlink($statePath);
            @unlink($target ?? '');
        }
    }
}
