@extends('layouts.app')

@section('title', 'マイページ | Stampippi')

@section('content')
<div class="space-y-8">
    <!-- Participant Header -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="shrink-0">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-12 sm:w-14 h-12 sm:h-14 rounded-full object-cover border-2 border-indigo-100 shadow-sm">
                @else
                    <div class="w-12 sm:w-14 h-12 sm:h-14 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-lg sm:text-xl shadow-sm">
                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="space-y-1">
                <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider">MY STAMP BOOK</div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">
                    {{ $user->name }} <span class="text-sm sm:text-base font-semibold text-indigo-600">({{ '@' . $user->handle }})</span>
                </h1>
                <p class="text-xs text-slate-500">参加中のスタンプラリーと獲得したスタンプの記録を確認できます。</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('dashboard.map') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl shadow-sm transition">
                🗺️ 獲得スタンプ全地点マップ ({{ $totalStampsCount }}個)
            </a>
            <a href="{{ route('profile.edit') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-3.5 py-2 rounded-xl transition">
                👤 プロフィール設定
            </a>
            @if ($user->canAccessManagement())
                <a href="{{ route('my.index') }}" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold px-3.5 py-2 rounded-xl transition">
                    ⚙️ ラリー管理画面へ →
                </a>
            @endif
        </div>
    </div>

    <!-- Participating Stamp Rallies (Player View) -->
    <section class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h2 class="text-xl font-bold text-slate-900">
                📖 {{ $showArchived ? 'すべての参加スタンプラリー（アーカイブ含む）' : '参加中のスタンプラリー' }} ({{ $participations->count() }}件)
            </h2>

            @if ($allParticipationsCount > 0)
                <div>
                    @if ($showArchived)
                        <a href="{{ route('dashboard', array_filter(['q' => $keyword ?: null])) }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-xl transition">
                            <span>📖 参加中のみ表示に戻す ({{ $activeParticipationsCount }}件)</span>
                        </a>
                    @else
                        <a href="{{ route('dashboard', array_filter(['archived' => 1, 'q' => $keyword ?: null])) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 px-3 py-1.5 rounded-xl transition">
                            <span>📦 アーカイブ済も含めてすべて表示 (全{{ $allParticipationsCount }}件 / アーカイブ {{ $archivedCount }}件)</span>
                        </a>
                    @endif
                </div>
            @endif
        </div>

        @if ($participations->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-6 text-sm text-slate-500 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <span>
                    @if ($archivedCount > 0 && ! $showArchived)
                        現在進行中のスタンプラリーはありません（アーカイブ済みのスタンプラリーが {{ $archivedCount }}件 あります）。
                    @else
                        まだ参加中のスタンプラリーはありません。下の「開催中のスタンプラリーを探す」から選んで参加できます。
                    @endif
                </span>
                @if ($archivedCount > 0 && ! $showArchived)
                    <a href="{{ route('dashboard', array_filter(['archived' => 1, 'q' => $keyword ?: null])) }}" class="text-xs font-bold text-indigo-600 hover:underline shrink-0">
                        📦 アーカイブ済を表示する →
                    </a>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($participations as $participation)
                    @php
                        $rally = $participation->rally;
                        $stamps = $stampsByRally->get($rally->id, collect());
                        $required = $rally->requiredCount();
                        $isArchived = $participation->isArchived();
                    @endphp
                    <div class="rounded-2xl border {{ $isArchived ? 'bg-slate-50/80 border-slate-200 opacity-90' : 'bg-white border-slate-200' }} p-5 flex flex-col justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                @if ($rally->series)
                                    <a href="{{ route('series.show', $rally->series) }}" class="text-indigo-600 font-semibold hover:underline truncate">
                                        📚 {{ $rally->series->title }}
                                    </a>
                                @else
                                    <span class="text-slate-400">単発ラリー（{{ $rally->periodLabel() }}）</span>
                                @endif

                                <div class="flex items-center gap-1.5 shrink-0">
                                    @if ($participation->archived_at)
                                        <span class="bg-slate-200 text-slate-700 font-bold px-2 py-0.5 rounded-full">📦 アーカイブ済</span>
                                    @elseif ($rally->ends_at && $rally->ends_at->isPast())
                                        <span class="bg-slate-200 text-slate-600 font-bold px-2 py-0.5 rounded-full">期間終了</span>
                                    @endif

                                    @if ($participation->completed_at)
                                        <span class="bg-amber-100 text-amber-800 font-bold px-2.5 py-0.5 rounded-full">🏆 コンプリート済</span>
                                    @else
                                        <span class="bg-indigo-50 text-indigo-700 font-bold px-2.5 py-0.5 rounded-full">進行中: {{ $stamps->count() }} / {{ $required }}</span>
                                    @endif
                                </div>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900">
                                <a href="{{ route('rallies.show', $rally) }}" class="hover:text-indigo-600">{{ $rally->title }}</a>
                            </h3>

                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach ($stamps as $s)
                                    <span class="inline-flex items-center gap-1 text-xs bg-indigo-50 text-indigo-900 px-2.5 py-1 rounded-lg font-medium">
                                        @if ($s->location->hasCustomImage())
                                            <img src="{{ $s->location->iconImageUrl() }}" alt="" class="w-4 h-4 object-contain">
                                        @else
                                            <span>{{ $s->location->iconEmoji() }}</span>
                                        @endif
                                        <span>{{ $s->location->name }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs gap-2">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('rallies.show', $rally) }}" class="font-bold text-indigo-600 hover:underline">スタンプ帳・マップを開く →</a>
                                @if ($participation->completed_at)
                                    <a href="{{ route('rallies.certificate', $rally) }}" class="font-bold text-amber-600 hover:underline">🏆 達成証</a>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('rallies.archive', $rally) }}">
                                @csrf
                                @if ($participation->archived_at)
                                    <button type="submit" class="text-slate-600 hover:text-indigo-600 font-semibold px-2 py-1 rounded hover:bg-slate-200/60 transition">
                                        ↩️ アーカイブ解除
                                    </button>
                                @else
                                    <button type="submit" class="text-slate-400 hover:text-slate-700 font-medium px-2 py-1 rounded hover:bg-slate-100 transition" title="参加中一覧からアーカイブへ移動します">
                                        📦 アーカイブ
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Public Active Rallies (Compact & Searchable, Max 6) -->
    <section id="public-rallies" class="space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-baseline gap-2">
                <h2 class="text-lg font-bold text-slate-900">🗺️ 開催中のスタンプラリーを探す</h2>
                <span class="text-xs text-slate-500">
                    @if ($keyword !== '')
                        「{{ $keyword }}」の検索結果: {{ $totalPublicActiveCount }}件（最大6件表示）
                    @else
                        最新 {{ $publicRallies->count() }}件を表示中（開催中 全{{ $totalPublicActiveCount }}件）
                    @endif
                </span>
            </div>

            <form method="GET" action="{{ route('dashboard') }}#public-rallies" class="flex items-center gap-1.5 w-full sm:w-auto">
                <input
                    type="search"
                    name="q"
                    value="{{ $keyword }}"
                    placeholder="ラリー名・シリーズ・会場名で検索..."
                    class="w-full sm:w-64 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs focus:border-indigo-500 focus:outline-none"
                >
                <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold px-3 py-1.5 rounded-xl shrink-0 transition">
                    検索
                </button>
                @if ($keyword !== '')
                    <a href="{{ route('dashboard') }}#public-rallies" class="text-xs text-slate-500 hover:text-slate-800 px-1.5 shrink-0">
                        クリア
                    </a>
                @endif
            </form>
        </div>

        @if ($publicRallies->isEmpty())
            <p class="text-xs text-slate-500 bg-white rounded-xl p-4 border border-slate-200">
                {{ $keyword !== '' ? "「{$keyword}」に一致する開催中のスタンプラリーは見つかりませんでした。" : '現在開催中の公開スタンプラリーはありません。' }}
            </p>
        @else
            <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
                @foreach ($publicRallies as $pubRally)
                    <a href="{{ route('rallies.show', $pubRally) }}" class="px-4 py-3 hover:bg-slate-50 flex items-center justify-between gap-3 transition">
                        <div class="min-w-0 flex-1 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                            <span class="text-sm font-bold text-slate-900 truncate">{{ $pubRally->title }}</span>
                            <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                @if ($pubRally->series)
                                    <span class="text-indigo-600 font-medium truncate">📚 {{ $pubRally->series->title }}</span>
                                @endif
                                <span class="truncate">{{ $pubRally->ownerName() }}</span>
                                <span>📍 {{ $pubRally->locations_count }}箇所</span>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-indigo-600 shrink-0">開く →</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
