<?php

namespace Tests\Feature;

use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VenueLocationGeocodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocode_requires_authentication(): void
    {
        $response = $this->getJson(route('my.rallies.geocode', ['q' => '35.6812,139.7671']));
        $response->assertUnauthorized();
    }

    public function test_geocode_with_empty_query_returns_empty_array(): void
    {
        $user = User::create([
            'handle' => 'geo_tester',
            'name' => 'Geo Tester',
            'email' => 'geo_tester@example.com',
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->getJson(route('my.rallies.geocode', ['q' => '']));
        $response->assertOk();
        $response->assertExactJson([]);
    }

    public function test_geocode_parses_direct_coordinates(): void
    {
        $user = User::create([
            'handle' => 'geo_tester2',
            'name' => 'Geo Tester 2',
            'email' => 'geo_tester2@example.com',
            'password' => 'password',
        ]);

        // Standard comma separated
        $res1 = $this->actingAs($user)->getJson(route('my.rallies.geocode', ['q' => '35.681236, 139.767125']));
        $res1->assertOk();
        $data1 = $res1->json();
        $this->assertCount(1, $data1);
        $this->assertSame(35.681236, $data1[0]['lat']);
        $this->assertSame(139.767125, $data1[0]['lng']);

        // Google Maps @lat,lng format
        $res2 = $this->actingAs($user)->getJson(route('my.rallies.geocode', ['q' => '@35.681236,139.767125']));
        $res2->assertOk();
        $data2 = $res2->json();
        $this->assertSame(35.681236, $data2[0]['lat']);
        $this->assertSame(139.767125, $data2[0]['lng']);

        // Japanese comma (、)
        $res3 = $this->actingAs($user)->getJson(route('my.rallies.geocode', ['q' => '35.681236、139.767125']));
        $res3->assertOk();
        $data3 = $res3->json();
        $this->assertSame(35.681236, $data3[0]['lat']);
        $this->assertSame(139.767125, $data3[0]['lng']);

        // Swapped (lon, lat) where lon > 90
        $res4 = $this->actingAs($user)->getJson(route('my.rallies.geocode', ['q' => '139.767125, 35.681236']));
        $res4->assertOk();
        $data4 = $res4->json();
        $this->assertSame(35.681236, $data4[0]['lat']);
        $this->assertSame(139.767125, $data4[0]['lng']);
    }

    public function test_geocode_with_external_api_mock(): void
    {
        $user = User::create([
            'handle' => 'geo_tester3',
            'name' => 'Geo Tester 3',
            'email' => 'geo_tester3@example.com',
            'password' => 'password',
        ]);

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '35.681236',
                    'lon' => '139.767125',
                    'display_name' => '東京駅, 東京都千代田区',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson(route('my.rallies.geocode', ['q' => '東京駅']));
        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertSame(35.681236, $data[0]['lat']);
        $this->assertSame(139.767125, $data[0]['lng']);
        $this->assertSame('東京駅, 東京都千代田区', $data[0]['display_name']);
    }

    public function test_geocode_fallback_to_gsi_when_nominatim_fails(): void
    {
        $user = User::create([
            'handle' => 'geo_tester4',
            'name' => 'Geo Tester 4',
            'email' => 'geo_tester4@example.com',
            'password' => 'password',
        ]);

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response('Access denied', 403),
            'msearch.gsi.go.jp/*' => Http::response([
                [
                    'geometry' => [
                        'coordinates' => [139.723923, 35.558529],
                    ],
                    'properties' => [
                        'title' => '東京都大田区南蒲田一丁目',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson(route('my.rallies.geocode', ['q' => '南蒲田']));
        $response->assertOk();
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertSame(35.558529, $data[0]['lat']);
        $this->assertSame(139.723923, $data[0]['lng']);
        $this->assertSame('東京都大田区南蒲田一丁目', $data[0]['display_name']);
    }

    public function test_venue_store_validates_coordinates_and_rejects_null_or_invalid(): void
    {
        $user = User::create([
            'handle' => 'venue_tester',
            'name' => 'Venue Tester',
            'email' => 'venue_tester@example.com',
            'password' => 'password',
        ]);

        $rally = StampRally::create([
            'creator_id' => $user->id,
            'title' => 'ベニューテストラリー',
            'slug' => 'venue-test-rally',
            'visibility' => 'public',
            'completion_condition' => 'all',
        ]);

        // Null latitude should fail validation
        $response1 = $this->actingAs($user)->post(route('my.rallies.locations.store', $rally), [
            'name' => 'テストベニュー',
            'latitude' => null,
            'longitude' => 139.7671,
            'radius_meters' => 100,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'star',
        ]);
        $response1->assertSessionHasErrors(['latitude']);

        // Empty string latitude should fail validation
        $response2 = $this->actingAs($user)->post(route('my.rallies.locations.store', $rally), [
            'name' => 'テストベニュー',
            'latitude' => '',
            'longitude' => 139.7671,
            'radius_meters' => 100,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'star',
        ]);
        $response2->assertSessionHasErrors(['latitude']);

        // NaN string should fail validation
        $response3 = $this->actingAs($user)->post(route('my.rallies.locations.store', $rally), [
            'name' => 'テストベニュー',
            'latitude' => 'NaN',
            'longitude' => 139.7671,
            'radius_meters' => 100,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'star',
        ]);
        $response3->assertSessionHasErrors(['latitude']);

        // Out of bounds latitude (> 90) should fail validation
        $response4 = $this->actingAs($user)->post(route('my.rallies.locations.store', $rally), [
            'name' => 'テストベニュー',
            'latitude' => 139.7671,
            'longitude' => 35.6812,
            'radius_meters' => 100,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'star',
        ]);
        $response4->assertSessionHasErrors(['latitude']);

        // Valid coordinates should succeed
        $response5 = $this->actingAs($user)->post(route('my.rallies.locations.store', $rally), [
            'name' => '正常ベニュー',
            'latitude' => 35.681236,
            'longitude' => 139.767125,
            'radius_meters' => 100,
            'stamp_color' => '#6366F1',
            'stamp_icon_value' => 'star',
        ]);
        $response5->assertRedirect(route('my.rallies.edit', $rally));
        $response5->assertSessionHasNoErrors();

        $location = StampLocation::where('name', '正常ベニュー')->firstOrFail();
        $this->assertEquals(35.681236, (float) $location->latitude);
        $this->assertEquals(139.767125, (float) $location->longitude);
    }
}
