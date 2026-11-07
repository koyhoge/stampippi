@extends('layouts.app')

@section('title', $organization->name . ' の管理 | Stampippi')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">🏢 {{ $organization->name }} ({{ '@' . $organization->slug }}) の管理</h1>
            <p class="text-xs text-slate-500 mt-1">基本情報の編集と、登録済みユーザー（@handle / メール）のメンバー追加が行えます。</p>
        </div>
        <a href="{{ route('orgs.show', $organization) }}" class="text-xs font-semibold text-indigo-600 hover:underline">公開ページを見る →</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Organization Settings -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">基本情報編集</h2>
            <form method="POST" action="{{ route('my.organizations.update', $organization) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Organization ID (slug)</label>
                    <input type="text" name="slug" value="{{ old('slug', $organization->slug) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">表示名</label>
                    <input type="text" name="name" value="{{ old('name', $organization->name) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">説明文</label>
                    <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">{{ old('description', $organization->description) }}</textarea>
                </div>
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-5 py-2.5 rounded-xl">
                    更新する
                </button>
            </form>
        </div>

        <!-- Members Management -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            <div>
                <h2 class="text-base font-bold text-slate-900">👥 所属メンバー ({{ $organization->members->count() }}名)</h2>
                <p class="text-xs text-slate-500">登録済みユーザーの <strong>@handle</strong> またはメールアドレスを指定して追加できます。</p>
            </div>

            @if (auth()->user()->isOwnerOfOrganization($organization))
                <form method="POST" action="{{ route('my.organizations.members.store', $organization) }}" class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">@handle または メールアドレス</label>
                            <input type="text" name="identifier" required placeholder="@participant または email" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">権限ロール</label>
                            <select name="role" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
                                <option value="member">member (編集可)</option>
                                <option value="owner">owner (管理者)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-lg">
                        ＋ 登録済みユーザーをメンバーに追加
                    </button>
                </form>
            @endif

            <div class="divide-y divide-slate-100">
                @foreach ($organization->members as $member)
                    <div class="py-3 flex items-center justify-between gap-2 text-sm">
                        <div>
                            <span class="font-bold text-slate-900">{{ $member->name }}</span>
                            <span class="text-xs font-semibold text-indigo-600">({{ '@' . $member->handle }})</span>
                            <span class="ml-2 text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-semibold">{{ $member->pivot->role }}</span>
                        </div>
                        @if (auth()->user()->isOwnerOfOrganization($organization))
                            <form method="POST" action="{{ route('my.organizations.members.destroy', [$organization, $member]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-600 hover:underline">削除</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

