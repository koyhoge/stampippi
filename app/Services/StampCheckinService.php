<?php

namespace App\Services;

use App\Models\RallyParticipation;
use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use App\Models\UserStamp;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Location\Coordinate;
use Location\Distance\Haversine;

class StampCheckinService
{
    /**
     * Calculate distance in meters between two GPS coordinates using phpgeo Haversine.
     */
    public function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $coord1 = new Coordinate($lat1, $lng1);
        $coord2 = new Coordinate($lat2, $lng2);
        $calculator = new Haversine();

        return (int) round($calculator->getDistance($coord1, $coord2));
    }

    /**
     * Attempt to check in and stamp a location for a user.
     *
     * @return array{
     *   success: bool,
     *   status: string,
     *   message: string,
     *   distance_meters?: int,
     *   radius_meters?: int,
     *   stamp?: UserStamp,
     *   is_rally_completed?: bool
     * }
     */
    public function attemptCheckin(
        User $user,
        StampRally $rally,
        StampLocation $location,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?Carbon $at = null
    ): array {
        $now = $at ?? now();

        if ((int) $location->stamp_rally_id !== (int) $rally->id) {
            return [
                'success' => false,
                'status' => 'invalid_location',
                'message' => '指定されたロケーションはこのスタンプラリーに含まれていません。',
            ];
        }

        if (! $rally->isCurrentlyActive($now)) {
            return [
                'success' => false,
                'status' => 'rally_out_of_period',
                'message' => 'このスタンプラリーは現在開催期間外です（' . $rally->periodLabel() . '）。',
            ];
        }

        if (! $location->isAvailableAt($now)) {
            return [
                'success' => false,
                'status' => 'location_out_of_period',
                'message' => "「{$location->name}」は現在打刻可能期間外です（{$location->availabilityLabel()}）。",
            ];
        }

        $distanceMeters = $this->calculateDistanceMeters(
            $latitude,
            $longitude,
            (float) $location->latitude,
            (float) $location->longitude
        );

        $existingStamp = UserStamp::where('user_id', $user->id)
            ->where('stamp_location_id', $location->id)
            ->first();

        if ($existingStamp) {
            return [
                'success' => true,
                'status' => 'already_stamped',
                'message' => "「{$location->name}」のスタンプは既に獲得済みです！",
                'distance_meters' => $distanceMeters,
                'radius_meters' => $location->radius_meters,
                'stamp' => $existingStamp,
                'is_rally_completed' => $this->isCompleted($user, $rally),
            ];
        }

        if ($distanceMeters > $location->radius_meters) {
            $diff = $distanceMeters - $location->radius_meters;

            return [
                'success' => false,
                'status' => 'too_far',
                'message' => "ベニューまで距離があります（現在地から約 {$distanceMeters}m / 判定半径 {$location->radius_meters}m）。あと約 {$diff}m 近づいてください。",
                'distance_meters' => $distanceMeters,
                'radius_meters' => $location->radius_meters,
            ];
        }

        return DB::transaction(function () use ($user, $rally, $location, $latitude, $longitude, $accuracy, $distanceMeters, $now) {
            $participation = RallyParticipation::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'stamp_rally_id' => $rally->id,
                ],
                [
                    'joined_at' => $now,
                ]
            );

            $stamp = UserStamp::create([
                'user_id' => $user->id,
                'stamp_rally_id' => $rally->id,
                'stamp_location_id' => $location->id,
                'checkin_lat' => $latitude,
                'checkin_lng' => $longitude,
                'accuracy_meters' => $accuracy,
                'distance_meters' => $distanceMeters,
                'stamped_at' => $now,
            ]);

            $stampedCount = UserStamp::where('user_id', $user->id)
                ->where('stamp_rally_id', $rally->id)
                ->count();

            $requiredCount = $rally->requiredCount();
            $isCompleted = $requiredCount > 0 && $stampedCount >= $requiredCount;

            if ($isCompleted && ! $participation->completed_at) {
                $participation->update(['completed_at' => $now]);
            }

            return [
                'success' => true,
                'status' => 'stamped',
                'message' => $isCompleted
                    ? "🎉 「{$location->name}」のスタンプを獲得し、スタンプラリーをコンプリートしました！"
                    : "💮 「{$location->name}」のスタンプを獲得しました！（中心から {$distanceMeters}m）",
                'distance_meters' => $distanceMeters,
                'radius_meters' => $location->radius_meters,
                'stamp' => $stamp,
                'is_rally_completed' => $isCompleted,
            ];
        });
    }

    public function isCompleted(User $user, StampRally $rally): bool
    {
        return RallyParticipation::where('user_id', $user->id)
            ->where('stamp_rally_id', $rally->id)
            ->whereNotNull('completed_at')
            ->exists();
    }
}

