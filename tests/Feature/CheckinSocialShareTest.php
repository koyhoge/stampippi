<?php

namespace Tests\Feature;

use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckinSocialShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkin_redirects_with_celebration_session_and_renders_sns_share(): void
    {
        $creator = User::create([
            'handle' => 'creator_share',
            'name' => 'Creator Share',
            'email' => 'creator@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $creator->id,
            'title' => 'Shibuya Adventure Rally',
            'slug' => 'shibuya-adventure',
            'completion_condition' => 'count',
            'required_locations_count' => 2,
            'visibility' => 'public',
        ]);

        $loc1 = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Hachiko Statue',
            'latitude' => 35.6590,
            'longitude' => 139.7005,
            'radius_meters' => 100,
            'order_no' => 1,
            'secret_token' => 't1',
        ]);

        $loc2 = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Miyashita Park',
            'latitude' => 35.6620,
            'longitude' => 139.7020,
            'radius_meters' => 100,
            'order_no' => 2,
            'secret_token' => 't2',
        ]);

        $player = User::create([
            'handle' => 'explorer',
            'name' => 'Explorer Taro',
            'email' => 'explorer@example.com',
            'password' => 'password',
        ]);

        // Join rally
        $this->actingAs($player)->post('/rallies/' . $rally->slug . '/join');

        // Checkin at Loc 1 (1 of 2 -> venue checkin, not completed yet)
        $response = $this->actingAs($player)
            ->post("/rallies/{$rally->slug}/locations/{$loc1->id}/checkin", [
                'latitude' => 35.6590,
                'longitude' => 139.7005,
                'accuracy' => 5,
            ]);

        $response->assertRedirect('/rallies/' . $rally->slug);
        $response->assertSessionHas('checkin_celebration');

        // Follow redirect to rally page
        $pageResponse = $this->actingAs($player)->get('/rallies/' . $rally->slug);
        $pageResponse->assertStatus(200);

        // Checkin celebration banner and SNS share buttons
        $pageResponse->assertSee('STAMP ACQUIRED!');
        $pageResponse->assertSee('💮 スタンプを獲得しました！');
        $pageResponse->assertSee('SNSでシェアする:');
        $pageResponse->assertSee('https://twitter.com/intent/tweet?text=', false);
        $pageResponse->assertSee('https://line.me/R/msg/text/?', false);
        $pageResponse->assertSee('shareStampippi(', false);

        // Loc 1 venue card now shows stamped and share button
        $pageResponse->assertSee('獲得をシェア:');
    }

    public function test_rally_completion_renders_completion_celebration_and_certificate_share(): void
    {
        $creator = User::create([
            'handle' => 'creator_complete',
            'name' => 'Creator Complete',
            'email' => 'creator2@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $creator->id,
            'title' => 'Single Checkpoint Rally',
            'slug' => 'single-checkpoint',
            'completion_condition' => 'all',
            'visibility' => 'public',
        ]);

        $loc = StampLocation::create([
            'stamp_rally_id' => $rally->id,
            'name' => 'Tokyo Tower',
            'latitude' => 35.6585,
            'longitude' => 139.7454,
            'radius_meters' => 100,
            'order_no' => 1,
            'secret_token' => 't1',
        ]);

        $player = User::create([
            'handle' => 'tokyo_runner',
            'name' => 'Tokyo Runner',
            'email' => 'runner@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($player)->post('/rallies/' . $rally->slug . '/join');

        // Checkin at the only location -> Completes the rally!
        $response = $this->actingAs($player)
            ->post("/rallies/{$rally->slug}/locations/{$loc->id}/checkin", [
                'latitude' => 35.6585,
                'longitude' => 139.7454,
                'accuracy' => 5,
            ]);

        $response->assertRedirect('/rallies/' . $rally->slug);
        $response->assertSessionHas('checkin_celebration');

        // Follow redirect to rally page
        $pageResponse = $this->actingAs($player)->get('/rallies/' . $rally->slug);
        $pageResponse->assertStatus(200);

        // Completion celebration banner
        $pageResponse->assertSee('RALLY COMPLETED!');
        $pageResponse->assertSee('🎉 コンプリート達成おめでとうございます！');
        $pageResponse->assertSee('🏆 達成証を見る');
        $pageResponse->assertSee('https://twitter.com/intent/tweet?text=', false);
        $pageResponse->assertSee('https://line.me/R/msg/text/?', false);

        // Access certificate page
        $certResponse = $this->actingAs($player)->get('/rallies/' . $rally->slug . '/certificate');
        $certResponse->assertStatus(200);
        $certResponse->assertSee('コンプリート達成証');
        $certResponse->assertSee('🏆 快挙をSNSでシェアしよう！');
        $certResponse->assertSee('https://twitter.com/intent/tweet?text=', false);
        $certResponse->assertSee('https://line.me/R/msg/text/?', false);
    }
}
