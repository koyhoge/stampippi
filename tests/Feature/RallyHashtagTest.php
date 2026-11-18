<?php

namespace Tests\Feature;

use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RallyHashtagTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_rally_with_hashtag_and_normalizes_prefix(): void
    {
        $user = User::create([
            'handle' => 'rally_creator',
            'name' => 'Rally Creator',
            'email' => 'creator@example.com',
            'password' => 'password',
        ]);

        // 1. Create rally without leading #
        $response = $this->actingAs($user)->post(route('my.rallies.store'), [
            'title' => 'PHPカンファレンスラリー',
            'slug' => 'phpcon-rally',
            'description' => 'テスト説明',
            'hashtag' => 'phpcon2026',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $response->assertRedirect();
        $rally = StampRally::where('slug', 'phpcon-rally')->firstOrFail();
        $this->assertSame('#phpcon2026', $rally->hashtag);

        // 2. Create another rally with leading #
        $this->actingAs($user)->post(route('my.rallies.store'), [
            'title' => 'Laravelカンファレンスラリー',
            'slug' => 'laravel-rally',
            'description' => 'テスト説明',
            'hashtag' => '#laracon2026',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ])->assertRedirect();

        $rally2 = StampRally::where('slug', 'laravel-rally')->firstOrFail();
        $this->assertSame('#laracon2026', $rally2->hashtag);
    }

    public function test_can_update_and_clear_rally_hashtag(): void
    {
        $user = User::create([
            'handle' => 'updater',
            'name' => 'Updater',
            'email' => 'updater@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $user->id,
            'title' => '更新前ラリー',
            'slug' => 'updatable-rally',
            'hashtag' => '#old_tag',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        // Update hashtag
        $this->actingAs($user)->put(route('my.rallies.update', $rally), [
            'title' => '更新前ラリー',
            'slug' => 'updatable-rally',
            'hashtag' => 'new_rally_tag',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ])->assertRedirect();

        $this->assertSame('#new_rally_tag', $rally->fresh()->hashtag);

        // Clear hashtag
        $this->actingAs($user)->put(route('my.rallies.update', $rally), [
            'title' => '更新前ラリー',
            'slug' => 'updatable-rally',
            'hashtag' => '',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ])->assertRedirect();

        $this->assertNull($rally->fresh()->hashtag);
    }

    public function test_social_share_appends_both_rally_and_venue_hashtags(): void
    {
        $creator = User::create([
            'handle' => 'creator_tags',
            'name' => 'Creator Tags',
            'email' => 'creator_tags@example.com',
            'password' => 'password',
        ]);

        $player = User::create([
            'handle' => 'player_tags',
            'name' => 'Player Tags',
            'email' => 'player_tags@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $creator->id,
            'title' => '大田区フェスラリー',
            'slug' => 'ota-fes-rally',
            'hashtag' => '#ota_fes2026',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $location = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'PiO大展示ホール',
            'latitude' => 35.5588,
            'longitude' => 139.7240,
            'radius_meters' => 100,
            'hashtag' => '#pio_hall_a',
        ]);

        $this->actingAs($player)->post(route('rallies.join', $rally));

        // Checkin at location
        $response = $this->actingAs($player)->post(route('rallies.checkin', [$rally, $location]), [
            'latitude' => 35.5588,
            'longitude' => 139.7240,
        ]);

        $response->assertRedirect(route('rallies.show', $rally));

        // Follow redirect and check celebration banner
        $followed = $this->actingAs($player)->get(route('rallies.show', $rally));
        $followed->assertStatus(200);

        // Both hashtags should appear in share text
        $followed->assertSee('#ota_fes2026');
        $followed->assertSee('#pio_hall_a');

        // Old default hashtags MUST NOT be present
        $followed->assertDontSee('#Stampippi');
        $followed->assertDontSee('%23Stampippi');
        $followed->assertDontSee('#スタンプラリー');
        $followed->assertDontSee('%23%E3%82%B9%E3%82%BF%E3%83%B3%E3%83%97%E3%83%A9%E3%83%AA%E3%83%BC');
    }

    public function test_social_share_appends_only_rally_hashtag_when_venue_has_none(): void
    {
        $creator = User::create([
            'handle' => 'creator_only_rally',
            'name' => 'Creator Only Rally',
            'email' => 'creator_only@example.com',
            'password' => 'password',
        ]);

        $player = User::create([
            'handle' => 'player_only_rally',
            'name' => 'Player Only Rally',
            'email' => 'player_only@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $creator->id,
            'title' => '品川散歩ラリー',
            'slug' => 'shinagawa-walk',
            'hashtag' => '#shinagawa_walk',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $location = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => '品川駅前広場',
            'latitude' => 35.6284,
            'longitude' => 139.7387,
            'radius_meters' => 100,
            'hashtag' => null,
        ]);

        $this->actingAs($player)->post(route('rallies.join', $rally));

        $this->actingAs($player)->post(route('rallies.checkin', [$rally, $location]), [
            'latitude' => 35.6284,
            'longitude' => 139.7387,
        ])->assertRedirect();

        $followed = $this->actingAs($player)->get(route('rallies.show', $rally));
        $followed->assertStatus(200);

        // Shows rally hashtag
        $followed->assertSee('#shinagawa_walk');

        // Does not show #Stampippi
        $followed->assertDontSee('#Stampippi');
        $followed->assertDontSee('%23Stampippi');
    }

    public function test_social_share_deduplicates_when_rally_and_venue_share_same_hashtag(): void
    {
        $rally = new StampRally([
            'title' => 'Test Rally',
            'hashtag' => '#phpcon',
        ]);

        $location = new StampLocation([
            'name' => 'Test Venue',
            'hashtag' => '#phpcon',
        ]);

        $this->assertSame(['#phpcon'], $rally->hashtags($location));
        $this->assertSame('#phpcon', $rally->shareHashtagString($location));
        $this->assertSame("テストメッセージ\n#phpcon", $rally->formatShareText("テストメッセージ", $location));
    }
}
