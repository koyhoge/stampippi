<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\RallySeries;
use App\Models\StampLocation;
use App\Models\StampRally;
use App\Services\StampStorage\StampImageStorageManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RallyManageController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->canCreateRally(), 403, 'スタンプラリーの新規作成権限がありません。');

        if ($user->isAdmin()) {
            $groups = Group::orderBy('name')->get();
            $seriesList = RallySeries::with('group')->orderBy('title')->get();
        } else {
            $groups = $user->groups()->orderBy('name')->get();
            $groupIds = $groups->pluck('id');
            $seriesList = RallySeries::with('group')
                ->whereIn('group_id', $groupIds)
                ->orderBy('title')
                ->get();
        }

        $defaultSeriesId = $request->query('series_id') ?: null;
        $defaultGroupId = $request->query('group_id');
        if (! $defaultGroupId && $defaultSeriesId) {
            $defaultGroupId = $seriesList->firstWhere('id', (int) $defaultSeriesId)?->group_id;
        }

        $rally = new StampRally([
            'visibility' => 'public',
            'completion_condition' => 'all',
            'rally_series_id' => $defaultSeriesId,
            'group_id' => $defaultGroupId,
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
        abort_unless($user->canCreateRally(), 403, 'スタンプラリーの新規作成権限がありません。');

        $groupIds = $user->isAdmin()
            ? Group::pluck('id')->all()
            : $user->groups()->pluck('groups.id')->all();

        $seriesRule = ['nullable', 'exists:rally_series,id'];

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:stamp_rallies,slug'],
            'group_id' => ['nullable', Rule::in($groupIds)],
            'rally_series_id' => $seriesRule,
            'description' => ['nullable', 'string', 'max:3000'],
            'visibility' => ['required', Rule::in(['draft', 'public', 'unlisted'])],
            'completion_condition' => ['required', Rule::in(['all', 'count'])],
            'required_locations_count' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $series = null;
        if (! empty($validated['rally_series_id'])) {
            $series = RallySeries::findOrFail($validated['rally_series_id']);
            abort_unless($user->canManageSeries($series), 403, '選択されたラリーシリーズにスタンプラリーを追加する権限がありません。');
        }

        $resolvedGroupId = ($series && $series->group_id && ! $user->isAdmin())
            ? $series->group_id
            : (($validated['group_id'] ?? null) ?: $series?->group_id);

        $rally = StampRally::create([
            'creator_id' => $user->id,
            'group_id' => $resolvedGroupId,
            'rally_series_id' => ($validated['rally_series_id'] ?? null) ?: null,
            'slug' => strtolower($validated['slug']),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
            'completion_condition' => $validated['completion_condition'],
            'required_locations_count' => $validated['completion_condition'] === 'count'
                ? ($validated['required_locations_count'] ?? 1)
                : null,
            'starts_at' => ($validated['starts_at'] ?? null) ?: null,
            'ends_at' => ($validated['ends_at'] ?? null) ?: null,
        ]);

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', "スタンプラリー「{$rally->title}」を作成しました！続けて地図からスタンプロケーション（ベニュー）を追加しましょう。");
    }

    public function edit(Request $request, StampRally $rally): View
    {
        $user = $request->user();
        abort_unless($user->canManageRally($rally), 403, 'このスタンプラリーを編集する権限がありません。');

        $rally->load(['locations', 'series', 'group']);

        if ($user->isAdmin()) {
            $groups = Group::orderBy('name')->get();
            $seriesList = RallySeries::with('group')->orderBy('title')->get();
            $manageableRallyIds = StampRally::where('id', '!=', $rally->id)->pluck('id');
        } else {
            $groups = $user->groups()->orderBy('name')->get();
            $groupIds = $groups->pluck('id');
            $seriesList = RallySeries::with('group')
                ->whereIn('group_id', $groupIds)
                ->orderBy('title')
                ->get();

            $manageableRallyIds = StampRally::where('id', '!=', $rally->id)
                ->where(function ($q) use ($user, $groupIds) {
                    $q->where('creator_id', $user->id);
                    if ($groupIds->isNotEmpty()) {
                        $q->orWhereIn('group_id', $groupIds)
                            ->orWhereHas('series', fn ($sq) => $sq->whereIn('group_id', $groupIds));
                    }
                })
                ->pluck('id');
        }

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
        abort_unless($user->canManageRally($rally), 403, 'このスタンプラリーを更新する権限がありません。');

        $groupIds = $user->isAdmin()
            ? Group::pluck('id')->all()
            : $user->groups()->pluck('groups.id')->all();

        $seriesRule = ['nullable', 'exists:rally_series,id'];

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
            'rally_series_id' => $seriesRule,
            'description' => ['nullable', 'string', 'max:3000'],
            'visibility' => ['required', Rule::in(['draft', 'public', 'unlisted'])],
            'completion_condition' => ['required', Rule::in(['all', 'count'])],
            'required_locations_count' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $series = null;
        if (! empty($validated['rally_series_id'])) {
            $series = RallySeries::findOrFail($validated['rally_series_id']);
            abort_unless($user->canManageSeries($series), 403, '選択されたラリーシリーズにスタンプラリーを追加する権限がありません。');
        }

        $resolvedGroupId = ($series && $series->group_id && ! $user->isAdmin())
            ? $series->group_id
            : (($validated['group_id'] ?? null) ?: $series?->group_id);

        $rally->update([
            'group_id' => $resolvedGroupId,
            'rally_series_id' => ($validated['rally_series_id'] ?? null) ?: null,
            'slug' => strtolower($validated['slug']),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
            'completion_condition' => $validated['completion_condition'],
            'required_locations_count' => $validated['completion_condition'] === 'count'
                ? ($validated['required_locations_count'] ?? 1)
                : null,
            'starts_at' => ($validated['starts_at'] ?? null) ?: null,
            'ends_at' => ($validated['ends_at'] ?? null) ?: null,
        ]);

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', 'スタンプラリー基本情報を更新しました。');
    }

    public function storeLocation(
        Request $request,
        StampRally $rally,
        StampImageStorageManager $stampStorage
    ): RedirectResponse {
        abort_unless($request->user()->canManageRally($rally), 403, 'このスタンプラリーにベニューを追加する権限がありません。');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'stamp_icon_value' => ['nullable', Rule::in(array_keys(StampLocation::presetIcons()))],
            'stamp_icon_image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,gif,svg', 'max:2048'],
            'stamp_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $nextOrder = $validated['sort_order'] ?? (($rally->locations()->max('sort_order') ?? 0) + 1);

        if ($request->hasFile('stamp_icon_image')) {
            $iconType = 'custom';
            $iconValue = $stampStorage->store($request->file('stamp_icon_image'));
        } else {
            $iconType = 'preset';
            $iconValue = $validated['stamp_icon_value'] ?? 'star';
        }

        $rally->locations()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'latitude' => round((float) $validated['latitude'], 6),
            'longitude' => round((float) $validated['longitude'], 6),
            'radius_meters' => $validated['radius_meters'],
            'available_from' => ($validated['available_from'] ?? null) ?: null,
            'available_until' => ($validated['available_until'] ?? null) ?: null,
            'stamp_icon_type' => $iconType,
            'stamp_icon_value' => $iconValue,
            'stamp_color' => $validated['stamp_color'],
            'sort_order' => $nextOrder,
        ]);

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', "スタンプロケーション（ベニュー）「{$validated['name']}」を追加しました！");
    }

    public function updateLocation(
        Request $request,
        StampRally $rally,
        StampLocation $location,
        StampImageStorageManager $stampStorage
    ): RedirectResponse {
        abort_unless($request->user()->canManageRally($rally), 403, 'このベニューを更新する権限がありません。');
        abort_unless((int) $location->stamp_rally_id === (int) $rally->id, 404, '指定されたベニューはこのスタンプラリーに存在しません。');

        $allowedIconValues = array_merge(array_keys(StampLocation::presetIcons()), ['__custom__']);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:10000'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'stamp_icon_value' => ['required', Rule::in($allowedIconValues)],
            'stamp_icon_image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,gif,svg', 'max:2048'],
            'stamp_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $iconType = $location->stamp_icon_type;
        $iconValue = $location->stamp_icon_value;

        if ($request->hasFile('stamp_icon_image')) {
            if (
                $location->hasCustomImage()
                && ! StampLocation::where('id', '!=', $location->id)->where('stamp_icon_value', $location->stamp_icon_value)->exists()
            ) {
                $stampStorage->delete($location->stamp_icon_value);
            }
            $iconType = 'custom';
            $iconValue = $stampStorage->store($request->file('stamp_icon_image'));
        } elseif ($validated['stamp_icon_value'] !== '__custom__') {
            if (
                $location->hasCustomImage()
                && ! StampLocation::where('id', '!=', $location->id)->where('stamp_icon_value', $location->stamp_icon_value)->exists()
            ) {
                $stampStorage->delete($location->stamp_icon_value);
            }
            $iconType = 'preset';
            $iconValue = $validated['stamp_icon_value'];
        }

        $location->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'latitude' => round((float) $validated['latitude'], 6),
            'longitude' => round((float) $validated['longitude'], 6),
            'radius_meters' => $validated['radius_meters'],
            'available_from' => ($validated['available_from'] ?? null) ?: null,
            'available_until' => ($validated['available_until'] ?? null) ?: null,
            'stamp_icon_type' => $iconType,
            'stamp_icon_value' => $iconValue,
            'stamp_color' => $validated['stamp_color'],
            'sort_order' => $validated['sort_order'] ?? $location->sort_order,
        ]);

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', "ロケーション「{$location->name}」を更新しました。");
    }

    public function destroyLocation(
        Request $request,
        StampRally $rally,
        StampLocation $location,
        StampImageStorageManager $stampStorage
    ): RedirectResponse {
        abort_unless($request->user()->canManageRally($rally), 403, 'このベニューを削除する権限がありません。');
        abort_unless((int) $location->stamp_rally_id === (int) $rally->id, 404, '指定されたベニューはこのスタンプラリーに存在しません。');

        if (
            $location->hasCustomImage()
            && ! StampLocation::where('id', '!=', $location->id)->where('stamp_icon_value', $location->stamp_icon_value)->exists()
        ) {
            $stampStorage->delete($location->stamp_icon_value);
        }

        $name = $location->name;
        $location->delete();

        return redirect()->route('my.rallies.edit', $rally)
            ->with('status', "ロケーション「{$name}」を削除しました。");
    }

    public function copyLocation(Request $request, StampRally $rally): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageRally($rally), 403, 'このスタンプラリーにベニューを追加する権限がありません。');

        $validated = $request->validate([
            'source_location_id' => ['required', 'exists:stamp_locations,id'],
        ]);

        $source = StampLocation::with('rally')->findOrFail($validated['source_location_id']);
        abort_unless($user->canManageRally($source->rally), 403, 'コピー元のベニューを参照する権限がありません。');

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
