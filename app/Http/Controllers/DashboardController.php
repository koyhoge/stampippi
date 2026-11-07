<?php

namespace App\Http\Controllers;

use App\Models\RallySeries;
use App\Models\StampRally;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('groups');
        $groupIds = $user->groups->pluck('id');

        $participations = $user->participations()
            ->with(['rally.locations', 'rally.series', 'rally.group'])
            ->latest('joined_at')
            ->get();

        $stampsByRally = $user->stamps()
            ->with('location')
            ->orderByDesc('stamped_at')
            ->get()
            ->groupBy('stamp_rally_id');

        $manageableSeries = RallySeries::with(['group', 'rallies'])
            ->where(function ($q) use ($user, $groupIds) {
                $q->where('creator_id', $user->id);
                if ($groupIds->isNotEmpty()) {
                    $q->orWhereIn('group_id', $groupIds);
                }
            })
            ->latest()
            ->get();

        $manageableRallies = StampRally::with(['group', 'series', 'locations'])
            ->where(function ($q) use ($user, $groupIds) {
                $q->where('creator_id', $user->id);
                if ($groupIds->isNotEmpty()) {
                    $q->orWhereIn('group_id', $groupIds);
                }
            })
            ->latest()
            ->get();

        $publicRallies = StampRally::with(['group', 'series', 'locations'])
            ->where('visibility', 'public')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();

        return view('dashboard.index', compact(
            'user',
            'participations',
            'stampsByRally',
            'manageableSeries',
            'manageableRallies',
            'publicRallies'
        ));
    }
}
