@extends('layouts.app')

@section('title', 'ラリー・シリーズ管理 | Stampippi')

@section('content')
<div class="space-y-8">
    <!-- Management Sub-Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
        <a href="{{ route('my.index') }}" class="inline-flex items-center gap-1.5 bg-indigo-600 text-white text-xs sm:text-sm font-bold px-4 py-2 rounded-xl shadow-sm">
            <span>🗺️ ラリー・シリーズ管理</span>
        </a>
        @if ($user->canManageAnyGroup())
            <a href="{{ route('my.groups.index') }}" class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs sm:text-sm font-bold px-4 py-2 rounded-xl transition">
                <span>👥 グループ管理</span>
            </a>
        @endif
    </div>

    <!-- Management Header & Quick Actions -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">RALLY MANAGEMENT</span>
                @if ($user->isAdmin())
                    <span class="bg-amber-100 text-amber-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full">👑 管理者 (Admin)</span>
                @elseif ($user->groups()->exists())
                    <span class="bg-indigo-50 text-indigo-700 text-[11px] font-bold px-2.5 py-0.5 rounded-full">👥 グループメンバー</span>
                @else
                    <span class="bg-slate-100 text-slate-700 text-[11px] font-bold px-2.5 py-0.5 rounded-full">👤 一般ユーザー</span>
                @endif
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">
                🗺️ スタンプラリー・シリーズ管理
            </h1>
            <p class="text-xs text-slate-500">
                @if ($user->isAdmin())
                    ラリーシリーズおよびスタンプラリーの作成・ベニュー管理が行えます。
                @elseif ($user->groups()->exists())
                    作成したスタンプラリーや所属グループのラリー・ベニュー管理が行えます。
                @else
                    自身が作成した単発スタンプラリーやベニューの管理が行えます。
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if ($user->canCreateSeries())
                <a href="{{ route('my.series.create') }}" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold px-4 py-2.5 rounded-xl transition">
                    📚 ＋ ラリーシリーズ作成
                </a>
            @endif
            @if ($user->maxRalliesAllowed() !== null)
                @if ($user->canCreateRally())
                    <div class="flex flex-col sm:items-end gap-1">
                        <a href="{{ route('my.rallies.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition">
                            🗺️ ＋ スタンプラリー作成
                        </a>
                        <span class="text-[11px] font-semibold text-slate-500">
                            あと <strong class="text-indigo-600 font-bold">{{ $user->remainingRalliesCount() }}個</strong> のラリーを作成できます（上限 {{ $user->maxRalliesAllowed() }}個）
                        </span>
                    </div>
                @else
                    <div class="flex flex-col sm:items-end gap-1">
                        <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-500 text-xs font-semibold px-3.5 py-2.5 rounded-xl border border-slate-200">
                            <span>⚠️ 作成上限（{{ $user->maxRalliesAllowed() }}個）に達しました</span>
                        </span>
                    </div>
                @endif
            @else
                @if ($user->canCreateRally())
                    <a href="{{ route('my.rallies.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition">
                        🗺️ ＋ スタンプラリー作成
                    </a>
                @endif
            @endif
        </div>
    </div>

    <!-- Manageable Stamp Rallies -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">🗺️ 管理中のスタンプラリー ({{ $manageableRallies->count() }}件)</h2>
                <p class="text-xs text-slate-500">
                    各ラリーの開催期間・コンプリート条件・ベニュー（スタンプロケーション）を編集できます
                    @if ($user->maxRalliesAllowed() !== null)
                        <span class="text-indigo-600 font-semibold ml-1">
                            （あと {{ $user->remainingRalliesCount() }}個 作成可能 / 上限 {{ $user->maxRalliesAllowed() }}個）
                        </span>
                    @endif
                </p>
            </div>
            @if ($user->canCreateRally())
                <a href="{{ route('my.rallies.create') }}" class="text-xs font-bold text-indigo-600 hover:underline">＋ スタンプラリー作成@if ($user->maxRalliesAllowed() !== null) (あと{{ $user->remainingRalliesCount() }}個)@endif</a>
            @elseif ($user->maxRalliesAllowed() !== null)
                <span class="text-xs font-medium text-slate-400">作成上限到達（{{ $user->maxRalliesAllowed() }}個）</span>
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
    @if ($user->canCreateSeries() || $manageableSeries->isNotEmpty())
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
                            @if ($user->canCreateRally())
                                <a href="{{ route('my.rallies.create', ['series_id' => $series->id, 'group_id' => $series->group_id]) }}" class="text-xs font-bold text-indigo-600 hover:underline">
                                    ＋ このシリーズにラリーを作成
                                </a>
                            @else
                                <span class="text-xs text-slate-400">作成上限到達</span>
                            @endif
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
    @endif
</div>
@endsection
