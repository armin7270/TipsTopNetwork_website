<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deploy_endpoints_are_hidden_without_key_configured(): void
    {
        // DEPLOY_KEY در محیط تست ست نیست → 404
        $this->get('/deploy/migrate?key=anything')->assertNotFound();
        $this->get('/deploy/cron?key=anything')->assertNotFound();
    }

    public function test_deploy_endpoints_reject_wrong_key(): void
    {
        putenv('DEPLOY_KEY=test-secret-key');

        try {
            $this->get('/deploy/migrate?key=wrong')->assertForbidden();
            $this->get('/deploy/cron?key=wrong')->assertForbidden();
        } finally {
            putenv('DEPLOY_KEY');
        }
    }

    public function test_deploy_migrate_runs_with_valid_key(): void
    {
        putenv('DEPLOY_KEY=test-secret-key');

        try {
            $response = $this->get('/deploy/migrate?key=test-secret-key');
            $response->assertOk();
            $response->assertJsonPath('ok', true);

            // سیدر باید ادمین ساخته باشد
            $this->assertDatabaseHas('users', ['username' => 'admin']);
        } finally {
            putenv('DEPLOY_KEY');
        }

        // پاک‌سازی لینک storage که ممکن است ساخته شده باشد
        $link = public_path('storage');

        if (is_link($link) || is_file($link)) {
            @unlink($link);
        }
    }

    public function test_deploy_cron_runs_with_valid_key(): void
    {
        putenv('DEPLOY_KEY=test-secret-key');

        try {
            $response = $this->get('/deploy/cron?key=test-secret-key');
            $response->assertOk();
            $response->assertJsonPath('ok', true);
        } finally {
            putenv('DEPLOY_KEY');
        }
    }
}
