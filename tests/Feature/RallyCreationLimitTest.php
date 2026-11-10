<?php

namespace Tests\Feature;

use App\Models\StampRally;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RallyCreationLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_unlimited_creation_when_config_is_null(): void
    {
        config(['app.max_user_rallies' => null]);

        $user = User::create([
            'handle' => 'regular_user',
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => 'password',
        ]);

        $this->assertNull($user->maxRalliesAllowed());
        $this->assertTrue($user->canCreateRally());

        $this->actingAs($user)
            ->get('/my/rallies/create')
            ->assertStatus(200);
    }

    public function test_general_user_cannot_exceed_configured_limit(): void
    {
        config(['app.max_user_rallies' => 2]);

        $user = User::create([
            'handle' => 'creator_user',
            'name' => 'Creator User',
            'email' => 'creator@example.com',
            'password' => 'password',
        ]);

        $this->assertEquals(2, $user->maxRalliesAllowed());
        $this->assertEquals(2, $user->remainingRalliesCount());
        $this->assertTrue($user->canCreateRally());

        // Manage index shows "あと 2個 のラリーを作成できます" initially
        $this->actingAs($user)
            ->get('/my')
            ->assertStatus(200)
            ->assertSeeText('あと 2個 のラリーを作成できます')
            ->assertSee('＋ スタンプラリー作成 (あと2個)');

        // 1st rally creation
        $this->actingAs($user)
            ->post('/my/rallies', [
                'title' => 'First Rally',
                'slug' => 'first-rally',
                'visibility' => 'public',
                'completion_condition' => 'all',
            ])
            ->assertRedirect();

        $this->assertEquals(1, $user->createdRallies()->count());
        $this->assertEquals(1, $user->fresh()->remainingRalliesCount());
        $this->assertTrue($user->fresh()->canCreateRally());

        // Manage index shows "あと 1個 のラリーを作成できます"
        $this->actingAs($user)
            ->get('/my')
            ->assertStatus(200)
            ->assertSeeText('あと 1個 のラリーを作成できます')
            ->assertSee('＋ スタンプラリー作成 (あと1個)');

        // Create form also displays remaining count banner
        $this->actingAs($user)
            ->get('/my/rallies/create')
            ->assertStatus(200)
            ->assertSeeText('あと 1個 のスタンプラリーを作成できます');

        // 2nd rally creation (reaches limit)
        $this->actingAs($user)
            ->post('/my/rallies', [
                'title' => 'Second Rally',
                'slug' => 'second-rally',
                'visibility' => 'public',
                'completion_condition' => 'all',
            ])
            ->assertRedirect();

        $this->assertEquals(2, $user->createdRallies()->count());
        $this->assertEquals(0, $user->fresh()->remainingRalliesCount());
        $this->assertFalse($user->fresh()->canCreateRally());

        // Manage index shows limit reached badge instead of creation button
        $this->actingAs($user)
            ->get('/my')
            ->assertStatus(200)
            ->assertSee('作成上限（2個）に達しました')
            ->assertDontSee('🗺️ ＋ スタンプラリー作成');

        // Trying to access create form returns 403
        $this->actingAs($user)
            ->get('/my/rallies/create')
            ->assertStatus(403)
            ->assertSee('スタンプラリーの作成上限数（2件）に達しているため、これ以上新規作成できません。');

        // Trying to POST 3rd rally is blocked with 403
        $this->actingAs($user)
            ->post('/my/rallies', [
                'title' => 'Third Rally',
                'slug' => 'third-rally',
                'visibility' => 'public',
                'completion_condition' => 'all',
            ])
            ->assertStatus(403);

        // Count remains 2
        $this->assertEquals(2, $user->createdRallies()->count());
    }

    public function test_admin_user_bypasses_rally_creation_limit(): void
    {
        config(['app.max_user_rallies' => 1]);

        $admin = User::create([
            'handle' => 'admin_user',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->assertNull($admin->maxRalliesAllowed());
        $this->assertTrue($admin->canCreateRally());

        // Create 1st rally
        StampRally::create([
            'creator_id' => $admin->id,
            'title' => 'Admin Rally 1',
            'slug' => 'admin-rally-1',
        ]);

        // Admin can still create more rallies
        $this->assertTrue($admin->fresh()->canCreateRally());

        $this->actingAs($admin)
            ->get('/my/rallies/create')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->post('/my/rallies', [
                'title' => 'Admin Rally 2',
                'slug' => 'admin-rally-2',
                'visibility' => 'public',
                'completion_condition' => 'all',
            ])
            ->assertRedirect();

        $this->assertEquals(2, $admin->createdRallies()->count());
    }

    public function test_user_can_still_edit_existing_rallies_when_limit_reached(): void
    {
        config(['app.max_user_rallies' => 1]);

        $user = User::create([
            'handle' => 'editor_user',
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $user->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        $this->assertFalse($user->fresh()->canCreateRally());

        // Can access edit page
        $this->actingAs($user)
            ->get('/my/rallies/' . $rally->slug . '/edit')
            ->assertStatus(200)
            ->assertSee('Original Title');

        // Can update rally
        $this->actingAs($user)
            ->put('/my/rallies/' . $rally->slug, [
                'title' => 'Updated Title',
                'slug' => 'original-slug',
                'visibility' => 'public',
                'completion_condition' => 'all',
            ])
            ->assertRedirect();

        $this->assertEquals('Updated Title', $rally->fresh()->title);
    }
}
