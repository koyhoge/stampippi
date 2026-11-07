<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\RallySeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeriesManageController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->canCreateSeries(), 403, 'ラリーシリーズの新規作成は管理者のみ行えます。');

        $groups = $user->isAdmin()
            ? Group::orderBy('name')->get()
            : $user->groups()->orderBy('name')->get();

        return view('manage.series.form', [
            'series' => new RallySeries(['visibility' => 'public']),
            'groups' => $groups,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canCreateSeries(), 403, 'ラリーシリーズの新規作成は管理者のみ行えます。');

        $groupIds = $user->isAdmin()
            ? Group::pluck('id')->all()
            : $user->groups()->pluck('groups.id')->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:rally_series,slug'],
            'group_id' => ['nullable', Rule::in($groupIds)],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(['draft', 'public', 'unlisted'])],
        ]);

        $series = RallySeries::create([
            'creator_id' => $user->id,
            'group_id' => $validated['group_id'] ?: null,
            'slug' => strtolower($validated['slug']),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
        ]);

        return redirect()->route('my.series.edit', $series)
            ->with('status', "ラリーシリーズ「{$series->title}」を作成しました！");
    }

    public function edit(Request $request, RallySeries $series): View
    {
        $user = $request->user();
        abort_unless($user->canManageSeries($series), 403, 'このラリーシリーズを編集する権限がありません。');

        $groups = $user->isAdmin()
            ? Group::orderBy('name')->get()
            : $user->groups()->orderBy('name')->get();

        $series->load('rallies.locations');

        return view('manage.series.form', [
            'series' => $series,
            'groups' => $groups,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, RallySeries $series): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageSeries($series), 403, 'このラリーシリーズを更新する権限がありません。');

        $groupIds = $user->isAdmin()
            ? Group::pluck('id')->all()
            : $user->groups()->pluck('groups.id')->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('rally_series', 'slug')->ignore($series->id),
            ],
            'group_id' => ['nullable', Rule::in($groupIds)],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(['draft', 'public', 'unlisted'])],
        ]);

        $series->update([
            'group_id' => $validated['group_id'] ?: null,
            'slug' => strtolower($validated['slug']),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
        ]);

        return redirect()->route('my.series.edit', $series)
            ->with('status', 'ラリーシリーズを更新しました。');
    }
}
