<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\RallySeries;
use App\Models\StampRally;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $participations = $user->participations()
            ->with(['rally.locations', 'rally.series', 'rally.group'])
            ->latest('joined_at')
            ->get();

        $stampsByRally = $user->stamps()
            ->with('location')
            ->orderByDesc('stamped_at')
            ->get()
            ->groupBy('stamp_rally_id');

        $publicRallies = StampRally::with(['group', 'series', 'locations'])
            ->where('visibility', 'public')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get();

        return view('dashboard.index', compact(
            'user',
            'participations',
            'stampsByRally',
            'publicRallies'
        ));
    }

    public function manage(Request $request): View
    {
        $user = $request->user()->load('groups');
        abort_unless($user->canAccessManagement(), 403, '運営・管理画面へのアクセス権限がありません。');

        if ($user->isAdmin()) {
            $manageableGroups = Group::withCount(['members', 'series', 'rallies'])
                ->orderBy('name')
                ->get();

            $manageableSeries = RallySeries::with(['group', 'rallies'])
                ->latest()
                ->get();

            $manageableRallies = StampRally::with(['group', 'series', 'locations'])
                ->latest()
                ->get();

            $adminUsers = User::where('is_admin', true)
                ->orderBy('id')
                ->get();
        } else {
            $manageableGroups = $user->groups()
                ->withCount(['members', 'series', 'rallies'])
                ->orderBy('name')
                ->get();

            $groupIds = $manageableGroups->pluck('id');

            $manageableSeries = RallySeries::with(['group', 'rallies'])
                ->whereIn('group_id', $groupIds)
                ->latest()
                ->get();

            $manageableRallies = StampRally::with(['group', 'series', 'locations'])
                ->where(function ($q) use ($user, $groupIds) {
                    $q->where('creator_id', $user->id);
                    if ($groupIds->isNotEmpty()) {
                        $q->orWhereIn('group_id', $groupIds)
                            ->orWhereHas('series', fn ($sq) => $sq->whereIn('group_id', $groupIds));
                    }
                })
                ->latest()
                ->get();

            $adminUsers = collect();
        }

        return view('manage.index', compact(
            'user',
            'manageableGroups',
            'manageableSeries',
            'manageableRallies',
            'adminUsers'
        ));
    }

    public function grantAdmin(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = ltrim(trim($validated['identifier']), '@');

        $targetUser = User::where('handle', strtolower($identifier))
            ->orWhere('email', strtolower($identifier))
            ->first();

        if (! $targetUser) {
            return back()->withErrors([
                'identifier' => "「{$validated['identifier']}」に一致する登録済みユーザーが見つかりません。",
            ])->withInput();
        }

        $targetUser->update(['is_admin' => true]);

        return back()->with('status', "{$targetUser->name} (@{$targetUser->handle}) さんを管理者に設定しました。シリーズやスタンプラリーを作成できるようになりました。");
    }

    public function revokeAdmin(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if (User::where('is_admin', true)->count() <= 1 && $user->isAdmin()) {
            return back()->withErrors([
                'identifier' => '最後の管理者を解除することはできません。',
            ]);
        }

        $user->update(['is_admin' => false]);

        return back()->with('status', "@{$user->handle} さんの管理者権限を解除しました。");
    }
}
