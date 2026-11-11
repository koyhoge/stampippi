<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_privacy_policy_page(): void
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200)
            ->assertSee('プライバシーポリシー')
            ->assertSee('取得する情報')
            ->assertSee('位置情報（GPS）')
            ->assertSee('情報の利用目的')
            ->assertSee('個人情報の第三者提供について')
            ->assertSee('Cookie（クッキー）の利用')
            ->assertSee('お問い合わせ');
    }

    public function test_authenticated_user_can_view_privacy_policy_page(): void
    {
        $user = User::create([
            'handle' => 'privacy_user',
            'name' => 'Privacy User',
            'email' => 'privacy@example.com',
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->get('/privacy');

        $response->assertStatus(200)
            ->assertSee('プライバシーポリシー');
    }

    public function test_footer_contains_privacy_policy_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee(route('privacy'))
            ->assertSee('プライバシーポリシー');
    }
}
