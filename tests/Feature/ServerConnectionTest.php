<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use App\Services\Xui\XuiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServerConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function seedSuperAdmin(): User
    {
        return User::create([
            'name' => 'مدیرکل',
            'phone' => '09120000000',
            'password' => 'Admin@1234',
            'password_changed_at' => now(),
            'status' => 'active',
            'is_admin' => true,
            'admin_role' => 'super',
        ]);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'پنل تست',
            'api_scheme' => 'http',
            'api_host' => '127.0.0.1',
            'api_port' => 2053,
            'api_path' => 'secret',
            'username' => 'admin',
            'password' => 'panel-pass',
            'public_host' => '',
            'is_active' => '1',
        ], $overrides);
    }

    protected function mockXui(bool $ok, string $message, int $times = 1): void
    {
        // نکته: app(XuiService::class, [...params...]) از binding نمونه (instance) عبور
        // می‌کند؛ برای همین با bind() بستن می‌شود که پارامترها را می‌پذیرد
        $this->app->bind(XuiService::class, function () use ($ok, $message, $times) {
            $mock = \Mockery::mock(XuiService::class);

            $mock->shouldReceive('testConnection')->times($times)
                ->andReturn(['ok' => $ok, 'message' => $message]);

            return $mock;
        });
    }

    public function test_store_server_is_blocked_when_connection_test_fails(): void
    {
        $admin = $this->seedSuperAdmin();

        $this->mockXui(false, 'اتصال رد شد (cURL 7)');

        $this->actingAs($admin)
            ->post(route('admin.servers.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('servers', ['name' => 'پنل تست']);
    }

    public function test_store_server_saves_only_after_successful_connection(): void
    {
        $admin = $this->seedSuperAdmin();

        $this->mockXui(true, 'اتصال موفق بود. تعداد اینباندها: 3');

        $this->actingAs($admin)
            ->post(route('admin.servers.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('servers', [
            'name' => 'پنل تست',
            'api_host' => '127.0.0.1',
            'is_active' => true,
        ]);
    }

    public function test_update_server_is_blocked_when_connection_test_fails(): void
    {
        $admin = $this->seedSuperAdmin();

        $server = Server::create([
            'name' => 'پنل قبلی',
            'api_scheme' => 'http',
            'api_host' => '10.0.0.1',
            'api_port' => 2053,
            'username' => 'u',
            'password' => 'p',
            'is_active' => true,
        ]);

        $this->mockXui(false, 'خطا');

        $this->actingAs($admin)
            ->put(route('admin.servers.update', $server), $this->validPayload(['name' => 'نام جدید']))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('پنل قبلی', $server->fresh()->name);
    }

    public function test_update_server_saves_after_successful_connection(): void
    {
        $admin = $this->seedSuperAdmin();

        $server = Server::create([
            'name' => 'پنل قبلی',
            'api_scheme' => 'http',
            'api_host' => '10.0.0.1',
            'api_port' => 2053,
            'username' => 'u',
            'password' => 'p',
            'is_active' => true,
        ]);

        $this->mockXui(true, 'اتصال موفق بود. تعداد اینباندها: 5');

        $this->actingAs($admin)
            ->put(route('admin.servers.update', $server), $this->validPayload(['name' => 'نام جدید']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('نام جدید', $server->fresh()->name);
    }

    public function test_unsaved_connection_endpoint_reports_success(): void
    {
        $admin = $this->seedSuperAdmin();

        $this->mockXui(true, 'اتصال موفق بود. تعداد اینباندها: 3');

        $this->actingAs($admin)
            ->postJson(route('admin.servers.test-connection'), $this->validPayload())
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_unsaved_connection_endpoint_requires_password_for_new_server(): void
    {
        $admin = $this->seedSuperAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.servers.test-connection'), $this->validPayload(['password' => '']))
            ->assertOk()
            ->assertJsonPath('ok', false);
    }

    public function test_ssl_verify_column_is_gone(): void
    {
        $this->assertFalse(
            Schema::hasColumn('servers', 'ssl_verify')
        );
    }
}
