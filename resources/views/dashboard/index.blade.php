@extends('layouts.app')

@section('title', 'マイページ・管理ダッシュボード | Stampippi')

@section('content')
<div class="space-y-10">
    <!-- User Profile & Quick Actions -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider">MY ACCOUNT</div>
            <h1 class="text-2xl font-extrabold text-slate-900">
                {{ $user->name }} <span class="text-base font-semibold text-indigo-600">({{ '@' . $user->handle }})</span>
            </h1>
            <p class="text-xs text-slate-500">{{ $user->email }} — グループへの招待時は <strong>{{ '@' . $user->handle }}</strong> を伝えてください</p>
        </div>

        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('my.groups.create') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold px-4 py-2.5 rounded-xl transition">
                👥 ＋ グループ作成
            </a>
            <a href="{{ route('my.series.create') }}" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold px-4 py-2.5 rounded-xl transition">
                📚 ＋ ラリーシリーズ作成
            </a>
            <a href="{{ route('my.rallies.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition">
                🗺️ ＋ スタンプラリー作成
            </a>
        </div>
    </div>

    <!-- Participating Stamp Rallies (Player View) -->
    <section class="space-y-4">
        <h2 class="text-xl font-bold text-slate-900">📖 参加中のスタンプラリー ({{ $participations->count() }}件)</h2>

        @if ($participations->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-6 text-sm text-slate-500">
                まだ参加中のスタンプラリーはありません。下の「公開中のスタンプラリー」から選んで参加できます。
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($participations as $participation)
                    @php
                        $rally = $participation->rally;
                        $stamps = $stampsByRally->get($rally->id, collect());
                        $total = $rally->locations->count();
                        $required = $rally->requiredCount();
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                @if ($rally->series)
                                    <a href="{{ route('series.show', $rally->series) }}" class="text-indigo-600 font-semibold hover:underline">
                                        📚 {{ $rally->series->title }}
                                    </a>
                                @else
                                    <span class="text-slate-400">単発ラリー</span>
                                @endif

                                @if ($participation->completed_at)
                                    <span class="bg-amber-100 text-amber-800 font-bold px-2.5 py-0.5 rounded-full">🏆 コンプリート済</span>
                                @else
                                    <span class="bg-indigo-50 text-indigo-700 font-bold px-2.5 py-0.5 rounded-full">進行中: {{ $stamps->count() }} / {{ $required }}</span>
                                @endif
                            </div>

                            <h3 class="text-lg font-bold text-slate-900">
                                <a href="{{ route('rallies.show', $rally) }}" class="hover:text-indigo-600">{{ $rally->title }}</a>
                            </h3>

                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach ($stamps as $s)
                                    <span class="inline-flex items-center gap-1 text-xs bg-indigo-50 text-indigo-900 px-2.5 py-1 rounded-lg font-medium">
                                        <span>{{ $s->location->iconEmoji() }}</span>
                                        <span>{{ $s->location->name }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                            <a href="{{ route('rallies.show', $rally) }}" class="font-bold text-indigo-600 hover:underline">スタンプ帳・マップを開く →</a>
                            @if ($participation->completed_at)
                                <a href="{{ route('rallies.certificate', $rally) }}" class="font-bold text-amber-600 hover:underline">🏆 達成証を表示</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Public Rallies to Join -->
    <section class="space-y-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">🗺️ 公開中のスタンプラリー一覧 ({{ $publicRallies->count() }}件)</h2>
            <p class="text-xs text-slate-500">参加したいスタンプラリーを選んでスタンプ帳を開けます</p>
        </div>
        @if ($publicRallies->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-5 border border-slate-200">現在公開中のスタンプラリーはありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($publicRallies as $pubRally)
                    <a href="{{ route('rallies.show', $pubRally) }}" class="bg-white rounded-2xl border border-slate-200 hover:border-indigo-400 p-5 flex flex-col justify-between gap-3 transition">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-slate-500 truncate">{{ $pubRally->ownerName() }}</span>
                                <span class="font-bold {{ $pubRally->isCurrentlyActive() ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $pubRally->isCurrentlyActive() ? '● 開催中' : '期間外' }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">{{ $pubRally->title }}</h3>
                            @if ($pubRally->series)
                                <p class="text-xs text-indigo-600 mt-0.5">📚 {{ $pubRally->series->title }}</p>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span>📍 {{ $pubRally->locations->count() }} ベニュー</span>
                            <span class="font-semibold text-indigo-600">開く →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Groups Management -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">👥 所属グループ (Groups)</h2>
                <p class="text-xs text-slate-500">複数人でラリーシリーズやスタンプラリーを共同運営・引き継ぎできます</p>
            </div>
            <a href="{{ route('my.groups.create') }}" class="text-xs font-bold text-indigo-600 hover:underline">＋ 新規作成</a>
        </div>

        @if ($user->groups->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-5 border border-slate-200">所属しているグループはありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($user->groups as $group)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col justify-between gap-3">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-bold text-indigo-600">{{ '@' . $group->slug }}</span>
                                <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-semibold">{{ $group->pivot->role }}</span>
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

    <!-- Manageable Rally Series -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900">📚 管理中のラリーシリーズ ({{ $manageableSeries->count() }}件)</h2>
            <a href="{{ route('my.series.create') }}" class="text-xs font-bold text-indigo-600 hover:underline">＋ シリーズ作成</a>
        </div>

        @if ($manageableSeries->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-5 border border-slate-200">管理中のラリーシリーズはありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($manageableSeries as $series)
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between gap-4">
                        <div>
                            <div class="text-xs text-slate-500 mb-0.5">
                                所有: {{ $series->group ? '👥 ' . $series->group->name : '👤 個人 (' . '@' . $user->handle . ')' }}
                                ／ 状態: <strong>{{ $series->visibility }}</strong>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">{{ $series->title }}</h3>
                            <p class="text-xs text-slate-500 mt-1">含まれるラリー: {{ $series->rallies->count() }}件</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('series.show', $series) }}" class="text-xs text-slate-600 hover:underline px-2 py-1">表示</a>
                            <a href="{{ route('my.series.edit', $series) }}" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold px-3 py-1.5 rounded-lg">編集</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Manageable Stamp Rallies -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900">🗺️ 管理中のスタンプラリー ({{ $manageableRallies->count() }}件)</h2>
            <a href="{{ route('my.rallies.create') }}" class="text-xs font-bold text-indigo-600 hover:underline">＋ スタンプラリー作成</a>
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
</div>
@endsection

