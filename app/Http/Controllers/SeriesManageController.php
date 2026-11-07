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
        $organizations = $request->user()->organizations()->orderBy('name')->get();

        return view('manage.series.form', [
            'series' => new RallySeries(['visibility' => 'public']),
            'organizations' => $organizations,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $orgIds = $user->organizations()->pluck('organizations.id')->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:rally_series,slug'],
            'organization_id' => ['nullable', Rule::in($orgIds)],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(['draft', 'public', 'unlisted'])],
        ]);

        $series = RallySeries::create([
            'creator_id' => $user->id,
            'organization_id' => $validated['organization_id'] ?: null,
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

        $organizations = $request->user()->organizations()->orderBy('name')->get();
        $series->load('rallies.locations');

        return view('manage.series.form', [
            'series' => $series,
            'organizations' => $organizations,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, RallySeries $series): RedirectResponse
    {
        abort_unless($request->user()->canManageSeries($series), 403);

        $user = $request->user();
        $orgIds = $user->organizations()->pluck('organizations.id')->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('rally_series', 'slug')->ignore($series->id),
            ],
            'organization_id' => ['nullable', Rule::in($orgIds)],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(['draft', 'public', 'unlisted'])],
        ]);

        $series->update([
            'organization_id' => $validated['organization_id'] ?: null,
            'slug' => strtolower($validated['slug']),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
        ]);

        return redirect()->route('my.series.edit', $series)
            ->with('status', 'ラリーシリーズを更新しました。');
    }
}

