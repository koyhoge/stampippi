@extends('layouts.app')

@section('title', 'コンプリート達成証 - ' . $rally->title . ' | Stampippi')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-3xl border-4 border-amber-400 shadow-xl p-8 sm:p-10 text-center space-y-6 relative overflow-hidden">
        <div class="text-5xl">🏆</div>
        <div class="space-y-1">
            <div class="text-xs font-bold tracking-widest uppercase text-amber-600">STAMP RALLY COMPLETION CERTIFICATE</div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">コンプリート達成証</h1>
        </div>

        <div class="py-4 border-y border-slate-200 space-y-2">
            <p class="text-sm text-slate-500">達成者</p>
            <p class="text-xl font-bold text-indigo-700">
                {{ auth()->user()->name }} <span class="text-sm font-normal text-slate-500">({{ '@' . auth()->user()->handle }})</span> 殿
            </p>
            <p class="text-sm text-slate-600 pt-2">
                あなたは <strong>「{{ $rally->title }}」</strong> において、<br>
                所定のスタンプロケーションを巡り見事コンプリートを達成されました。
            </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-left">
            @foreach ($userStamps as $stamp)
                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-3 flex items-center gap-2.5">
                    <span class="text-2xl">{{ $stamp->location->iconEmoji() }}</span>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-800 truncate">{{ $stamp->location->name }}</div>
                        <div class="text-[11px] text-slate-500">{{ $stamp->stamped_at->format('Y/m/d H:i') }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-xs text-slate-500 pt-2">
            達成日時: {{ $participation->completed_at->format('Y年m月d日 H:i') }} ／ 主催: {{ $rally->ownerName() }}
        </div>

        <div class="pt-4">
            <a href="{{ route('rallies.show', $rally) }}" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition">
                ← スタンプラリー画面に戻る
            </a>
        </div>
    </div>
</div>
@endsection

