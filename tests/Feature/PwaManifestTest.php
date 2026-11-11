<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_json_file_exists_and_is_valid(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $content = file_get_contents($manifestPath);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertSame('Stampippi - Webスタンプラリー', $data['name']);
        $this->assertSame('Stampippi', $data['short_name']);
        $this->assertSame('/', $data['start_url']);
        $this->assertSame('standalone', $data['display']);
        $this->assertSame('#4f46e5', $data['theme_color']);
        $this->assertSame('#f8fafc', $data['background_color']);
        $this->assertNotEmpty($data['icons']);

        foreach ($data['icons'] as $icon) {
            $this->assertArrayHasKey('src', $icon);
            $this->assertArrayHasKey('sizes', $icon);
            $this->assertArrayHasKey('type', $icon);

            // Icon files must physically exist in public directory
            $iconPath = public_path(ltrim($icon['src'], '/'));
            $this->assertFileExists($iconPath);
        }
    }

    public function test_web_app_head_contains_manifest_and_pwa_meta_tags(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('<link rel="manifest" href="' . asset('manifest.json') . '"', false)
            ->assertSee('<meta name="theme-color" content="#4f46e5">', false)
            ->assertSee('<meta name="mobile-web-app-capable" content="yes">', false)
            ->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false)
            ->assertSee('<meta name="apple-mobile-web-app-title" content="Stampippi">', false);
    }

    public function test_service_worker_file_exists_and_layout_registers_it(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $swContent = file_get_contents($swPath);
        $this->assertStringContainsString("CACHE = 'stampippi-static-v1'", $swContent);
        $this->assertStringContainsString("STATIC_PATHS", $swContent);

        $response = $this->get('/');
        $response->assertStatus(200)
            ->assertSee("navigator.serviceWorker.register('/sw.js')", false);
    }
}
