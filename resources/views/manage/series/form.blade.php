@extends('layouts.app')

@section('title', ($isEdit ? 'ラリーシリーズ編集' : 'ラリーシリーズ作成') . ' | Stampippi')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">
                    📚 {{ $isEdit ? 'ラリーシリーズを編集' : '新しいラリーシリーズを作成' }}
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    複数年・複数回のスタンプラリーを束ねるシリーズ枠を作成・管理します。
                </p>
            </div>
            @if ($isEdit)
                <a href="{{ route('series.show', $series) }}" class="text-xs font-semibold text-indigo-600 hover:underline">公開ページを見る →</a>
            @endif
        </div>

        <form method="POST" action="{{ $isEdit ? route('my.series.update', $series) : route('my.series.store') }}" class="space-y-4">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">所有オーナー（個人 または グループ）</label>
                <select name="group_id" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                    <option value="">👤 個人所有 ({{ '@' . auth()->user()->handle }})</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected(old('group_id', $series->group_id) == $group->id)>
                            👥 グループ: {{ $group->name }} ({{ '@' . $group->slug }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">シリーズタイトル</label>
                    <input type="text" name="title" value="{{ old('title', $series->title) }}" required placeholder="全国イベントスタンプラリーシリーズ" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">URLスラッグ (半角英数・ハイフン)</label>
                    <input type="text" name="slug" value="{{ old('slug', $series->slug) }}" required placeholder="event-rally-series" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">説明文</label>
                <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">{{ old('description', $series->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">公開ステータス</label>
                <select name="visibility" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                    <option value="public" @selected(old('visibility', $series->visibility) === 'public')>全体公開 (public)</option>
                    <option value="unlisted" @selected(old('visibility', $series->visibility) === 'unlisted')>限定公開・URLを知っている人のみ (unlisted)</option>
                    <option value="draft" @selected(old('visibility', $series->visibility) === 'draft')>下書き・非公開 (draft)</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('my.index') }}" class="text-xs text-slate-500 hover:text-slate-800">ラリー管理へ戻る</a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl">
                    {{ $isEdit ? '更新する' : 'シリーズを作成する' }}
                </button>
            </div>
        </form>
    </div>

    @if ($isEdit)
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900">このシリーズに属するスタンプラリー ({{ $series->rallies->count() }}件)</h2>
                @if (auth()->user()->canCreateRally())
                    <a href="{{ route('my.rallies.create', ['series_id' => $series->id, 'group_id' => $series->group_id]) }}" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3.5 py-2 rounded-xl">
                        ＋ このシリーズに新しいスタンプラリーを追加
                    </a>
                @endif
            </div>
            @foreach ($series->rallies as $r)
                <div class="flex items-center justify-between py-2 border-t border-slate-100 text-sm">
                    <div>
                        <span class="font-bold text-slate-900">{{ $r->title }}</span>
                        <span class="text-xs text-slate-500 ml-2">({{ $r->locations->count() }} ベニュー / {{ $r->periodLabel() }})</span>
                    </div>
                    <a href="{{ route('my.rallies.edit', $r) }}" class="text-xs font-bold text-indigo-600 hover:underline">管理・ベニュー編集 →</a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

