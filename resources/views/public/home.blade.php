@extends('layouts.app')

@section('title', 'Stampippi - ネットと現地をつなぐスタンプラリー')

@section('content')
<div class="space-y-12">
    <!-- Hero -->
    <section class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 rounded-2xl p-8 sm:p-10 text-white shadow-lg">
        <div class="max-w-3xl space-y-4">
            <span class="inline-block bg-white/20 backdrop-blur px-3 py-1 rounded-full text-xs font-semibold tracking-wide">
                GPS連動・シリーズ対応スタンプラリー
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold leading-tight">
                全国のイベントや街歩きを、<br class="hidden sm:inline">あなたのスタンプ帳に記録しよう。
            </h1>
            <p class="text-indigo-100 text-sm sm:text-base leading-relaxed">
                カンファレンス巡り、地域イベント、常設スポット巡礼まで。個人でもグループ（Organization）でも自由にスタンプラリーシリーズを開催・参加できます。
            </p>
            <div class="pt-2 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="bg-white text-indigo-700 hover:bg-indigo-50 font-bold px-5 py-2.5 rounded-xl text-sm shadow transition">
                        マイスタンプ帳を見る
                    </a>
                    <a href="{{ route('my.rallies.create') }}" class="bg-indigo-500/40 hover:bg-indigo-500/60 border border-white/30 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition">
                        ＋ 新しいスタンプラリーを作る
                    </a>
                @else
                    <a href="{{ route('login') }}" class="bg-white text-indigo-700 hover:bg-indigo-50 font-bold px-5 py-2.5 rounded-xl text-sm shadow transition">
                        ログインしてスタンプラリーに参加する
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <!-- Rally Series Section -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">📚 ラリーシリーズ</h2>
                <p class="text-xs text-slate-500">年次開催や複数コースを束ねたスタンプラリーシリーズ</p>
            </div>
            @auth
                <a href="{{ route('my.series.create') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">＋ シリーズを作成</a>
            @endauth
        </div>

        @if ($seriesList->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-6 border border-slate-200">公開中のラリーシリーズはまだありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach ($seriesList as $series)
                    <a href="{{ route('series.show', $series) }}" class="block bg-white rounded-2xl border border-slate-200 p-6 hover:border-indigo-400 hover:shadow-md transition">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full">
                                📚 シリーズ ({{ $series->rallies->count() }} ラリー)
                            </span>
                            <span class="text-xs text-slate-500">
                                主催: {{ $series->ownerName() }}
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-1">{{ $series->title }}</h3>
                        <p class="text-sm text-slate-600 line-clamp-2 mb-4">{{ $series->description }}</p>
                        @if ($series->rallies->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5 pt-2 border-t border-slate-100">
                                @foreach ($series->rallies->take(4) as $r)
                                    <span class="text-xs bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-md font-medium">
                                        {{ $r->title }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Stamp Rallies Section -->
    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">🗺️ 公開中のスタンプラリー</h2>
                <p class="text-xs text-slate-500">現在開催中・常設のスタンプラリー一覧</p>
            </div>
            @auth
                <a href="{{ route('my.rallies.create') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">＋ ラリーを作成</a>
            @endauth
        </div>

        @if ($rallies->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-6 border border-slate-200">公開中のスタンプラリーはまだありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($rallies as $rally)
                    <a href="{{ route('rallies.show', $rally) }}" class="flex flex-col justify-between bg-white rounded-2xl border border-slate-200 p-5 hover:border-indigo-400 hover:shadow-md transition">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                @if ($rally->series)
                                    <span class="text-xs font-medium text-purple-700 bg-purple-50 px-2 py-0.5 rounded">
                                        {{ $rally->series->title }}
                                    </span>
                                @else
                                    <span class="text-xs font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                        単発ラリー
                                    </span>
                                @endif
                                <span class="text-xs font-semibold {{ $rally->isCurrentlyActive() ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $rally->isCurrentlyActive() ? '● 開催中' : '期間外' }}
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 mb-1.5">{{ $rally->title }}</h3>
                            <p class="text-xs text-slate-600 line-clamp-2 mb-4">{{ $rally->description }}</p>
                        </div>

                        <div class="pt-3 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                            <div class="flex items-center justify-between">
                                <span>📍 ベニュー数: <strong class="text-slate-800">{{ $rally->locations->count() }}箇所</strong></span>
                                <span>主催: {{ $rally->ownerName() }}</span>
                            </div>
                            <div class="text-[11px] text-slate-400">
                                期間: {{ $rally->periodLabel() }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Organizations Section -->
    @if ($organizations->isNotEmpty())
        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">🏢 運営グループ (Organizations)</h2>
                <p class="text-xs text-slate-500">複数のスタンプラリーやシリーズを共同運営する団体・コミュニティ</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @foreach ($organizations as $org)
                    <a href="{{ route('orgs.show', $org) }}" class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:border-indigo-400 px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm transition">
                        <span>🏢</span>
                        <span class="font-bold text-slate-800">{{ $org->name }}</span>
                        <span class="text-xs text-slate-400">({{ '@' . $org->slug }})</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

