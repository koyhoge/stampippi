<?php

namespace App\Http\Controllers;

use App\Models\RallySeries;
use App\Models\StampLocation;
use App\Models\StampRally;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RallyManageController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();
        $groups = $user->groups()->orderBy('name')->get();
        $groupIds = $groups->pluck('id');

        $seriesList = RallySeries::where('creator_id', $user->id)
            ->when($groupIds->isNotEmpty(), fn ($q) => $q->orWhereIn('group_id', $groupIds))
            ->orderBy('title')
            ->get();

        $rally = new StampRally([
            'visibility' => 'public',
            'completion_condition' => 'all',
            'rally_series_id' => $request->query('series_id'),
            'group_id' => $request->query('group_id'),
        ]);

        return view('manage.rallies.form', [
            'rally' => $rally,
            'groups' => $groups,
            'seriesList' => $seriesList,
            'presetIcons' => StampLocation::presetIcons(),
            'copyableLocations' => collect(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $groupIds = $user->groups()->pluck('groups.id')->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:stamp_rallies,slug'],
            'group_id' => ['nullable', Rule::in($groupIds)],
            'rally_series_id' => ['nullable', 'exists:rally_series,id'],
            'description' => ['nullable', 'string', 'max:3000'],
            'visibility' => ['required', Rule::in(['draft', 'public', 'unlisted'])],
            'completion_condition' => ['required', Rule::in(['all', 'count'])],
            'required_locations_count' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if (! empty($validated['rally_series_id'])) {
            $series = RallySeries::findOrFail($validated['rally_series_id']);
            abort_unless($user->canManageSeries($series), 403);
        }

        $rally = StampRally::create([
            'creator_id' => $user->id,
            'group_id' => $validated['group_id'] ?: null,
            'rally_series_id' => $validated['rally_series_id'] ?: null,
            'slug' => strtolower($validated['slug']),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
            'completion_condition' => $validated['completion_condition'],
            'required_locations_count' => $validated['completion_condition'] === 'count'
                ? ($validated['required_locations_count'] ?? 1)
                : null,
            'starts_at' => $validated['starts_at'] ?: null,
            'ends_at' => $validated['ends_at'] ?: null,
        ]);

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', "スタンプラリー「{$rally->title}」を作成しました！続けて地図からスタンプロケーション（ベニュー）を追加しましょう。");
    }

    public function edit(Request $request, StampRally $rally): View
    {
        $user = $request->user();
        abort_unless($user->canManageRally($rally), 403);

        $rally->load(['locations', 'series', 'group']);

        $groups = $user->groups()->orderBy('name')->get();
        $groupIds = $groups->pluck('id');

        $seriesList = RallySeries::where('creator_id', $user->id)
            ->when($groupIds->isNotEmpty(), fn ($q) => $q->orWhereIn('group_id', $groupIds))
            ->orderBy('title')
            ->get();

        $manageableRallyIds = StampRally::where('id', '!=', $rally->id)
            ->where(function ($q) use ($user, $groupIds) {
                $q->where('creator_id', $user->id);
                if ($groupIds->isNotEmpty()) {
                    $q->orWhereIn('group_id', $groupIds);
                }
            })
            ->pluck('id');

        $copyableLocations = StampLocation::with('rally')
            ->whereIn('stamp_rally_id', $manageableRallyIds)
            ->orderBy('name')
            ->get();

        return view('manage.rallies.form', [
            'rally' => $rally,
            'groups' => $groups,
            'seriesList' => $seriesList,
            'presetIcons' => StampLocation::presetIcons(),
            'copyableLocations' => $copyableLocations,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, StampRally $rally): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageRally($rally), 403);

        $groupIds = $user->groups()->pluck('groups.id')->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('stamp_rallies', 'slug')->ignore($rally->id),
            ],
            'group_id' => ['nullable', Rule::in($groupIds)],
            'rally_series_id' => ['nullable', 'exists:rally_series,id'],
            'description' => ['nullable', 'string', 'max:3000'],
            'visibility' => ['required', Rule::in(['draft', 'public', 'unlisted'])],
            'completion_condition' => ['required', Rule::in(['all', 'count'])],
            'required_locations_count' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if (! empty($validated['rally_series_id'])) {
            $series = RallySeries::findOrFail($validated['rally_series_id']);
            abort_unless($user->canManageSeries($series), 403);
        }

        $rally->update([
            'group_id' => $validated['group_id'] ?: null,
            'rally_series_id' => $validated['rally_series_id'] ?: null,
            'slug' => strtolower($validated['slug']),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
            'completion_condition' => $validated['completion_condition'],
            'required_locations_count' => $validated['completion_condition'] === 'count'
                ? ($validated['required_locations_count'] ?? 1)
                : null,
            'starts_at' => $validated['starts_at'] ?: null,
            'ends_at' => $validated['ends_at'] ?: null,
        ]);

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', 'スタンプラリー基本情報を更新しました。');
    }

    public function storeLocation(Request $request, StampRally $rally): RedirectResponse
    {
        abort_unless($request->user()->canManageRally($rally), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'stamp_icon_value' => ['required', Rule::in(array_keys(StampLocation::presetIcons()))],
            'stamp_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $nextOrder = $validated['sort_order'] ?? (($rally->locations()->max('sort_order') ?? 0) + 1);

        $rally->locations()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'],
            'available_from' => $validated['available_from'] ?: null,
            'available_until' => $validated['available_until'] ?: null,
            'stamp_icon_type' => 'preset',
            'stamp_icon_value' => $validated['stamp_icon_value'],
            'stamp_color' => $validated['stamp_color'],
            'sort_order' => $nextOrder,
        ]);

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', "スタンプロケーション「{$validated['name']}」を追加しました！");
    }

    public function updateLocation(Request $request, StampRally $rally, StampLocation $location): RedirectResponse
    {
        abort_unless($request->user()->canManageRally($rally), 403);
        abort_unless((int) $location->stamp_rally_id === (int) $rally->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'stamp_icon_value' => ['required', Rule::in(array_keys(StampLocation::presetIcons()))],
            'stamp_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $location->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'],
            'available_from' => $validated['available_from'] ?: null,
            'available_until' => $validated['available_until'] ?: null,
            'stamp_icon_value' => $validated['stamp_icon_value'],
            'stamp_color' => $validated['stamp_color'],
            'sort_order' => $validated['sort_order'] ?? $location->sort_order,
        ]);

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', "ロケーション「{$location->name}」を更新しました。");
    }

    public function destroyLocation(Request $request, StampRally $rally, StampLocation $location): RedirectResponse
    {
        abort_unless($request->user()->canManageRally($rally), 403);
        abort_unless((int) $location->stamp_rally_id === (int) $rally->id, 404);

        $name = $location->name;
        $location->delete();

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', "ロケーション「{$name}」を削除しました。");
    }

    public function copyLocation(Request $request, StampRally $rally): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageRally($rally), 403);

        $validated = $request->validate([
            'source_location_id' => ['required', 'exists:stamp_locations,id'],
        ]);

        $source = StampLocation::with('rally')->findOrFail($validated['source_location_id']);
        abort_unless($user->canManageRally($source->rally), 403);

        $nextOrder = ($rally->locations()->max('sort_order') ?? 0) + 1;

        $rally->locations()->create([
            'name' => $source->name,
            'description' => $source->description,
            'latitude' => $source->latitude,
            'longitude' => $source->longitude,
            'radius_meters' => $source->radius_meters,
            'available_from' => null,
            'available_until' => null,
            'stamp_icon_type' => $source->stamp_icon_type,
            'stamp_icon_value' => $source->stamp_icon_value,
            'stamp_color' => $source->stamp_color,
            'sort_order' => $nextOrder,
        ]);

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', "過去ロケーション「{$source->name}」からベニュー情報をコピーして追加しました！");
    }
}
