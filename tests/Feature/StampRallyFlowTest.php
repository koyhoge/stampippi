<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\RallySeries;
use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_group_and_series_and_rally_hierarchy_with_gps_checkin(): void
    {
        $owner = User::create([
            'handle' => 'koyhoge',
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $coStaff = User::create([
            'handle' => 'staff_member',
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);

        $player = User::create([
            'handle' => 'rally_player',
            'name' => 'Player User',
            'email' => 'player@example.com',
            'password' => 'password',
        ]);

        // 1. Owner creates a Group
        $this->actingAs($owner)
            ->post('/my/groups', [
                'slug' => 'phpcon',
                'name' => 'PHPカンファレンス運営',
                'description' => '全国PHPカンファレンス運営グループ',
            ])
            ->assertRedirect();

        $group = Group::where('slug', 'phpcon')->firstOrFail();

        // 2. Owner adds registered user by @handle
        $this->actingAs($owner)
            ->post("/my/groups/{$group->slug}/members", [
                'identifier' => '@staff_member',
                'role' => 'member',
            ])
            ->assertRedirect();

        $this->assertTrue($coStaff->canManageGroup($group));

        // 3. Co-staff creates a RallySeries under the Group
        $this->actingAs($coStaff)
            ->post('/my/series', [
                'group_id' => $group->id,
                'title' => 'PHPカンファレンススタンプラリー',
                'slug' => 'phpcon-series',
                'description' => '各年版を含むシリーズ',
                'visibility' => 'public',
            ])
            ->assertRedirect();

        $series = RallySeries::where('slug', 'phpcon-series')->firstOrFail();

        // 4. Create a permanent StampRally (starts_at = null, ends_at = null)
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

        // 5. Add a StampLocation (venue) with no period restriction
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

        // 6. Player attempts check-in from too far away (Tokyo Station ~14km away) -> Fails
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

        // 7. Player checks in within 20 meters of the venue -> Succeeds and completes rally!
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

        // 8. Player can view the completion certificate
        $this->actingAs($player)
            ->get("/rallies/{$rally->slug}/certificate")
            ->assertStatus(200)
            ->assertSee('コンプリート達成証')
            ->assertSee('大田区産業プラザPiO');
    }

    public function test_location_period_window_is_enforced_when_specified(): void
    {
        $creator = User::create([
            'handle' => 'creator_user',
            'name' => 'Creator',
            'email' => 'creator@example.com',
            'password' => 'password',
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
