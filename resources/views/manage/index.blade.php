@extends('layouts.app')

@section('title', '運営・ラリー管理ダッシュボード | Stampippi')

@section('content')
<div class="space-y-10">
    <!-- Management Header & Quick Actions -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">MANAGEMENT CONSOLE</span>
                @if ($user->isAdmin())
                    <span class="bg-amber-100 text-amber-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full">👑 管理者 (Admin)</span>
                @else
                    <span class="bg-indigo-50 text-indigo-700 text-[11px] font-bold px-2.5 py-0.5 rounded-full">👥 グループメンバー</span>
                @endif
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">
                ⚙️ スタンプラリー運営・管理
            </h1>
            <p class="text-xs text-slate-500">
                @if ($user->isAdmin())
                    管理者として、グループ・ラリーシリーズ・スタンプラリーの作成と全リソースの管理が行えます。
                @else
                    所属グループのシリーズに属するスタンプラリーの作成・ベニュー管理が行えます。
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2.5">
            @if ($user->canCreateGroup())
                <a href="{{ route('my.groups.create') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold px-4 py-2.5 rounded-xl transition">
                    👥 ＋ グループ作成
                </a>
            @endif
            @if ($user->canCreateSeries())
                <a href="{{ route('my.series.create') }}" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold px-4 py-2.5 rounded-xl transition">
                    📚 ＋ ラリーシリーズ作成
                </a>
            @endif
            @if ($user->canCreateRally())
                <a href="{{ route('my.rallies.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition">
                    🗺️ ＋ スタンプラリー作成
                </a>
            @endif
        </div>
    </div>

    <!-- Manageable Stamp Rallies -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">🗺️ 管理中のスタンプラリー ({{ $manageableRallies->count() }}件)</h2>
                <p class="text-xs text-slate-500">各ラリーの開催期間・コンプリート条件・ベニュー（スタンプロケーション）を編集できます</p>
            </div>
            @if ($user->canCreateRally())
                <a href="{{ route('my.rallies.create') }}" class="text-xs font-bold text-indigo-600 hover:underline">＋ スタンプラリー作成</a>
            @endif
        </div>

        @if ($manageableRallies->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-5 border border-slate-200">管理中のスタンプラリーはありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($manageableRallies as $rally)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs text-slate-500 mb-0.5">
                                所有: {{ $rally->ownerName() }}
                                @if ($rally->series) ／ 📚 {{ $rally->series->title }} @endif
                            </div>
                            <h3 class="text-base font-bold text-slate-900">{{ $rally->title }}</h3>
                            <p class="text-xs text-slate-500 mt-1">
                                📍 {{ $rally->locations->count() }} ベニュー ／ {{ $rally->periodLabel() }} ／ <strong>{{ $rally->visibility }}</strong>
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('rallies.show', $rally) }}" class="text-xs text-slate-600 hover:underline px-2 py-1">表示</a>
                            <a href="{{ route('my.rallies.edit', $rally) }}" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-3 py-1.5 rounded-lg">管理・ベニュー編集</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Manageable Rally Series -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">📚 管理中のラリーシリーズ ({{ $manageableSeries->count() }}件)</h2>
                <p class="text-xs text-slate-500">シリーズに属する新しいスタンプラリーの追加やシリーズ情報の編集が行えます</p>
            </div>
            @if ($user->canCreateSeries())
                <a href="{{ route('my.series.create') }}" class="text-xs font-bold text-indigo-600 hover:underline">＋ シリーズ作成</a>
            @endif
        </div>

        @if ($manageableSeries->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-5 border border-slate-200">管理中のラリーシリーズはありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($manageableSeries as $series)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col justify-between gap-4">
                        <div>
                            <div class="text-xs text-slate-500 mb-0.5">
                                所有: {{ $series->group ? '👥 ' . $series->group->name : '👤 個人 (' . '@' . ($series->creator?->handle ?? 'unknown') . ')' }}
                                ／ 状態: <strong>{{ $series->visibility }}</strong>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">{{ $series->title }}</h3>
                            <p class="text-xs text-slate-500 mt-1">含まれるラリー: {{ $series->rallies->count() }}件</p>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 gap-2">
                            <a href="{{ route('my.rallies.create', ['series_id' => $series->id, 'group_id' => $series->group_id]) }}" class="text-xs font-bold text-indigo-600 hover:underline">
                                ＋ このシリーズにラリーを作成
                            </a>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('series.show', $series) }}" class="text-xs text-slate-600 hover:underline px-2 py-1">表示</a>
                                <a href="{{ route('my.series.edit', $series) }}" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold px-3 py-1.5 rounded-lg">編集</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Groups Management -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">👥 管理グループ ({{ $manageableGroups->count() }}件)</h2>
                <p class="text-xs text-slate-500">グループメンバーを追加すると、そのグループのシリーズに属するスタンプラリーを共同作成・管理できます</p>
            </div>
            @if ($user->canCreateGroup())
                <a href="{{ route('my.groups.create') }}" class="text-xs font-bold text-indigo-600 hover:underline">＋ グループ作成</a>
            @endif
        </div>

        @if ($manageableGroups->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-5 border border-slate-200">所属・管理しているグループはありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($manageableGroups as $group)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col justify-between gap-3">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-bold text-indigo-600">{{ '@' . $group->slug }}</span>
                                <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-semibold">
                                    {{ $group->pivot?->role ?? ($user->isAdmin() ? 'admin' : 'member') }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">{{ $group->name }}</h3>
                            <p class="text-xs text-slate-500 line-clamp-2 mt-1">{{ $group->description }}</p>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                            <a href="{{ route('groups.show', $group) }}" class="text-slate-600 hover:underline">公開ページ</a>
                            <a href="{{ route('my.groups.edit', $group) }}" class="font-bold text-indigo-600 hover:underline">⚙️ 管理・メンバー追加</a>
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
                <h2 class="text-lg font-bold text-slate-900">👑 管理者ユーザーの管理</h2>
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

