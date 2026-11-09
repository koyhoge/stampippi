<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class UserProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_profile_page_renders_oauth_avatar_and_fallback(): void
    {
        $userWithOauthAvatar = User::create([
            'handle' => 'user_oauth',
            'name' => 'OAuth User',
            'email' => 'oauth@example.com',
            'password' => 'password',
            'avatar_url' => 'https://example.com/oauth-photo.jpg',
        ]);

        $this->actingAs($userWithOauthAvatar)
            ->get(route('profile.edit'))
            ->assertStatus(200)
            ->assertSee('https://example.com/oauth-photo.jpg')
            ->assertSee('連携サービス (OAuth) のアイコンを使用中');

        $userWithoutAvatar = User::create([
            'handle' => 'user_plain',
            'name' => 'Plain User',
            'email' => 'plain@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($userWithoutAvatar)
            ->get(route('profile.edit'))
            ->assertStatus(200)
            ->assertSee('P'); // First letter initial
    }

    public function test_user_can_upload_custom_avatar_and_override_oauth_avatar(): void
    {
        $user = User::create([
            'handle' => 'avatar_tester',
            'name' => 'Avatar Tester',
            'email' => 'avatar@example.com',
            'password' => 'password',
            'avatar_url' => 'https://example.com/original-oauth.jpg',
        ]);

        $fakeFile = UploadedFile::fake()->image('my_custom_avatar.png', 120, 120);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'handle' => $user->handle,
                'name' => $user->name,
                'avatar' => $fakeFile,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'プロフィールを更新しました。');

        $user->refresh();
        $this->assertTrue($user->hasCustomAvatar());
        $this->assertNotNull($user->custom_avatar_path);
        $this->assertStringContainsString('avatars/', $user->avatarUrl());
        $this->assertStringNotContainsString('original-oauth.jpg', $user->avatarUrl());

        // View displays the custom avatar and indicator
        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertStatus(200)
            ->assertSee('独自アイコン設定中')
            ->assertSee('削除して連携元 (OAuth) のアイコンに戻す');
    }

    public function test_user_can_remove_custom_avatar_and_revert_to_oauth_avatar(): void
    {
        $user = User::create([
            'handle' => 'revert_tester',
            'name' => 'Revert Tester',
            'email' => 'revert@example.com',
            'password' => 'password',
            'avatar_url' => 'https://example.com/original-oauth.jpg',
        ]);

        // 1. Upload custom avatar
        $fakeFile = UploadedFile::fake()->image('custom.jpg', 100, 100);
        $this->actingAs($user)->put(route('profile.update'), [
            'handle' => $user->handle,
            'name' => $user->name,
            'avatar' => $fakeFile,
        ])->assertRedirect();

        $user->refresh();
        $oldPath = $user->custom_avatar_path;
        $this->assertNotNull($oldPath);

        // 2. Remove custom avatar by checking remove_avatar
        $this->actingAs($user)->put(route('profile.update'), [
            'handle' => $user->handle,
            'name' => $user->name,
            'remove_avatar' => 1,
        ])->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->hasCustomAvatar());
        $this->assertNull($user->custom_avatar_path);
        $this->assertEquals('https://example.com/original-oauth.jpg', $user->avatarUrl());
    }

    public function test_oauth_login_preserves_custom_avatar(): void
    {
        $user = User::create([
            'handle' => 'google_user',
            'name' => 'Google User',
            'email' => 'google_user@example.com',
            'password' => 'password',
            'google_id' => 'google-uid-12345',
            'avatar_url' => 'https://example.com/old-google.jpg',
            'custom_avatar_path' => 'avatars/my-uploaded-icon.png',
        ]);

        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-uid-12345');
        $socialiteUser->shouldReceive('getEmail')->andReturn('google_user@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Google User Updated');
        $socialiteUser->shouldReceive('getNickname')->andReturn(null);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/new-google.jpg');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        // OAuth avatar_url column is updated with latest Google avatar
        $this->assertEquals('https://example.com/new-google.jpg', $user->avatar_url);
        // But custom avatar is still active and prioritized
        $this->assertTrue($user->hasCustomAvatar());
        $this->assertEquals('avatars/my-uploaded-icon.png', $user->custom_avatar_path);
        $this->assertStringContainsString('my-uploaded-icon.png', $user->avatarUrl());
    }

    public function test_avatar_url_scheme_validation_blocks_unsafe_schemes(): void
    {
        $unsafeUser = User::create([
            'handle' => 'unsafe_user',
            'name' => 'Unsafe User',
            'email' => 'unsafe@example.com',
            'password' => 'password',
            'avatar_url' => 'javascript:alert(1)',
        ]);

        $this->assertNull($unsafeUser->avatarUrl());

        $dataUriUser = User::create([
            'handle' => 'data_user',
            'name' => 'Data URI User',
            'email' => 'data@example.com',
            'password' => 'password',
            'avatar_url' => 'data:text/html,<script>alert(1)</script>',
        ]);

        $this->assertNull($dataUriUser->avatarUrl());

        $safeUser = User::create([
            'handle' => 'safe_user',
            'name' => 'Safe User',
            'email' => 'safe@example.com',
            'password' => 'password',
            'avatar_url' => 'https://secure.gravatar.com/avatar/12345',
        ]);

        $this->assertEquals('https://secure.gravatar.com/avatar/12345', $safeUser->avatarUrl());
    }
}

