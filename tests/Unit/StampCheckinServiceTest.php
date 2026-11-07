<?php

namespace Tests\Unit;

use App\Services\StampCheckinService;
use PHPUnit\Framework\TestCase;

class StampCheckinServiceTest extends TestCase
{
    public function test_haversine_distance_calculation_matches_prototype(): void
    {
        $service = new StampCheckinService();

        // Coordinates from old/src/test_geo.php (Higashi-Nakano area, ~21 meters apart)
        $distance = $service->calculateDistanceMeters(
            35.706183773092164,
            139.68633360267796,
            35.70599793998768,
            139.68638304503946
        );

        $this->assertGreaterThanOrEqual(20, $distance);
        $this->assertLessThanOrEqual(22, $distance);
    }
}

