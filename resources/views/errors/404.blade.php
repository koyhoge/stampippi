@extends('layouts.app')

@section('title', 'ページが見つかりません (404) | Stampippi')

@section('content')
<div class="max-w-lg mx-auto py-10">
    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-4 shadow-sm">
        <div class="text-4xl">🗺️</div>
        <h1 class="text-xl font-extrabold text-slate-900">お探しのページが見つかりませんでした</h1>
        <p class="text-sm text-slate-600 leading-relaxed">
            URLが間違っているか、スタンプラリー・ベニューが削除または非公開（下書き）になっている可能性があります。
        </p>
        <div class="pt-3 flex items-center justify-center gap-3">
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">
                {{ auth()->check() ? 'マイページへ戻る' : 'トップへ戻る' }}
            </a>
        </div>
    </div>
</div>
@endsection

