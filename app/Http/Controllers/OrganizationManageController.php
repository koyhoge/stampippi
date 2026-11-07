<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationManageController extends Controller
{
    public function create(): View
    {
        return view('manage.organizations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:48', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:organizations,slug'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $organization = Organization::create([
            'slug' => strtolower($validated['slug']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $organization->members()->attach($request->user()->id, ['role' => 'owner']);

        return redirect()->route('my.organizations.edit', $organization)
            ->with('status', "Organization「{$organization->name}」を作成しました！");
    }

    public function edit(Request $request, Organization $organization): View
    {
        abort_unless($request->user()->canManageOrganization($organization), 403);

        $organization->load(['members', 'series', 'rallies']);

        return view('manage.organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($request->user()->canManageOrganization($organization), 403);

        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:48',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('organizations', 'slug')->ignore($organization->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $organization->update([
            'slug' => strtolower($validated['slug']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('my.organizations.edit', $organization)
            ->with('status', 'Organization 情報を更新しました。');
    }

    public function addMember(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOfOrganization($organization), 403, 'メンバーの追加は Organization オーナーのみ行えます。');

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['owner', 'member'])],
        ]);

        $identifier = ltrim(trim($validated['identifier']), '@');

        $targetUser = User::where('handle', strtolower($identifier))
            ->orWhere('email', strtolower($identifier))
            ->first();

        if (! $targetUser) {
            return back()->withErrors([
                'identifier' => "「{$validated['identifier']}」に一致する登録済みユーザーが見つかりません。先にサービスへログインしてもらってください。",
            ])->withInput();
        }

        $organization->members()->syncWithoutDetaching([
            $targetUser->id => ['role' => $validated['role']],
        ]);

        $organization->members()->updateExistingPivot($targetUser->id, [
            'role' => $validated['role'],
        ]);

        return back()->with('status', "{$targetUser->name} (@{$targetUser->handle}) さんを {$validated['role']} として追加しました。");
    }

    public function removeMember(Request $request, Organization $organization, User $user): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOfOrganization($organization), 403);

        $ownerCount = $organization->members()->wherePivot('role', 'owner')->count();
        $isTargetOwner = $organization->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();

        if ($isTargetOwner && $ownerCount <= 1) {
            return back()->withErrors([
                'identifier' => '最後のオーナーを削除することはできません。',
            ]);
        }

        $organization->members()->detach($user->id);

        return back()->with('status', "@{$user->handle} さんをメンバーから外しました。");
    }
}

