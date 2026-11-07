@extends('layouts.app')

@section('title', 'Organization 作成 | Stampippi')

@section('content')
<div class="max-w-xl mx-auto bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900">🏢 Organization（グループ）を作成</h1>
        <p class="text-xs text-slate-500 mt-1">
            ラリーシリーズや複数のスタンプラリーをチームで共同運営・引き継ぎするためのグループを作成します。
        </p>
    </div>

    <form method="POST" action="{{ route('my.organizations.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Organization ID (slug) ※半角英数・ハイフン</label>
            <input type="text" name="slug" value="{{ old('slug') }}" required placeholder="sample-org" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">表示名</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="イベントスタンプラリー運営事務局" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">説明文</label>
            <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">{{ old('description') }}</textarea>
        </div>
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('dashboard') }}" class="text-xs text-slate-500 hover:text-slate-800">キャンセル</a>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl">
                作成する
            </button>
        </div>
    </form>
</div>
@endsection

