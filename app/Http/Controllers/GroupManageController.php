<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupManageController extends Controller
{
    public function create(): View
    {
        return view('manage.groups.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:48', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:groups,slug'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $group = Group::create([
            'slug' => strtolower($validated['slug']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $group->members()->attach($request->user()->id, ['role' => 'owner']);

        return redirect()->route('my.groups.edit', $group)
            ->with('status', "グループ「{$group->name}」を作成しました！");
    }

    public function edit(Request $request, Group $group): View
    {
        abort_unless($request->user()->canManageGroup($group), 403);

        $group->load(['members', 'series', 'rallies']);

        return view('manage.groups.edit', compact('group'));
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()->canManageGroup($group), 403);

        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:48',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('groups', 'slug')->ignore($group->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $group->update([
            'slug' => strtolower($validated['slug']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('my.groups.edit', $group)
            ->with('status', 'グループ情報を更新しました。');
    }

    public function addMember(Request $request, Group $group): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOfGroup($group), 403, 'メンバーの追加はグループオーナーのみ行えます。');

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

        $group->members()->syncWithoutDetaching([
            $targetUser->id => ['role' => $validated['role']],
        ]);

        $group->members()->updateExistingPivot($targetUser->id, [
            'role' => $validated['role'],
        ]);

        return back()->with('status', "{$targetUser->name} (@{$targetUser->handle}) さんを {$validated['role']} として追加しました。");
    }

    public function removeMember(Request $request, Group $group, User $user): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOfGroup($group), 403);

        $ownerCount = $group->members()->wherePivot('role', 'owner')->count();
        $isTargetOwner = $group->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();

        if ($isTargetOwner && $ownerCount <= 1) {
            return back()->withErrors([
                'identifier' => '最後のオーナーを削除することはできません。',
            ]);
        }

        $group->members()->detach($user->id);

        return back()->with('status', "@{$user->handle} さんをメンバーから外しました。");
    }
}

