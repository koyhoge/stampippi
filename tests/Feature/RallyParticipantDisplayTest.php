<?php

namespace Tests\Feature;

use App\Models\RallyParticipation;
use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use App\Models\UserStamp;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
