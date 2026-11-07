@extends('layouts.app')

@section('title', $series->title . ' - ラリーシリーズ | Stampippi')

@section('content')
<div class="space-y-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-3">
            <div class="flex items-center gap-2">
                <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full">📚 ラリーシリーズ</span>
                @if ($series->group)
                    <a href="{{ route('groups.show', $series->group) }}" class="text-xs font-semibold text-slate-600 hover:text-indigo-600">
                        👥 {{ $series->group->name }} ({{ '@' . $series->group->slug }})
                    </a>
                @else
                    <span class="text-xs text-slate-500">作成者: {{ $series->creator->name }} ({{ '@' . $series->creator->handle }})</span>
                @endif
            </div>

            @auth
                @if (auth()->user()->canManageSeries($series))
                    <div class="flex items-center gap-2">
                        <a href="{{ route('my.rallies.create', ['series_id' => $series->id, 'group_id' => $series->group_id]) }}" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-3 py-1.5 rounded-lg">
                            ＋ このシリーズにラリーを追加
                        </a>
                        <a href="{{ route('my.series.edit', $series) }}" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-1.5 rounded-lg">
                            ⚙️ シリーズ編集
                        </a>
                    </div>
                @endif
            @endauth
        </div>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-3">{{ $series->title }}</h1>
        @if ($series->description)
            <p class="text-sm sm:text-base text-slate-600 whitespace-pre-line">{{ $series->description }}</p>
        @endif
    </div>

    <div class="space-y-4">
        <h2 class="text-xl font-bold text-slate-900">このシリーズに含まれるスタンプラリー ({{ $series->rallies->count() }}件)</h2>

        @if ($series->rallies->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-6 border border-slate-200">まだスタンプラリーが登録されていません。</p>
        @else
            <div class="space-y-4">
                @foreach ($series->rallies as $rally)
                    @php
                        $participation = $userParticipations->get($rally->id);
                        $stampedCount = $userStampCounts->get($rally->id, 0);
                        $totalLocations = $rally->locations->count();
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 hover:border-indigo-400 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $rally->isCurrentlyActive() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $rally->isCurrentlyActive() ? '開催中' : '期間外 / アーカイブ' }}
                                </span>
                                <span class="text-xs text-slate-500">期間: {{ $rally->periodLabel() }}</span>
                            </div>

                            <h3 class="text-xl font-bold text-slate-900">
                                <a href="{{ route('rallies.show', $rally) }}" class="hover:text-indigo-600">{{ $rally->title }}</a>
                            </h3>
                            <p class="text-sm text-slate-600">{{ $rally->description }}</p>

                            <div class="flex flex-wrap gap-2 pt-1">
                                @foreach ($rally->locations as $loc)
                                    <span class="inline-flex items-center gap-1 text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md">
                                        <span>{{ $loc->iconEmoji() }}</span>
                                        <span>{{ $loc->name }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="sm:text-right flex flex-col sm:items-end justify-between gap-3 shrink-0">
                            @auth
                                <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs">
                                    @if ($participation && $participation->completed_at)
                                        <div class="font-bold text-emerald-600">🎉 コンプリート達成済み</div>
                                    @elseif ($stampedCount > 0 || $participation)
                                        <div class="font-bold text-indigo-600">参加中: {{ $stampedCount }} / {{ $totalLocations }} 獲得</div>
                                    @else
                                        <div class="text-slate-500">未参加 (全 {{ $totalLocations }} ベニュー)</div>
                                    @endif
                                </div>
                            @endauth

                            <a href="{{ route('rallies.show', $rally) }}" class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition">
                                スタンプ帳・マップを開く →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

