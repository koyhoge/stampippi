@extends('layouts.app')

@section('title', 'グループ管理 | Stampippi')

@section('content')
<div class="space-y-8">
    <!-- Management Sub-Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
        <a href="{{ route('my.index') }}" class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs sm:text-sm font-bold px-4 py-2 rounded-xl transition">
            <span>🧭 ラリー・シリーズ管理</span>
        </a>
        <a href="{{ route('my.groups.index') }}" class="inline-flex items-center gap-1.5 bg-indigo-600 text-white text-xs sm:text-sm font-bold px-4 py-2 rounded-xl shadow-sm">
            <span>👥 グループ管理</span>
        </a>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">GROUP & ROLE MANAGEMENT</span>
                @if ($user->isAdmin())
                    <span class="bg-amber-100 text-amber-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full">👑 管理者 (Admin)</span>
                @else
                    <span class="bg-indigo-50 text-indigo-700 text-[11px] font-bold px-2.5 py-0.5 rounded-full">👥 グループメンバー</span>
                @endif
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">
                👥 グループ・運営メンバー管理
            </h1>
            <p class="text-xs text-slate-500">
                グループにメンバーを追加すると、そのグループのラリーシリーズに属するスタンプラリーを共同で作成・管理できます。
            </p>
        </div>

        @if ($user->canCreateGroup())
            <a href="{{ route('my.groups.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm shrink-0 transition">
                👥 ＋ 新しいグループを作成
            </a>
        @endif
    </div>

    <!-- Groups List -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900">👥 所属・管理グループ ({{ $manageableGroups->count() }}件)</h2>
            @if ($user->canCreateGroup())
                <a href="{{ route('my.groups.create') }}" class="text-xs font-bold text-indigo-600 hover:underline">＋ グループ作成</a>
            @endif
        </div>

        @if ($manageableGroups->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-5 border border-slate-200">所属・管理しているグループはありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($manageableGroups as $group)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col justify-between gap-4">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-bold text-indigo-600">{{ '@' . $group->slug }}</span>
                                <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-semibold">
                                    {{ $group->pivot?->role ?? ($user->isAdmin() ? 'admin' : 'member') }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">{{ $group->name }}</h3>
                            <p class="text-xs text-slate-500 line-clamp-2 mt-1">{{ $group->description }}</p>
                            <div class="flex items-center gap-3 text-[11px] text-slate-500 mt-3">
                                <span>👤 メンバー {{ $group->members_count }}名</span>
                                <span>📚 シリーズ {{ $group->series_count }}件</span>
                                <span>🧭 ラリー {{ $group->rallies_count }}件</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                            <a href="{{ route('groups.show', $group) }}" class="text-slate-600 hover:underline">公開ページ</a>
                            <a href="{{ route('my.groups.edit', $group) }}" class="font-bold text-indigo-600 hover:underline">⚙️ 管理・メンバー追加 →</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Admin User Management (Admins Only) -->
    @if ($user->isAdmin())
        <section class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
            <div>
                <h2 class="text-lg font-bold text-slate-900">👑 システム管理者ユーザーの管理</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    管理者に指定されたユーザーは、新しいグループやラリーシリーズ、単発スタンプラリーを自由に作成できます。
                </p>
            </div>

            <form method="POST" action="{{ route('my.admins.store') }}" class="flex flex-col sm:flex-row gap-2 max-w-lg">
                @csrf
                <input type="text" name="identifier" required placeholder="管理者に追加する @handle または メールアドレス" class="flex-1 rounded-xl border border-slate-300 px-3.5 py-2 text-xs">
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2 rounded-xl shrink-0">
                    ＋ 管理者に設定する
                </button>
            </form>

            <div class="divide-y divide-slate-100">
                @foreach ($adminUsers as $adminUser)
                    <div class="py-3 flex items-center justify-between gap-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900">{{ $adminUser->name }}</span>
                            <span class="text-xs font-semibold text-indigo-600">({{ '@' . $adminUser->handle }})</span>
                            <span class="text-xs text-slate-400">{{ $adminUser->email }}</span>
                        </div>
                        @if ($adminUsers->count() > 1)
                            <form method="POST" action="{{ route('my.admins.destroy', $adminUser) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-600 hover:underline">管理者解除</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

