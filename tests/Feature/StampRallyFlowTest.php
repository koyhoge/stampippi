<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\RallySeries;
use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StampRallyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_public_pages_render_correctly(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Stampippi')
            ->assertSee('ログイン');

        $this->get('/groups/phpcon')
            ->assertStatus(200)
            ->assertSee('PHPカンファレンススタンプラリー運営');

        $this->get('/series/phpcon-stamp-rally')
            ->assertStatus(200)
            ->assertSee('日本全国PHPカンファレンススタンプラリー 2026')
            ->assertSee('日本全国PHPカンファレンススタンプラリー 2025');

        $this->get('/rallies/phpcon-japan-2026')
            ->assertStatus(200)
            ->assertSee('大田区産業プラザPiO')
            ->assertSee('グランフロント大阪');
    }

    public function test_admin_and_group_member_and_general_user_permissions_with_gps_checkin(): void
    {
        $admin = User::create([
            'handle' => 'koyhoge',
            'name' => 'Admin User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $coStaff = User::create([
            'handle' => 'staff_member',
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => 'password',
            'is_admin' => false,
        ]);

        $player = User::create([
            'handle' => 'rally_player',
            'name' => 'Player User',
            'email' => 'player@example.com',
            'password' => 'password',
            'is_admin' => false,
        ]);

        // General user can view participant dashboard (/dashboard) and search active rallies, but cannot access management (/my) or create rallies
        $this->actingAs($player)->get('/dashboard')->assertStatus(200);
        $this->actingAs($player)->get('/dashboard?q=PHP')->assertStatus(200);
        $this->actingAs($player)->get('/my')->assertStatus(403);
        $this->actingAs($player)->get('/my/groups')->assertStatus(403);
        $this->actingAs($player)->get('/my/rallies/create')->assertStatus(403);
        $this->actingAs($player)->post('/my/rallies', [
            'title' => 'Unauthorized Rally',
            'slug' => 'unauthorized-rally',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ])->assertStatus(403);

        // 1. Admin creates a Group
        $this->actingAs($admin)
            ->post('/my/groups', [
                'slug' => 'phpcon',
                'name' => 'PHPカンファレンス運営',
                'description' => '全国PHPカンファレンス運営グループ',
            ])
            ->assertRedirect();

        $group = Group::where('slug', 'phpcon')->firstOrFail();

        // 2. Admin adds registered user by @handle as group member
        $this->actingAs($admin)
            ->post("/my/groups/{$group->slug}/members", [
                'identifier' => '@staff_member',
                'role' => 'member',
            ])
            ->assertRedirect();

        $this->assertTrue($coStaff->canManageGroup($group));

        // 3. Group member (non-admin) cannot create a new RallySeries
        $this->actingAs($coStaff)
            ->post('/my/series', [
                'group_id' => $group->id,
                'title' => 'Unauthorized Series',
                'slug' => 'unauthorized-series',
                'visibility' => 'public',
            ])
            ->assertStatus(403);

        // 4. Admin creates a RallySeries under the Group
        $this->actingAs($admin)
            ->post('/my/series', [
                'group_id' => $group->id,
                'title' => 'PHPカンファレンススタンプラリー',
                'slug' => 'phpcon-series',
                'description' => '各年版を含むシリーズ',
                'visibility' => 'public',
            ])
            ->assertRedirect();

        $series = RallySeries::where('slug', 'phpcon-series')->firstOrFail();

        // 5. Group member (and Admin) CAN access /my and /my/groups management pages and create a StampRally belonging to that Series
        $this->actingAs($coStaff)->get('/my')->assertStatus(200);
        $this->actingAs($coStaff)->get('/my/groups')->assertStatus(200);

        // Non-admin group member attempting to create a standalone rally without a series gets a Japanese validation error
        $this->actingAs($coStaff)
            ->from('/my/rallies/create')
            ->post('/my/rallies', [
                'group_id' => $group->id,
                'rally_series_id' => null,
                'title' => 'Standalone Rally',
                'slug' => 'standalone-rally',
                'visibility' => 'public',
                'completion_condition' => 'all',
            ])
            ->assertRedirect('/my/rallies/create')
            ->assertSessionHasErrors([
                'rally_series_id' => '所属するラリーシリーズを選択してください（グループメンバーはシリーズに属するスタンプラリーのみ作成できます）。',
            ]);

        $this->actingAs($coStaff)
            ->post('/my/rallies', [
                'group_id' => $group->id,
                'rally_series_id' => $series->id,
                'title' => '日本全国PHPカンファレンススタンプラリー 2026',
                'slug' => 'phpcon-2026',
                'visibility' => 'public',
                'completion_condition' => 'all',
                'starts_at' => null,
                'ends_at' => null,
            ])
            ->assertRedirect();

        $rally = StampRally::where('slug', 'phpcon-2026')->firstOrFail();

        // 6. Add a StampLocation (venue) with no period restriction
        $this->actingAs($coStaff)
            ->post("/my/rallies/{$rally->slug}/locations", [
                'name' => '大田区産業プラザPiO',
                'description' => '東京会場',
                'latitude' => 35.5588810,
                'longitude' => 139.7240960,
                'radius_meters' => 150,
                'available_from' => null,
                'available_until' => null,
                'stamp_icon_value' => 'elephant',
                'stamp_color' => '#4F46E5',
            ])
            ->assertRedirect();

        $location = StampLocation::where('stamp_rally_id', $rally->id)->firstOrFail();

        // 6b. Update location with an uploaded custom stamp image
        Storage::fake('public');
        $fakeStampSvg = UploadedFile::fake()->createWithContent(
            'custom_stamp.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="28" fill="#4F46E5"/></svg>'
        );

        $this->actingAs($coStaff)
            ->put("/my/rallies/{$rally->slug}/locations/{$location->id}", [
                'name' => '大田区産業プラザPiO',
                'description' => '東京会場',
                'latitude' => 35.5588810,
                'longitude' => 139.7240960,
                'radius_meters' => 150,
                'stamp_icon_value' => 'elephant',
                'stamp_icon_image' => $fakeStampSvg,
                'stamp_color' => '#4F46E5',
            ])
            ->assertRedirect();

        $location->refresh();
        $this->assertSame('custom', $location->stamp_icon_type);
        $this->assertTrue($location->hasCustomImage());
        Storage::disk('public')->assertExists($location->stamp_icon_value);

        // 6c. Switching service-wide stamp storage driver to 'database' (serverless inline Data URI plugin)
        config(['stamp.image_driver' => 'database']);
        $fakeStampSvg2 = UploadedFile::fake()->createWithContent(
            'inline_stamp.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#10B981"/></svg>'
        );

        $this->actingAs($coStaff)
            ->put("/my/rallies/{$rally->slug}/locations/{$location->id}", [
                'name' => '大田区産業プラザPiO',
                'description' => '東京会場',
                'latitude' => 35.5588810,
                'longitude' => 139.7240960,
                'radius_meters' => 150,
                'stamp_icon_value' => '__custom__',
                'stamp_icon_image' => $fakeStampSvg2,
                'stamp_color' => '#4F46E5',
            ])
            ->assertRedirect();

        $location->refresh();
        $this->assertStringStartsWith('data:', $location->stamp_icon_value);
        $this->assertStringStartsWith('data:', $location->iconImageUrl());

        // 6d. Switching to 's3' (S3StampStorageDriver with custom CloudFront/CDN base URL)
        Storage::fake('s3');
        config([
            'stamp.image_driver' => 's3',
            'stamp.drivers.s3.public_base_url' => 'https://cdn.stampippi.example.com',
        ]);
        app(\App\Services\StampStorage\StampImageStorageManager::class)->extend('s3', function () {
            return new \App\Services\StampStorage\S3StampStorageDriver(
                disk: 's3',
                directory: 'stamps',
                publicBaseUrl: 'https://cdn.stampippi.example.com'
            );
        });

        $fakeS3Svg = UploadedFile::fake()->createWithContent(
            's3_stamp.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="24" fill="#F59E0B"/></svg>'
        );

        $this->actingAs($coStaff)
            ->put("/my/rallies/{$rally->slug}/locations/{$location->id}", [
                'name' => '大田区産業プラザPiO',
                'description' => '東京会場',
                'latitude' => 35.5588810,
                'longitude' => 139.7240960,
                'radius_meters' => 150,
                'stamp_icon_value' => '__custom__',
                'stamp_icon_image' => $fakeS3Svg,
                'stamp_color' => '#4F46E5',
            ])
            ->assertRedirect();

        $location->refresh();
        Storage::disk('s3')->assertExists($location->stamp_icon_value);
        $this->assertStringStartsWith('https://cdn.stampippi.example.com/stamps/', $location->iconImageUrl());

        // 6e. Switching to 'laravel_cloud' (LaravelCloudObjectStorageDriver via LARAVEL_CLOUD_DISK_CONFIG)
        $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode([
            [
                'disk' => 'cloud-stamps-bucket',
                'access_key_id' => 'test-key',
                'access_key_secret' => 'test-secret',
                'bucket' => 'stampippi-cloud',
                'url' => 'https://fls-example.laravel.cloud',
                'endpoint' => 'https://example.r2.cloudflarestorage.com',
                'region' => 'auto',
                'is_default' => true,
            ],
        ]);
        Storage::fake('cloud-stamps-bucket');
        config(['stamp.image_driver' => 'laravel_cloud']);

        $cloudDriver = app(\App\Services\StampStorage\StampImageStorageManager::class)->driver('laravel_cloud');
        $this->assertSame('cloud-stamps-bucket', $cloudDriver->resolveDiskName());

        $fakeCloudSvg = UploadedFile::fake()->createWithContent(
            'cloud_stamp.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="24" fill="#EC4899"/></svg>'
        );

        $this->actingAs($coStaff)
            ->put("/my/rallies/{$rally->slug}/locations/{$location->id}", [
                'name' => '大田区産業プラザPiO',
                'description' => '東京会場',
                'latitude' => 35.5588810,
                'longitude' => 139.7240960,
                'radius_meters' => 150,
                'stamp_icon_value' => '__custom__',
                'stamp_icon_image' => $fakeCloudSvg,
                'stamp_color' => '#4F46E5',
            ])
            ->assertRedirect();

        $location->refresh();
        Storage::disk('cloud-stamps-bucket')->assertExists($location->stamp_icon_value);
        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']);

        // 7. Player attempts check-in from too far away (Tokyo Station ~14km away) -> Fails
        $farResponse = $this->actingAs($player)
            ->postJson("/rallies/{$rally->slug}/locations/{$location->id}/checkin", [
                'latitude' => 35.6812360,
                'longitude' => 139.7671250,
            ]);

        $farResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'too_far',
            ]);

        // 8. Player checks in within 20 meters of the venue -> Succeeds and completes rally!
        $nearResponse = $this->actingAs($player)
            ->postJson("/rallies/{$rally->slug}/locations/{$location->id}/checkin", [
                'latitude' => 35.5589000,
                'longitude' => 139.7241000,
            ]);

        $nearResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'stamped',
                'is_rally_completed' => true,
            ]);

        // 9. Player can view the completion certificate and the all-stamps footprint map (/dashboard/map)
        $this->actingAs($player)
            ->get("/rallies/{$rally->slug}/certificate")
            ->assertStatus(200)
            ->assertSee('コンプリート達成証')
            ->assertSee('大田区産業プラザPiO');

        $this->actingAs($player)
            ->get('/dashboard/map')
            ->assertStatus(200)
            ->assertSee('獲得スタンプ全地点マップ')
            ->assertSee('大田区産業プラザPiO')
            ->assertSee('日本全国PHPカンファレンススタンプラリー 2026');

        // 9b. Player can archive the permanent rally from "参加中のスタンプラリー" and still view it via ?archived=1
        $this->actingAs($player)
            ->post("/rallies/{$rally->slug}/archive")
            ->assertRedirect();

        $this->assertNotNull(
            $player->participations()->where('stamp_rally_id', $rally->id)->first()->archived_at
        );

        $this->actingAs($player)
            ->get('/dashboard?archived=1')
            ->assertStatus(200)
            ->assertSee('アーカイブ済');

        // 10. Admin promotes general user to Admin -> user can now create Series & Rallies
        $this->actingAs($admin)
            ->post('/my/admins', ['identifier' => '@rally_player'])
            ->assertRedirect();

        $this->assertTrue($player->fresh()->isAdmin());
        $this->actingAs($player->fresh())
            ->get('/my/series/create')
            ->assertStatus(200);

        // 11. Disabling config('app.dev_tools') turns off dev login and GPS simulator
        config(['app.dev_tools' => false]);
        $this->actingAs($player)
            ->get("/rallies/{$rally->slug}")
            ->assertStatus(200)
            ->assertDontSee('開発用 GPSシミュレーター');

        auth()->logout();
        $this->post('/dev-login', ['user_id' => $player->id])->assertStatus(403);
    }

    public function test_location_period_window_is_enforced_when_specified(): void
    {
        $creator = User::create([
            'handle' => 'creator_user',
            'name' => 'Creator',
            'email' => 'creator@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $rally = StampRally::create([
            'creator_id' => $creator->id,
            'slug' => 'timed-rally',
            'title' => '期間テストラリー',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $expiredLocation = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => '過去開催ベニュー',
            'latitude' => 35.5588810,
            'longitude' => 139.7240960,
            'radius_meters' => 200,
            'available_from' => now()->subDays(10),
            'available_until' => now()->subDays(5),
            'stamp_icon_value' => 'star',
            'stamp_color' => '#4F46E5',
        ]);

        $this->actingAs($creator)
            ->postJson("/rallies/{$rally->slug}/locations/{$expiredLocation->id}/checkin", [
                'latitude' => 35.5588810,
                'longitude' => 139.7240960,
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status' => 'location_out_of_period',
            ]);
    }
}
