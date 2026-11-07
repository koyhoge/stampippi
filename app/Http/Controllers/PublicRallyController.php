<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\RallyParticipation;
use App\Models\RallySeries;
use App\Models\StampLocation;
use App\Models\StampRally;
use App\Services\StampCheckinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicRallyController extends Controller
{
    public function home(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        $devUsers = app()->environment('local', 'testing')
            ? \App\Models\User::orderBy('id')->get()
            : collect();

        return view('public.home', compact('devUsers'));
    }

    public function showOrganization(Organization $organization): View
    {
        $organization->load([
            'members',
            'series' => fn ($q) => $q->where('visibility', 'public')->with('rallies'),
            'rallies' => fn ($q) => $q->where('visibility', 'public')->with(['locations', 'series']),
        ]);

        return view('public.organization', compact('organization'));
    }

    public function showSeries(Request $request, RallySeries $series): View
    {
        if ($series->visibility === 'draft' && (! $request->user() || ! $request->user()->canManageSeries($series))) {
            abort(404);
        }

        $series->load([
            'organization',
            'creator',
            'rallies' => function ($query) use ($request, $series) {
                if (! $request->user() || ! $request->user()->canManageSeries($series)) {
                    $query->where('visibility', '!=', 'draft');
                }
                $query->with('locations');
            },
        ]);

        $userParticipations = collect();
        $userStampCounts = collect();

        if ($request->user()) {
            $rallyIds = $series->rallies->pluck('id');
            $userParticipations = RallyParticipation::where('user_id', $request->user()->id)
                ->whereIn('stamp_rally_id', $rallyIds)
                ->get()
                ->keyBy('stamp_rally_id');

            $userStampCounts = $request->user()->stamps()
                ->whereIn('stamp_rally_id', $rallyIds)
                ->selectRaw('stamp_rally_id, count(*) as total')
                ->groupBy('stamp_rally_id')
                ->pluck('total', 'stamp_rally_id');
        }

        return view('public.series', compact('series', 'userParticipations', 'userStampCounts'));
    }

    public function showRally(Request $request, StampRally $rally): View
    {
        if ($rally->visibility === 'draft' && (! $request->user() || ! $request->user()->canManageRally($rally))) {
            abort(404);
        }

        $rally->load(['organization', 'series.organization', 'creator', 'locations']);

        $participation = null;
        $userStampsByLocation = collect();

        if ($request->user()) {
            $participation = RallyParticipation::where('user_id', $request->user()->id)
                ->where('stamp_rally_id', $rally->id)
                ->first();

            $userStampsByLocation = $request->user()->stamps()
                ->where('stamp_rally_id', $rally->id)
                ->get()
                ->keyBy('stamp_location_id');
        }

        $mapLocations = $rally->locations->map(function (StampLocation $loc) use ($userStampsByLocation) {
            $stamped = $userStampsByLocation->get($loc->id);

            return [
                'id' => $loc->id,
                'name' => $loc->name,
                'description' => $loc->description,
                'latitude' => (float) $loc->latitude,
                'longitude' => (float) $loc->longitude,
                'radius_meters' => (int) $loc->radius_meters,
                'availability_label' => $loc->availabilityLabel(),
                'is_available_now' => $loc->isAvailableAt(),
                'icon_emoji' => $loc->iconEmoji(),
                'stamp_color' => $loc->stamp_color,
                'is_stamped' => (bool) $stamped,
                'stamped_at' => $stamped ? $stamped->stamped_at->format('Y/m/d H:i') : null,
            ];
        })->values();

        return view('public.rally', compact(
            'rally',
            'participation',
            'userStampsByLocation',
            'mapLocations'
        ));
    }

    public function joinRally(Request $request, StampRally $rally): RedirectResponse
    {
        if (! $rally->isCurrentlyActive()) {
            return back()->withErrors([
                'checkin' => 'このスタンプラリーは現在開催期間外のため参加できません。',
            ]);
        }

        RallyParticipation::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'stamp_rally_id' => $rally->id,
            ],
            [
                'joined_at' => now(),
            ]
        );

        return redirect()->route('rallies.show', $rally)
            ->with('status', "「{$rally->title}」への参加を開始しました！ベニューの近くでスタンプを押しましょう。");
    }

    public function checkin(
        Request $request,
        StampRally $rally,
        StampLocation $location,
        StampCheckinService $checkinService
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $result = $checkinService->attemptCheckin(
            $request->user(),
            $rally,
            $location,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            isset($validated['accuracy']) ? (float) $validated['accuracy'] : null
        );

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if (! $result['success']) {
            return back()->withErrors(['checkin' => $result['message']]);
        }

        return redirect()->route('rallies.show', $rally)
            ->with('status', $result['message']);
    }

    public function certificate(Request $request, StampRally $rally): View|RedirectResponse
    {
        $rally->load(['organization', 'series', 'locations']);

        $participation = RallyParticipation::where('user_id', $request->user()->id)
            ->where('stamp_rally_id', $rally->id)
            ->first();

        if (! $participation || ! $participation->completed_at) {
            return redirect()->route('rallies.show', $rally)
                ->withErrors(['checkin' => 'まだこのスタンプラリーの達成条件を満たしていません。']);
        }

        $userStamps = $request->user()->stamps()
            ->with('location')
            ->where('stamp_rally_id', $rally->id)
            ->orderBy('stamped_at')
            ->get();

        return view('public.certificate', compact('rally', 'participation', 'userStamps'));
    }
}

