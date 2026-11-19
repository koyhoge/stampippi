<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\RallyParticipation;
use App\Models\RallySeries;
use App\Models\StampLocation;
use App\Models\StampRally;
use App\Models\User;
use App\Models\UserStamp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['handle' => 'admin'],
            [
                'name' => '管理者ユーザー',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ]
        );
        $admin->forceFill(['is_admin' => true])->save();

        $staff = User::updateOrCreate(
            ['handle' => 'group'],
            [
                'name' => 'グループメンバー',
                'email' => 'group@example.com',
                'password' => Hash::make('password'),
            ]
        );
        $staff->forceFill(['is_admin' => false])->save();

        $player = User::updateOrCreate(
            ['handle' => 'user'],
            [
                'name' => '一般ユーザー',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
            ]
        );
        $player->forceFill(['is_admin' => false])->save();

        // Group: PHPカンファレンス運営
        $group = Group::updateOrCreate(
            ['slug' => 'phpcon'],
            [
                'name' => 'PHPカンファレンススタンプラリー運営',
                'description' => '日本各地で開催されるPHPカンファレンスシリーズのスタンプラリーを共同運営するグループです。',
            ]
        );

        $group->members()->syncWithoutDetaching([
            $admin->id => ['role' => 'owner'],
            $staff->id => ['role' => 'member'],
        ]);

        // Rally Series: PHPカンファレンススタンプラリー
        $series = RallySeries::updateOrCreate(
            ['slug' => 'phpcon-stamp-rally'],
            [
                'group_id' => $group->id,
                'creator_id' => $admin->id,
                'title' => 'PHPカンファレンススタンプラリー',
                'description' => '日本全国で開催される各年のPHPカンファレンス会場（ベニュー）を巡る公式スタンプラリーシリーズです。',
                'visibility' => 'public',
            ]
        );

        // 2026 Edition
        $rally2026 = StampRally::updateOrCreate(
            ['slug' => 'phpcon-japan-2026'],
            [
                'group_id' => $group->id,
                'rally_series_id' => $series->id,
                'creator_id' => $admin->id,
                'title' => '日本全国PHPカンファレンススタンプラリー 2026',
                'description' => '2026年に全国各地で開催されるPHPカンファレンス会場でスタンプを集めよう！3会場以上でコンプリート達成証が発行されます。',
                'visibility' => 'public',
                'completion_condition' => 'count',
                'required_locations_count' => 3,
                'starts_at' => '2026-01-01 00:00:00',
                'ends_at' => '2026-12-31 23:59:59',
                'sort_order' => 1,
            ]
        );

        $venues2026 = [
            [
                'name' => 'PHPカンファレンス東京 2026 会場（大田区産業プラザPiO）',
                'description' => '京急蒲田駅より徒歩約3分。1F大展示ホール入口付近でチェックイン！',
                'latitude' => 35.5588810,
                'longitude' => 139.7240960,
                'radius_meters' => 200,
                'stamp_icon_value' => 'elephant',
                'stamp_color' => '#4F46E5',
                'sort_order' => 1,
            ],
            [
                'name' => 'PHPカンファレンス関西 2026 会場（グランフロント大阪）',
                'description' => '大阪・梅田エリア。カンファレンス受付フロア周辺でスタンプ獲得！',
                'latitude' => 34.7055050,
                'longitude' => 135.4943830,
                'radius_meters' => 250,
                'stamp_icon_value' => 'conference',
                'stamp_color' => '#EA580C',
                'sort_order' => 2,
            ],
            [
                'name' => 'PHPカンファレンス福岡 2026 会場（FFB HALL）',
                'description' => '博多駅徒歩圏内。会場ロビーでチェックイン！',
                'latitude' => 33.5898520,
                'longitude' => 130.4207310,
                'radius_meters' => 200,
                'stamp_icon_value' => 'star',
                'stamp_color' => '#059669',
                'sort_order' => 3,
            ],
            [
                'name' => 'PHPカンファレンス小田原 2026 会場（小田原三の丸ホール）',
                'description' => '小田原城すぐそば！城下町を楽しみながらスタンプを押そう。',
                'latitude' => 35.2506910,
                'longitude' => 139.1566030,
                'radius_meters' => 250,
                'stamp_icon_value' => 'castle',
                'stamp_color' => '#7C3AED',
                'sort_order' => 4,
            ],
        ];

        foreach ($venues2026 as $v) {
            StampLocation::updateOrCreate(
                [
                    'stamp_rally_id' => $rally2026->id,
                    'name' => $v['name'],
                ],
                $v
            );
        }

        // 2025 Edition under the same series
        $rally2025 = StampRally::updateOrCreate(
            ['slug' => 'phpcon-japan-2025'],
            [
                'group_id' => $group->id,
                'rally_series_id' => $series->id,
                'creator_id' => $staff->id,
                'title' => '日本全国PHPカンファレンススタンプラリー 2025',
                'description' => '2025年版の全国PHPカンファレンススタンプラリーです。',
                'visibility' => 'public',
                'completion_condition' => 'all',
                'starts_at' => '2025-01-01 00:00:00',
                'ends_at' => '2025-12-31 23:59:59',
                'sort_order' => 2,
            ]
        );

        StampLocation::updateOrCreate(
            [
                'stamp_rally_id' => $rally2025->id,
                'name' => 'PHPカンファレンス東京 2025 会場（大田区産業プラザPiO）',
            ],
            [
                'description' => '2025年東京会場',
                'latitude' => 35.5588810,
                'longitude' => 139.7240960,
                'radius_meters' => 200,
                'stamp_icon_value' => 'elephant',
                'stamp_color' => '#2563EB',
                'sort_order' => 1,
            ]
        );

        // Permanent (No Period) Personal Stamp Rally
        $permanentRally = StampRally::updateOrCreate(
            ['slug' => 'tokyo-station-landmarks'],
            [
                'group_id' => null,
                'rally_series_id' => null,
                'creator_id' => $admin->id,
                'title' => '中央線・東京街歩き常設スタンプラリー',
                'description' => '開催期間の制限がない常設スタンプラリーです。いつでも好きなタイミングでベニューを訪れてスタンプを集められます。',
                'visibility' => 'public',
                'completion_condition' => 'all',
                'starts_at' => null,
                'ends_at' => null,
                'sort_order' => 3,
            ]
        );

        $permanentVenues = [
            [
                'name' => '東中野駅前 ベニュー',
                'description' => '旧プロトタイプ(test_geo.php)ゆかりの座標スポット',
                'latitude' => 35.7061838,
                'longitude' => 139.6863336,
                'radius_meters' => 150,
                'stamp_icon_value' => 'train',
                'stamp_color' => '#D97706',
                'sort_order' => 1,
            ],
            [
                'name' => '東京駅 丸の内駅前広場',
                'description' => '赤レンガ駅舎前の広場',
                'latitude' => 35.6812360,
                'longitude' => 139.7671250,
                'radius_meters' => 200,
                'stamp_icon_value' => 'coffee',
                'stamp_color' => '#DC2626',
                'sort_order' => 2,
            ],
        ];

        foreach ($permanentVenues as $pv) {
            StampLocation::updateOrCreate(
                [
                    'stamp_rally_id' => $permanentRally->id,
                    'name' => $pv['name'],
                ],
                $pv
            );
        }

        // Seed 1 initial stamp for @participant on 2026 rally
        $locations2026 = $rally2026->locations()->orderBy('sort_order')->get();
        $firstLoc = $locations2026->first();
        if ($firstLoc) {
            RallyParticipation::firstOrCreate(
                ['user_id' => $player->id, 'stamp_rally_id' => $rally2026->id],
                ['joined_at' => now()->subDays(3)]
            );

            UserStamp::firstOrCreate(
                ['user_id' => $player->id, 'stamp_location_id' => $firstLoc->id],
                [
                    'stamp_rally_id' => $rally2026->id,
                    'checkin_lat' => $firstLoc->latitude,
                    'checkin_lng' => $firstLoc->longitude,
                    'accuracy_meters' => 10,
                    'distance_meters' => 0,
                    'stamped_at' => now()->subDays(3),
                ]
            );
        }

        // Seed 25 sample participants & check-ins for testing 10-by-10 icon pagination
        $sampleProfiles = [
            ['handle' => 'sample_alice', 'name' => 'Alice'],
            ['handle' => 'sample_bob', 'name' => 'Bob'],
            ['handle' => 'sample_chihiro', 'name' => 'Chihiro'],
            ['handle' => 'sample_daiki', 'name' => 'Daiki'],
            ['handle' => 'sample_emi', 'name' => 'Emi'],
            ['handle' => 'sample_fumiya', 'name' => 'Fumiya'],
            ['handle' => 'sample_gaku', 'name' => 'Gaku'],
            ['handle' => 'sample_haruka', 'name' => 'Haruka'],
            ['handle' => 'sample_ichiro', 'name' => 'Ichiro'],
            ['handle' => 'sample_jun', 'name' => 'Jun'],
            ['handle' => 'sample_kana', 'name' => 'Kana'],
            ['handle' => 'sample_leo', 'name' => 'Leo'],
            ['handle' => 'sample_mika', 'name' => 'Mika'],
            ['handle' => 'sample_naoki', 'name' => 'Naoki'],
            ['handle' => 'sample_ori', 'name' => 'Ori'],
            ['handle' => 'sample_piro', 'name' => 'Piro'],
            ['handle' => 'sample_qtaro', 'name' => 'Qtaro'],
            ['handle' => 'sample_rina', 'name' => 'Rina'],
            ['handle' => 'sample_sota', 'name' => 'Sota'],
            ['handle' => 'sample_tomo', 'name' => 'Tomo'],
            ['handle' => 'sample_umi', 'name' => 'Umi'],
            ['handle' => 'sample_vivi', 'name' => 'Vivi'],
            ['handle' => 'sample_wataru', 'name' => 'Wataru'],
            ['handle' => 'sample_yuki', 'name' => 'Yuki'],
            ['handle' => 'sample_zoe', 'name' => 'Zoe'],
        ];

        $samplePassword = Hash::make('password');
        $venue1 = $locations2026->get(0); // Tokyo: +25 (total 26)
        $venue2 = $locations2026->get(1); // Kansai: 15
        $venue3 = $locations2026->get(2); // Fukuoka: 8
        $venue4 = $locations2026->get(3); // Odawara: 3

        foreach ($sampleProfiles as $index => $profile) {
            $sampleUser = User::updateOrCreate(
                ['handle' => $profile['handle']],
                [
                    'name' => $profile['name'],
                    'email' => $profile['handle'] . '@example.com',
                    'password' => $samplePassword,
                ]
            );

            $timestamp = now()->subHours(48 - $index);

            RallyParticipation::firstOrCreate(
                ['user_id' => $sampleUser->id, 'stamp_rally_id' => $rally2026->id],
                ['joined_at' => $timestamp]
            );

            $checkins = [];
            if ($venue1) {
                $checkins[] = $venue1;
            }
            if ($venue2 && $index < 15) {
                $checkins[] = $venue2;
            }
            if ($venue3 && $index < 8) {
                $checkins[] = $venue3;
            }
            if ($venue4 && $index < 3) {
                $checkins[] = $venue4;
            }

            foreach ($checkins as $vOffset => $loc) {
                UserStamp::firstOrCreate(
                    ['user_id' => $sampleUser->id, 'stamp_location_id' => $loc->id],
                    [
                        'stamp_rally_id' => $rally2026->id,
                        'checkin_lat' => $loc->latitude,
                        'checkin_lng' => $loc->longitude,
                        'accuracy_meters' => 10,
                        'distance_meters' => 0,
                        'stamped_at' => $timestamp->copy()->addMinutes($vOffset * 5),
                    ]
                );
            }
        }
    }
}

