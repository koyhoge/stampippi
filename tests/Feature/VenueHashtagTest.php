<?php

namespace Tests\Feature;

use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueHashtagTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_venue_with_hashtag_and_normalizes_hash_prefix(): void
    {
        $user = User::create([
            'handle' => 'venue_creator',
            'name' => 'Venue Creator',
            'email' => 'venue_creator@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $user->id,
            'title' => 'ハッシュタグテストラリー',
            'slug' => 'hashtag-rally',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        // 1. Create venue with hashtag without leading # (should be normalized to #phpcon2026)
        $response = $this->actingAs($user)->post(route('my.rallies.locations.store', $rally), [
            'name' => '第1会場',
            'latitude' => 35.6812,
            'longitude' => 139.7671,
            'radius_meters' => 50,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'star',
            'hashtag' => 'phpcon2026',
        ]);

        $response->assertRedirect(route('my.rallies.edit', $rally));

        $location1 = StampLocation::where('name', '第1会場')->firstOrFail();
        $this->assertSame('#phpcon2026', $location1->hashtag);

        // 2. Create venue with hashtag with leading #
        $this->actingAs($user)->post(route('my.rallies.locations.store', $rally), [
            'name' => '第2会場',
            'latitude' => 35.6820,
            'longitude' => 139.7680,
            'radius_meters' => 50,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'elephant',
            'hashtag' => '#phpcon_hall_b',
        ])->assertRedirect();

        $location2 = StampLocation::where('name', '第2会場')->firstOrFail();
        $this->assertSame('#phpcon_hall_b', $location2->hashtag);

        // 3. Create venue with empty hashtag
        $this->actingAs($user)->post(route('my.rallies.locations.store', $rally), [
            'name' => '第3会場（タグなし）',
            'latitude' => 35.6830,
            'longitude' => 139.7690,
            'radius_meters' => 50,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'coffee',
            'hashtag' => '',
        ])->assertRedirect();

        $location3 = StampLocation::where('name', '第3会場（タグなし）')->firstOrFail();
        $this->assertNull($location3->hashtag);
    }

    public function test_can_update_venue_hashtag(): void
    {
        $user = User::create([
            'handle' => 'venue_updater',
            'name' => 'Venue Updater',
            'email' => 'venue_updater@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $user->id,
            'title' => '更新テストラリー',
            'slug' => 'update-rally',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $location = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => '更新前会場',
            'latitude' => 35.6812,
            'longitude' => 139.7671,
            'radius_meters' => 50,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'star',
            'hashtag' => '#old_tag',
        ]);

        // Update with new hashtag
        $this->actingAs($user)->put(route('my.rallies.locations.update', [$rally, $location]), [
            'name' => '更新後会場',
            'latitude' => 35.6812,
            'longitude' => 139.7671,
            'radius_meters' => 50,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'star',
            'hashtag' => 'new_tag',
        ])->assertRedirect();

        $this->assertSame('#new_tag', $location->fresh()->hashtag);

        // Clear hashtag
        $this->actingAs($user)->put(route('my.rallies.locations.update', [$rally, $location]), [
            'name' => '更新後会場',
            'latitude' => 35.6812,
            'longitude' => 139.7671,
            'radius_meters' => 50,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'star',
            'hashtag' => '',
        ])->assertRedirect();

        $this->assertNull($location->fresh()->hashtag);
    }

    public function test_checkin_with_venue_hashtag_includes_hashtag_in_social_share(): void
    {
        $creator = User::create([
            'handle' => 'checkin_creator',
            'name' => 'Checkin Creator',
            'email' => 'checkin_creator@example.com',
            'password' => 'password',
        ]);

        $player = User::create([
            'handle' => 'checkin_player',
            'name' => 'Checkin Player',
            'email' => 'checkin_player@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $creator->id,
            'title' => 'シェアテストラリー',
            'slug' => 'share-rally',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $location = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'PiO展示ホール',
            'latitude' => 35.5612,
            'longitude' => 139.7155,
            'radius_meters' => 100,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'flag',
            'hashtag' => '#phpcon2026_pio',
        ]);

        $this->actingAs($player)->post(route('rallies.join', $rally));

        // Checkin at location
        $response = $this->actingAs($player)->post(route('rallies.checkin', [$rally, $location]), [
            'latitude' => 35.5612,
            'longitude' => 139.7155,
        ]);

        $response->assertRedirect(route('rallies.show', $rally));
        $response->assertSessionHas('checkin_celebration', function ($val) {
            return ($val['location_hashtag'] ?? null) === '#phpcon2026_pio';
        });

        // Following redirect renders celebration banner with the custom hashtag
        $followed = $this->actingAs($player)->get(route('rallies.show', $rally));
        $followed->assertStatus(200)
            ->assertSee('#phpcon2026_pio');
    }
}
