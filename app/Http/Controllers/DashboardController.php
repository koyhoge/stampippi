<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\RallyParticipation;
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
        $keyword = trim((string) $request->query('q', ''));
        $showArchived = $request->boolean('archived');
        $now = now();

        $allParticipations = $user->participations()
            ->with(['rally.locations', 'rally.series', 'rally.group'])
            ->latest('joined_at')
            ->get();

        $archivedCount = $allParticipations->filter(fn (RallyParticipation $p) => $p->isArchived())->count();
        $allParticipationsCount = $allParticipations->count();
        $activeParticipationsCount = $allParticipationsCount - $archivedCount;

        $participations = $showArchived
            ? $allParticipations
            : $allParticipations->reject(fn (RallyParticipation $p) => $p->isArchived())->values();

        $userStamps = $user->stamps()
            ->with('location')
            ->orderByDesc('stamped_at')
            ->get();

        $totalStampsCount = $userStamps->count();
        $stampsByRally = $userStamps->groupBy('stamp_rally_id');

        $publicQuery = StampRally::with(['group', 'series'])
            ->withCount('locations')
            ->where('visibility', 'public')
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });

        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $publicQuery->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhereHas('series', fn ($sq) => $sq->where('title', 'like', $like))
                    ->orWhereHas('group', fn ($gq) => $gq->where('name', 'like', $like))
                    ->orWhereHas('locations', fn ($lq) => $lq->where('name', 'like', $like));
            });
        }

        $totalPublicActiveCount = (clone $publicQuery)->count();

        $publicRallies = $publicQuery
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return view('dashboard.index', compact(
            'user',
            'participations',
            'allParticipationsCount',
            'activeParticipationsCount',
            'archivedCount',
            'showArchived',
            'totalStampsCount',
            'stampsByRally',
            'publicRallies',
            'totalPublicActiveCount',
            'keyword'
        ));
    }

    public function stampMap(Request $request): View
    {
        $user = $request->user();

        $stamps = $user->stamps()
            ->with(['location', 'rally.series'])
            ->orderByDesc('stamped_at')
            ->get()
            ->filter(fn ($stamp) => $stamp->location && $stamp->rally)
            ->values();

        $mapStamps = $stamps->map(function ($stamp) {
            return [
                'id' => $stamp->id,
                'location_id' => $stamp->location->id,
                'location_name' => $stamp->location->name,
                'latitude' => (float) $stamp->location->latitude,
                'longitude' => (float) $stamp->location->longitude,
                'icon_emoji' => $stamp->location->iconEmoji(),
                'stamp_color' => $stamp->location->stamp_color,
                'stamped_at' => $stamp->stamped_at->format('Y/m/d H:i'),
                'distance_meters' => (int) $stamp->distance_meters,
                'rally_title' => $stamp->rally->title,
                'rally_url' => route('rallies.show', $stamp->rally),
                'series_title' => $stamp->rally->series?->title,
            ];
        })->values();

        $rallyCount = $stamps->pluck('stamp_rally_id')->unique()->count();

        return view('dashboard.map', compact(
            'user',
            'stamps',
            'mapStamps',
            'rallyCount'
        ));
    }

    public function toggleArchive(Request $request, StampRally $rally): RedirectResponse
    {
        $participation = RallyParticipation::where('user_id', $request->user()->id)
            ->where('stamp_rally_id', $rally->id)
            ->firstOrFail();

        if ($participation->archived_at) {
            $participation->update(['archived_at' => null]);

            return back()->with('status', "「{$rally->title}」のアーカイブを解除し、参加中一覧に戻しました。");
        }

        $participation->update(['archived_at' => now()]);

        return back()->with('status', "「{$rally->title}」をアーカイブしました。「アーカイブ済も含めてすべて表示」からいつでも確認・解除できます。");
    }

    public function manage(Request $request): View
    {
        $user = $request->user()->load('groups');
        abort_unless($user->canAccessManagement(), 403, '運営・管理画面へのアクセス権限がありません。');

        if ($user->isAdmin()) {
            $manageableSeries = RallySeries::with(['group', 'rallies'])
                ->latest()
                ->get();

            $manageableRallies = StampRally::with(['group', 'series', 'locations'])
                ->latest()
                ->get();
        } else {
            $groupIds = $user->groups->pluck('id');

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
        }

        return view('manage.index', compact(
            'user',
            'manageableSeries',
            'manageableRallies'
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
