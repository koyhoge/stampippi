@extends('layouts.app')

@section('title', $user->name . ' さんの参加状況 | ' . $rally->title . ' | Stampippi')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb / Back Link -->
    <div>
        <a href="{{ route('rallies.show', $rally) }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
            <span>←</span>
            <span>「{{ $rally->title }}」スタンプラリーに戻る</span>
        </a>
    </div>

    <!-- Participant Summary Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
            <div class="flex items-center gap-4">
                <div class="shrink-0">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-16 sm:w-20 h-16 sm:h-20 rounded-full object-cover border-2 border-indigo-200 shadow-sm bg-white">
                    @else
                        <div class="w-16 sm:w-20 h-16 sm:h-20 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-2xl shadow-sm">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="space-y-1">
                    <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">PARTICIPANT STATUS</div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">
                        {{ $user->name }}
                        <span class="text-sm sm:text-base font-semibold text-slate-500 font-mono">({{ '@' . $user->handle }})</span>
                    </h1>
                    <p class="text-xs text-slate-500">
                        参加開始: {{ $participation->joined_at->format('Y/m/d H:i') }}
                    </p>
                </div>
            </div>

            <!-- Achievement Badge & Share -->
            <div class="self-start sm:self-center shrink-0 flex flex-col sm:items-end gap-2">
                @if ($participation->completed_at)
                    <div class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-500 to-amber-600 text-white text-xs sm:text-sm font-extrabold px-4 py-2.5 rounded-xl shadow-md">
                        <span>🏆</span>
                        <span>コンプリート達成！</span>
                    </div>
                @else
                    <div class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 text-xs sm:text-sm font-bold px-4 py-2 rounded-xl border border-indigo-200">
                        <span>進行中</span>
                    </div>
                @endif
                @include('partials.sns-share', [
                    'title' => "{$user->name} さんの参加状況 | {$rally->title}",
                    'text' => $participation->completed_at
                        ? "🎉 「{$rally->title}」をコンプリート達成しました！🏆\n#Stampippi #スタンプラリー"
                        : "スタンプラリー「{$rally->title}」に参加中！💮\n#Stampippi #スタンプラリー",
                    'url' => route('rallies.participants.show', [$rally, $user]),
                    'size' => 'xs',
                ])
            </div>
        </div>

        <!-- Progress Overview -->
        @php
            $totalCount = $rally->locations->count();
            $requiredCount = $rally->requiredCount();
            $stampedCount = $userStampsByLocation->count();
            $progressPercent = $requiredCount > 0 ? min(100, round(($stampedCount / $requiredCount) * 100)) : 0;
        @endphp
        <div class="space-y-2 bg-slate-50 rounded-xl p-4 border border-slate-200">
            <div class="flex items-center justify-between text-xs font-bold">
                <span class="text-slate-700">
                    スタンプ獲得状況
                    @if ($rally->completion_condition === 'count')
                        （全{{ $totalCount }}箇所中 {{ $requiredCount }}個で達成）
                    @else
                        （全{{ $totalCount }}箇所制覇で達成）
                    @endif
                </span>
                <span class="text-indigo-600 font-extrabold">{{ $stampedCount }} / {{ $requiredCount }} 獲得 ({{ $progressPercent }}%)</span>
            </div>
            <div class="w-full h-3 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-600 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
            </div>
            @if ($participation->completed_at)
                <p class="text-[11px] text-amber-700 font-bold mt-1">
                    ✨ {{ $participation->completed_at->format('Y年m月d日 H:i') }} に達成条件を満たしました！
                </p>
            @endif
        </div>
    </div>

    <!-- Stamp Collection Grid for this Participant -->
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span>💮</span>
            <span>スタンプ獲得一覧</span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($rally->locations as $location)
                @php
                    $stamp = $userStampsByLocation->get($location->id);
                @endphp
                <div class="bg-white rounded-2xl border-2 {{ $stamp ? 'border-indigo-400 bg-indigo-50/20 shadow-xs' : 'border-slate-200 opacity-60' }} p-4 flex items-start gap-3.5">
                    <!-- Stamp Visual -->
                    <div class="w-16 h-16 rounded-2xl flex flex-col items-center justify-center shrink-0 border-2 overflow-hidden {{ $stamp ? 'bg-white shadow-xs' : 'bg-slate-50 border-dashed border-slate-300' }}"
                         style="{{ $stamp ? 'border-color:' . $location->stamp_color . ';' : '' }}">
                        @if ($location->hasCustomImage())
                            <img src="{{ $location->iconImageUrl() }}" alt="{{ $location->name }}" class="w-9 h-9 object-contain">
                        @else
                            <span class="text-2xl">{{ $location->iconEmoji() }}</span>
                        @endif
                        <span class="text-[9px] font-extrabold mt-0.5 tracking-wider {{ $stamp ? 'text-indigo-600' : 'text-slate-400' }}">
                            {{ $stamp ? 'STAMPED' : 'UNSTAMPED' }}
                        </span>
                    </div>

                    <div class="flex-1 min-w-0 space-y-1">
                        <h3 class="text-sm font-bold text-slate-900 truncate">{{ $location->name }}</h3>
                        <p class="text-xs text-slate-500">
                            {{ $location->availabilityLabel() }}
                        </p>
                        @if ($stamp)
                            <div class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded inline-block">
                                💮 {{ $stamp->stamped_at->format('Y/m/d H:i') }} 獲得
                            </div>
                        @else
                            <div class="text-[11px] font-medium text-slate-400 bg-slate-100 px-2 py-0.5 rounded inline-block">
                                未獲得
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
