<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseTransferService;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigrationBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function seedAdmin(): User
    {
        return User::create([
            'name' => 'مدیر',
            'phone' => '09120000000',
            'password' => 'Admin@1234',
            'password_changed_at' => now(),
            'status' => 'active',
            'is_admin' => true,
            'admin_role' => 'super',
        ]);
    }

    public function test_migration_page_renders_for_super_admin(): void
    {
        $this->actingAs($this->seedAdmin())->get('/admin/migration')->assertOk();
    }

    public function test_finance_admin_cannot_access_migration(): void
    {
        $finance = $this->seedAdmin();
        $finance->update(['phone' => '09120000001', 'admin_role' => 'finance']);

        $this->actingAs($finance)->get('/admin/migration')->assertForbidden();
    }

    public function test_db_export_command_lists_driver(): void
    {
        // روی sqlite لوکالِ تست (:memory:) باید با خطای کنترل‌شده خارج شود، نه اکسپشن
        $this->artisan('db:export')->assertFailed();
    }

    public function test_transfer_service_reports_sqlite_driver(): void
    {
        $service = app(DatabaseTransferService::class);

        $this->assertSame('sqlite', $service->driver());
    }

    public function test_telegram_backup_noops_when_bot_not_configured(): void
    {
        // بدون توکن/چت‌آیدی باید graceful خارج شود
        $this->artisan('db:backup-telegram')->assertFailed();
    }

    public function test_send_local_document_rejects_missing_file(): void
    {
        $this->expectException(TelegramException::class);

        app(TelegramClient::class)->sendLocalDocument('123', '/nope/missing.zip');
    }
}
