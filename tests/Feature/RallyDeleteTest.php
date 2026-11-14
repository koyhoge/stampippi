<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\RallyParticipation;
use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use App\Models\UserStamp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RallyDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_own_rally_with_cascade(): void
    {
        $user = User::create([
            'handle' => 'creator_user',
            'name' => 'Creator User',
            'email' => 'creator@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $user->id,
            'title' => 'テストラリー to be deleted',
            'slug' => 'test-rally-delete',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $location = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Test Location',
            'latitude' => 35.6812,
            'longitude' => 139.7671,
            'radius_meters' => 50,
            'stamp_icon_value' => 'star',
            'stamp_color' => '#6366F1',
        ]);

        $participant = User::create([
            'handle' => 'participant_user',
            'name' => 'Participant User',
            'email' => 'participant@example.com',
            'password' => 'password',
        ]);

        $participation = RallyParticipation::create([
            'user_id' => $participant->id,
            'stamp_rally_id' => $rally->id,
            'joined_at' => now(),
        ]);

        $stamp = UserStamp::create([
            'user_id' => $participant->id,
            'stamp_rally_id' => $rally->id,
            'stamp_location_id' => $location->id,
            'stamped_at' => now(),
            'checkin_lat' => 35.6812,
            'checkin_lng' => 139.7671,
            'distance_meters' => 5,
        ]);

        // Edit page displays danger zone and delete confirmation modal
        $this->actingAs($user)
            ->get(route('my.rallies.edit', $rally))
            ->assertStatus(200)
            ->assertSee('スタンプラリーの削除')
            ->assertSee('delete-rally-modal')
            ->assertSee('上記の注意事項を理解し、このスタンプラリーを完全に削除することに同意します');

        // Delete execution
        $response = $this->actingAs($user)
            ->delete(route('my.rallies.destroy', $rally));

        $response->assertRedirect(route('my.index'))
            ->assertSessionHas('status', 'スタンプラリー「テストラリー to be deleted」を削除しました。');

        // Verify cascade deletion
        $this->assertDatabaseMissing('stamp_rallies', ['id' => $rally->id]);
        $this->assertDatabaseMissing('stamp_locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('rally_participations', ['id' => $participation->id]);
        $this->assertDatabaseMissing('user_stamps', ['id' => $stamp->id]);
    }

    public function test_user_cannot_delete_other_users_rally(): void
    {
        $owner = User::create([
            'handle' => 'owner_user',
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password',
        ]);

        $other = User::create([
            'handle' => 'other_user',
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $owner->id,
            'title' => 'Owner Rally',
            'slug' => 'owner-rally',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $this->actingAs($other)
            ->delete(route('my.rallies.destroy', $rally))
            ->assertStatus(403);

        $this->assertDatabaseHas('stamp_rallies', ['id' => $rally->id]);
    }

    public function test_admin_can_delete_any_rally(): void
    {
        $admin = User::create([
            'handle' => 'admin_user',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $creator = User::create([
            'handle' => 'creator_user_2',
            'name' => 'Creator User 2',
            'email' => 'creator2@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $creator->id,
            'title' => 'Creator Rally',
            'slug' => 'creator-rally',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $this->actingAs($admin)
            ->delete(route('my.rallies.destroy', $rally))
            ->assertRedirect(route('my.index'))
            ->assertSessionHas('status', 'スタンプラリー「Creator Rally」を削除しました。');

        $this->assertDatabaseMissing('stamp_rallies', ['id' => $rally->id]);
    }

    public function test_deleting_rally_recovers_rally_creation_slot_for_general_user(): void
    {
        config(['app.max_user_rallies' => 1]);

        $user = User::create([
            'handle' => 'slot_user',
            'name' => 'Slot User',
            'email' => 'slot@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $user->id,
            'title' => 'Slot Rally',
            'slug' => 'slot-rally',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $this->assertFalse($user->canCreateRally());
        $this->assertSame(0, $user->remainingRalliesCount());

        $this->actingAs($user)
            ->delete(route('my.rallies.destroy', $rally))
            ->assertRedirect(route('my.index'));

        $this->assertTrue($user->fresh()->canCreateRally());
        $this->assertSame(1, $user->fresh()->remainingRalliesCount());
    }
}
