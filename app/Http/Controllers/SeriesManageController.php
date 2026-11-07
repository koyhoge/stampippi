<?php

namespace App\Http\Controllers;

use App\Models\RallySeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeriesManageController extends Controller
{
    public function create(Request $request): View
    {
        $groups = $request->user()->groups()->orderBy('name')->get();

        return view('manage.series.form', [
            'series' => new RallySeries(['visibility' => 'public']),
            'groups' => $groups,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $groupIds = $user->groups()->pluck('groups.id')->all();

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
        abort_unless($request->user()->canManageSeries($series), 403);

        $groups = $request->user()->groups()->orderBy('name')->get();
        $series->load('rallies.locations');

        return view('manage.series.form', [
            'series' => $series,
            'groups' => $groups,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, RallySeries $series): RedirectResponse
    {
        abort_unless($request->user()->canManageSeries($series), 403);

        $user = $request->user();
        $groupIds = $user->groups()->pluck('groups.id')->all();

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
