<?php

namespace Tests\Feature;

use App\Models\RallyParticipation;
use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use App\Models\UserStamp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RallyParticipantDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_rally_shows_participants_and_location_checkins_when_display_mode_is_icons(): void
    {
        $creator = User::create([
            'handle' => 'creator',
            'name' => 'Creator User',
            'email' => 'creator@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'title' => 'Participant Test Rally',
            'slug' => 'participant-test-rally',
            'description' => 'Testing participant display',
            'creator_id' => $creator->id,
            'is_published' => true,
            'participant_display_mode' => 'icons',
        ]);

        $loc1 = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'First Checkpoint',
            'latitude' => 35.681236,
            'longitude' => 139.767125,
            'order_no' => 1,
            'secret_token' => 'token1',
        ]);

        $loc2 = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Second Checkpoint',
            'latitude' => 35.689500,
            'longitude' => 139.691700,
            'order_no' => 2,
            'secret_token' => 'token2',
        ]);

        $player1 = User::create([
            'handle' => 'alice',
            'name' => 'Alice Walker',
            'email' => 'alice@example.com',
            'password' => 'password',
            'avatar_url' => 'https://example.com/alice.png',
        ]);

        $player2 = User::create([
            'handle' => 'bob',
            'name' => 'Bob Runner',
            'email' => 'bob@example.com',
            'password' => 'password',
        ]);

        // Alice participates and checked in at loc1
        RallyParticipation::create([
            'user_id' => $player1->id,
            'stamp_rally_id' => $rally->id,
            'joined_at' => now(),
            'stamps_count' => 1,
            'is_completed' => false,
        ]);
        UserStamp::create([
            'user_id' => $player1->id,
            'stamp_rally_id' => $rally->id,
            'stamp_location_id' => $loc1->id,
            'checkin_lat' => 35.681236,
            'checkin_lng' => 139.767125,
            'distance_meters' => 5,
            'stamped_at' => now(),
        ]);

        // Bob participates without checkin
        RallyParticipation::create([
            'user_id' => $player2->id,
            'stamp_rally_id' => $rally->id,
            'joined_at' => now(),
            'stamps_count' => 0,
            'is_completed' => false,
        ]);

        $response = $this->get('/rallies/' . $rally->slug);
        $response->assertStatus(200);

        // Header shows participant count
        $response->assertSee('2人');

        // Header shows links to participant detail pages
        $response->assertSee('/rallies/' . $rally->slug . '/participants/alice');
        $response->assertSee('/rallies/' . $rally->slug . '/participants/bob');

        // Location 1 shows Alice as checked-in
        $response->assertSee('1人がチェックイン');
        $response->assertSee('Alice Walker (@alice) の参加状態を見る');

        // Location 2 has 0 checkins
        $response->assertSee('0人がチェックイン');
    }

    public function test_rally_hides_icons_when_display_mode_is_count_only(): void
    {
        $creator = User::create([
            'handle' => 'creator2',
            'name' => 'Creator Two',
            'email' => 'creator2@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'title' => 'Count Only Rally',
            'slug' => 'count-only-rally',
            'description' => 'Testing count only mode',
            'creator_id' => $creator->id,
            'is_published' => true,
            'participant_display_mode' => 'count_only',
        ]);

        $loc = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Secret Spot',
            'latitude' => 35.681236,
            'longitude' => 139.767125,
            'order_no' => 1,
            'secret_token' => 'spottoken',
        ]);

        $player = User::create([
            'handle' => 'charlie',
            'name' => 'Charlie Hidden',
            'email' => 'charlie@example.com',
            'password' => 'password',
        ]);

        RallyParticipation::create([
            'user_id' => $player->id,
            'stamp_rally_id' => $rally->id,
            'joined_at' => now(),
            'stamps_count' => 1,
            'is_completed' => true,
            'completed_at' => now(),
        ]);
        UserStamp::create([
            'user_id' => $player->id,
            'stamp_rally_id' => $rally->id,
            'stamp_location_id' => $loc->id,
            'checkin_lat' => 35.681236,
            'checkin_lng' => 139.767125,
            'distance_meters' => 10,
            'stamped_at' => now(),
        ]);

        $response = $this->get('/rallies/' . $rally->slug);
        $response->assertStatus(200);

        // Count is displayed
        $response->assertSee('1人');
        $response->assertSee('1人がチェックイン');

        // Icons / Links to participant detail page are NOT rendered in rally view
        $response->assertDontSee('/rallies/' . $rally->slug . '/participants/charlie');
    }

    public function test_rally_participant_detail_page_renders_correctly(): void
    {
        $creator = User::create([
            'handle' => 'creator3',
            'name' => 'Creator Three',
            'email' => 'creator3@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'title' => 'Awesome Adventure',
            'slug' => 'awesome-adventure',
            'creator_id' => $creator->id,
            'is_published' => true,
        ]);

        $loc1 = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Castle Gate',
            'latitude' => 35.681236,
            'longitude' => 139.767125,
            'order_no' => 1,
            'secret_token' => 't1',
        ]);

        $loc2 = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Dragon Cave',
            'latitude' => 35.689500,
            'longitude' => 139.691700,
            'order_no' => 2,
            'secret_token' => 't2',
        ]);

        $player = User::create([
            'handle' => 'dave',
            'name' => 'Dave Explorer',
            'email' => 'dave@example.com',
            'password' => 'password',
        ]);

        RallyParticipation::create([
            'user_id' => $player->id,
            'stamp_rally_id' => $rally->id,
            'joined_at' => now(),
            'stamps_count' => 1,
            'is_completed' => false,
        ]);

        UserStamp::create([
            'user_id' => $player->id,
            'stamp_rally_id' => $rally->id,
            'stamp_location_id' => $loc1->id,
            'checkin_lat' => 35.681236,
            'checkin_lng' => 139.767125,
            'distance_meters' => 8,
            'stamped_at' => now(),
        ]);

        $response = $this->get('/rallies/' . $rally->slug . '/participants/dave');
        $response->assertStatus(200);
        $response->assertSee('Dave Explorer さんの参加状況');
        $response->assertSee('(@dave)');
        $response->assertSee('進行中');
        $response->assertSee('1 / 2 獲得');
        $response->assertSee('Castle Gate');
        $response->assertSee('STAMPED');
        $response->assertSee('Dragon Cave');
        $response->assertSee('UNSTAMPED');
        $response->assertSee('未獲得');
    }

    public function test_rally_participant_detail_page_returns_404_for_non_participant(): void
    {
        $creator = User::create([
            'handle' => 'creator4',
            'name' => 'Creator Four',
            'email' => 'creator4@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'title' => 'Mystery Rally',
            'slug' => 'mystery-rally',
            'creator_id' => $creator->id,
            'is_published' => true,
        ]);

        $nonParticipant = User::create([
            'handle' => 'outsider',
            'name' => 'Outsider User',
            'email' => 'outsider@example.com',
            'password' => 'password',
        ]);

        $response = $this->get('/rallies/' . $rally->slug . '/participants/outsider');
        $response->assertStatus(404);
    }

    public function test_rally_management_can_update_participant_display_mode(): void
    {
        $creator = User::create([
            'handle' => 'creator5',
            'name' => 'Creator Five',
            'email' => 'creator5@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'title' => 'Configurable Rally',
            'slug' => 'configurable-rally',
            'creator_id' => $creator->id,
            'visibility' => 'draft',
            'completion_condition' => 'all',
            'participant_display_mode' => 'icons',
        ]);

        // Form page has the select option
        $this->actingAs($creator)
            ->get('/my/rallies/' . $rally->slug . '/edit')
            ->assertStatus(200)
            ->assertSee('participant_display_mode')
            ->assertSee('アイコンと人数を表示')
            ->assertSee('人数のみ表示');

        // Update to count_only
        $this->actingAs($creator)
            ->put('/my/rallies/' . $rally->slug, [
                'title' => 'Configurable Rally Updated',
                'slug' => 'configurable-rally',
                'description' => 'Updated description',
                'visibility' => 'public',
                'completion_condition' => 'all',
                'participant_display_mode' => 'count_only',
            ])
            ->assertRedirect();

        $this->assertEquals('count_only', $rally->fresh()->participant_display_mode);
    }

    public function test_location_checkins_aggregation_and_recent_icons_limit(): void
    {
        $creator = User::create([
            'handle' => 'creator6',
            'name' => 'Creator Six',
            'email' => 'creator6@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'title' => 'Many Checkins Rally',
            'slug' => 'many-checkins-rally',
            'creator_id' => $creator->id,
            'visibility' => 'public',
            'completion_condition' => 'all',
            'participant_display_mode' => 'icons',
        ]);

        $loc = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Popular Spot',
            'latitude' => 35.681236,
            'longitude' => 139.767125,
            'radius_meters' => 100,
        ]);

        // Create 15 users and have them check in at different times
        for ($i = 1; $i <= 15; $i++) {
            $user = User::create([
                'handle' => "runner{$i}",
                'name' => "Runner {$i}",
                'email' => "runner{$i}@example.com",
                'password' => 'password',
            ]);

            RallyParticipation::create([
                'user_id' => $user->id,
                'stamp_rally_id' => $rally->id,
                'joined_at' => now()->subMinutes(100 - $i),
            ]);

            UserStamp::create([
                'user_id' => $user->id,
                'stamp_rally_id' => $rally->id,
                'stamp_location_id' => $loc->id,
                'checkin_lat' => 35.681236,
                'checkin_lng' => 139.767125,
                'distance_meters' => 5,
                'stamped_at' => now()->subMinutes(100 - $i),
            ]);
        }

        $response = $this->get('/rallies/' . $rally->slug);
        $response->assertStatus(200);

        // Location shows total count aggregated from SQL
        $response->assertSee('15人がチェックイン');

        // Shows load-more button for remaining 5 users (15 - 10 = 5)
        $response->assertSee('+5人');
        $response->assertDontSee('+5人表示');

        // Verify viewData locationCheckinCounts, recentParticipants, and locationRecentUsers
        $counts = $response->viewData('locationCheckinCounts');
        $this->assertSame(15, $counts->get($loc->id));

        $recentParticipants = $response->viewData('recentParticipants');
        $this->assertCount(10, $recentParticipants);

        $recentUsers = $response->viewData('locationRecentUsers')->get($loc->id);
        $this->assertCount(10, $recentUsers);

        // Most recent 10 runners (runners 6 through 15) are included
        for ($i = 6; $i <= 15; $i++) {
            $this->assertTrue($recentParticipants->pluck('handle')->contains("runner{$i}"));
            $this->assertTrue($recentUsers->pluck('handle')->contains("runner{$i}"));
        }

        // Oldest 5 runners (runner 1 through 5) are excluded from the initial 10 icons
        for ($i = 1; $i <= 5; $i++) {
            $this->assertFalse($recentParticipants->pluck('handle')->contains("runner{$i}"));
            $this->assertFalse($recentUsers->pluck('handle')->contains("runner{$i}"));
        }
    }

    public function test_participants_and_location_checkins_pagination_endpoints(): void
    {
        $creator = User::create([
            'handle' => 'creator_page',
            'name' => 'Creator Page',
            'email' => 'creator_page@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'title' => 'Paginated Rally',
            'slug' => 'paginated-rally',
            'creator_id' => $creator->id,
            'visibility' => 'public',
            'completion_condition' => 'all',
            'participant_display_mode' => 'icons',
        ]);

        $loc = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Busy Venue',
            'latitude' => 35.681236,
            'longitude' => 139.767125,
            'radius_meters' => 100,
        ]);

        for ($i = 1; $i <= 25; $i++) {
            $user = User::create([
                'handle' => "walker{$i}",
                'name' => "Walker {$i}",
                'email' => "walker{$i}@example.com",
                'password' => 'password',
            ]);

            RallyParticipation::create([
                'user_id' => $user->id,
                'stamp_rally_id' => $rally->id,
                'joined_at' => now()->subMinutes(100 - $i),
            ]);

            UserStamp::create([
                'user_id' => $user->id,
                'stamp_rally_id' => $rally->id,
                'stamp_location_id' => $loc->id,
                'checkin_lat' => 35.681236,
                'checkin_lng' => 139.767125,
                'distance_meters' => 5,
                'stamped_at' => now()->subMinutes(100 - $i),
            ]);
        }

        // Initial page shows compact +10人 button without extra remaining suffix
        $page = $this->get('/rallies/' . $rally->slug);
        $page->assertStatus(200);
        $page->assertSee('+10人');
        $page->assertDontSee('+10人表示');
        $page->assertDontSee('(残り15)');
        $page->assertDontSee('(残15)');

        // Rally participants batch 2 (offset=10, limit=10 -> walkers 15 down to 6)
        $pBatch2 = $this->getJson("/rallies/{$rally->slug}/participants?offset=10&limit=10");
        $pBatch2->assertOk()
            ->assertJsonPath('total', 25)
            ->assertJsonPath('next_offset', 20)
            ->assertJsonPath('remaining', 5)
            ->assertJsonPath('has_more', true)
            ->assertJsonCount(10, 'users');
        $this->assertSame('walker15', $pBatch2->json('users.0.handle'));
        $this->assertSame('walker6', $pBatch2->json('users.9.handle'));

        // Rally participants batch 3 (offset=20, limit=10 -> walkers 5 down to 1)
        $pBatch3 = $this->getJson("/rallies/{$rally->slug}/participants?offset=20&limit=10");
        $pBatch3->assertOk()
            ->assertJsonPath('total', 25)
            ->assertJsonPath('next_offset', 25)
            ->assertJsonPath('remaining', 0)
            ->assertJsonPath('has_more', false)
            ->assertJsonCount(5, 'users');
        $this->assertSame('walker5', $pBatch3->json('users.0.handle'));
        $this->assertSame('walker1', $pBatch3->json('users.4.handle'));

        // Location checkins batch 2 (offset=10, limit=10 -> walkers 15 down to 6)
        $cBatch2 = $this->getJson("/rallies/{$rally->slug}/locations/{$loc->id}/checkins?offset=10&limit=10");
        $cBatch2->assertOk()
            ->assertJsonPath('total', 25)
            ->assertJsonPath('next_offset', 20)
            ->assertJsonPath('remaining', 5)
            ->assertJsonPath('has_more', true)
            ->assertJsonCount(10, 'users');
        $this->assertSame('walker15', $cBatch2->json('users.0.handle'));
        $this->assertSame('walker6', $cBatch2->json('users.9.handle'));

        // Location checkins batch 3 (offset=20, limit=10 -> walkers 5 down to 1)
        $cBatch3 = $this->getJson("/rallies/{$rally->slug}/locations/{$loc->id}/checkins?offset=20&limit=10");
        $cBatch3->assertOk()
            ->assertJsonPath('total', 25)
            ->assertJsonPath('next_offset', 25)
            ->assertJsonPath('remaining', 0)
            ->assertJsonPath('has_more', false)
            ->assertJsonCount(5, 'users');
        $this->assertSame('walker5', $cBatch3->json('users.0.handle'));
        $this->assertSame('walker1', $cBatch3->json('users.4.handle'));
    }

    public function test_pagination_endpoints_respect_privacy_and_ownership(): void
    {
        $creator = User::create([
            'handle' => 'creator_priv',
            'name' => 'Creator Priv',
            'email' => 'creator_priv@example.com',
            'password' => 'password',
        ]);

        $countOnlyRally = StampRally::create([
            'title' => 'Private Icons Rally',
            'slug' => 'private-icons-rally',
            'creator_id' => $creator->id,
            'visibility' => 'public',
            'participant_display_mode' => 'count_only',
        ]);

        $loc = StampLocation::create([
            'stamp_rally_id' => $countOnlyRally->id,
            'name' => 'Private Spot',
            'latitude' => 35.681236,
            'longitude' => 139.767125,
            'radius_meters' => 100,
        ]);

        $otherRally = StampRally::create([
            'title' => 'Other Public Rally',
            'slug' => 'other-public-rally',
            'creator_id' => $creator->id,
            'visibility' => 'public',
            'participant_display_mode' => 'icons',
        ]);

        // count_only forbids icon pagination APIs
        $this->getJson("/rallies/{$countOnlyRally->slug}/participants")->assertStatus(403);
        $this->getJson("/rallies/{$countOnlyRally->slug}/locations/{$loc->id}/checkins")->assertStatus(403);

        // Mismatched rally & location returns 404
        $this->getJson("/rallies/{$otherRally->slug}/locations/{$loc->id}/checkins")->assertStatus(404);
    }

    public function test_location_checkin_counts_are_cached_and_invalidated_on_checkin(): void
    {
        $creator = User::create([
            'handle' => 'creator7',
            'name' => 'Creator Seven',
            'email' => 'creator7@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'title' => 'Cache Test Rally',
            'slug' => 'cache-test-rally',
            'creator_id' => $creator->id,
            'visibility' => 'public',
            'completion_condition' => 'all',
            'participant_display_mode' => 'icons',
        ]);

        $loc = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Cache Spot',
            'latitude' => 35.681236,
            'longitude' => 139.767125,
            'radius_meters' => 100,
        ]);

        $cacheKey = "rally:{$rally->id}:stamp-counts";
        Cache::forget($cacheKey);

        // First access caches the result (0)
        $this->get('/rallies/' . $rally->slug)->assertStatus(200);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertSame(0, Cache::get($cacheKey)->get($loc->id, 0));

        $player = User::create([
            'handle' => 'player1',
            'name' => 'Player One',
            'email' => 'player1@example.com',
            'password' => 'password',
        ]);

        // When cache is present, showRally still reads the cached value
        $this->get('/rallies/' . $rally->slug)->assertStatus(200);
        $this->assertSame(0, Cache::get($cacheKey)->get($loc->id, 0));

        // Now player checks in via checkin endpoint
        $this->actingAs($player)
            ->post("/rallies/{$rally->slug}/locations/{$loc->id}/checkin", [
                'latitude' => 35.681236,
                'longitude' => 139.767125,
            ])
            ->assertRedirect();

        // Cache was forgotten on checkin!
        $this->assertFalse(Cache::has($cacheKey));

        // Accessing showRally recaches with updated count (1)
        $this->get('/rallies/' . $rally->slug)->assertStatus(200);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertSame(1, Cache::get($cacheKey)->get($loc->id));
    }
}
