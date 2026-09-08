<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpsSchemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_assets_are_http_by_default_locally(): void
    {
        // در محیط لوکال (بدون RAILWAY_ENVIRONMENT و FORCE_HTTPS) اسکیم http باقی می‌ماند
        $response = $this->get('/');

        $response->assertOk();

        $this->assertStringNotContainsString('https://localhost/build/', $response->getContent());
    }

    public function test_assets_force_https_when_enabled(): void
    {
        config(['app.env' => 'production']);
        putenv('FORCE_HTTPS=1');

        try {
            $response = $this->get('/');
            $response->assertOk();

            // تمام ارجاع‌های build باید https باشند
            preg_match_all('/(?:src|href)="([^"]*\/build\/[^"]+)"/', $response->getContent(), $matches);

            $this->assertNotEmpty($matches[1], 'Vite assets must be present on the homepage');

            foreach ($matches[1] as $url) {
                $this->assertStringStartsWith('https://', $url, "Asset URL must be https: $url");
            }
        } finally {
            putenv('FORCE_HTTPS');
        }
    }
}
