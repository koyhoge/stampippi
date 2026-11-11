@extends('layouts.app')

@section('title', $group->name . ' (@' . $group->slug . ') | Stampippi')

@section('content')
<div class="space-y-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 text-xs font-bold text-indigo-700 bg-indigo-50 px-3 py-1 rounded-full">
                    👥 Group ({{ '@' . $group->slug }})
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ $group->name }}</h1>
                @if ($group->description)
                    <p class="text-sm text-slate-600 max-w-3xl whitespace-pre-line">{{ $group->description }}</p>
                @endif
            </div>

            @auth
                @if (auth()->user()->canManageGroup($group))
                    <a href="{{ route('my.groups.edit', $group) }}" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2 rounded-lg">
                        ⚙️ グループ管理・メンバー追加
                    </a>
                @endif
            @endauth
        </div>

        <div class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-slate-500 mr-1">運営メンバー:</span>
            @foreach ($group->members as $member)
                <span class="inline-flex items-center gap-1 text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full">
                    <strong class="text-indigo-600">{{ '@' . $member->handle }}</strong>
                    <span>({{ $member->name }})</span>
                </span>
            @endforeach
        </div>
    </div>

    @if ($group->series->isNotEmpty())
        <section class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900">📚 主催ラリーシリーズ</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($group->series as $series)
                    <a href="{{ route('series.show', $series) }}" class="block bg-white rounded-2xl border border-slate-200 p-5 hover:border-indigo-400 transition">
                        <h3 class="text-lg font-bold text-slate-900 mb-1">{{ $series->title }}</h3>
                        <p class="text-xs text-slate-600 mb-3">{{ $series->description }}</p>
                        <span class="text-xs font-semibold text-indigo-600">{{ $series->rallies->count() }} 件のスタンプラリー →</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="space-y-4">
        <h2 class="text-xl font-bold text-slate-900">🧭 主催スタンプラリー</h2>
        @if ($group->rallies->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-6 border border-slate-200">公開中のスタンプラリーはありません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($group->rallies as $rally)
                    <a href="{{ route('rallies.show', $rally) }}" class="block bg-white rounded-2xl border border-slate-200 p-5 hover:border-indigo-400 transition">
                        <h3 class="text-base font-bold text-slate-900 mb-1">{{ $rally->title }}</h3>
                        <p class="text-xs text-slate-600 line-clamp-2 mb-3">{{ $rally->description }}</p>
                        <div class="text-xs text-slate-500">📍 {{ $rally->locations->count() }} ベニュー / {{ $rally->periodLabel() }}</div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

