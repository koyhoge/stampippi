@extends('layouts.app')

@section('title', 'アクセス権限がありません (403) | Stampippi')

@section('content')
<div class="max-w-lg mx-auto py-10">
    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-4 shadow-sm">
        <div class="text-4xl">🔒</div>
        <h1 class="text-xl font-extrabold text-slate-900">この操作を行う権限がありません</h1>
        <p class="text-sm text-slate-600 leading-relaxed">
            {{ $exception->getMessage() ?: 'アクセスしようとしたページまたは操作には、管理者権限または対象グループのメンバー権限が必要です。' }}
        </p>
        <div class="pt-3 flex items-center justify-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">
                    マイページへ戻る
                </a>
            @else
                <a href="{{ route('home') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">
                    ログイン画面へ戻る
                </a>
            @endauth
        </div>
    </div>
</div>
@endsection

