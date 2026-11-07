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
        $user = $request->user()->load('organizations');
        $orgIds = $user->organizations->pluck('id');

        $participations = $user->participations()
            ->with(['rally.locations', 'rally.series', 'rally.organization'])
            ->latest('joined_at')
            ->get();

        $stampsByRally = $user->stamps()
            ->with('location')
            ->orderByDesc('stamped_at')
            ->get()
            ->groupBy('stamp_rally_id');

        $manageableSeries = RallySeries::with(['organization', 'rallies'])
            ->where(function ($q) use ($user, $orgIds) {
                $q->where('creator_id', $user->id);
                if ($orgIds->isNotEmpty()) {
                    $q->orWhereIn('organization_id', $orgIds);
                }
            })
            ->latest()
            ->get();

        $manageableRallies = StampRally::with(['organization', 'series', 'locations'])
            ->where(function ($q) use ($user, $orgIds) {
                $q->where('creator_id', $user->id);
                if ($orgIds->isNotEmpty()) {
                    $q->orWhereIn('organization_id', $orgIds);
                }
            })
            ->latest()
            ->get();

        return view('dashboard.index', compact(
            'user',
            'participations',
            'stampsByRally',
            'manageableSeries',
            'manageableRallies'
        ));
    }
}

