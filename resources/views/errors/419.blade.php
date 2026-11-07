@extends('layouts.app')

@section('title', 'セッションの有効期限が切れました (419) | Stampippi')

@section('content')
<div class="max-w-lg mx-auto py-10">
    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center space-y-4 shadow-sm">
        <div class="text-4xl">⏳</div>
        <h1 class="text-xl font-extrabold text-slate-900">ページの有効期限が切れました</h1>
        <p class="text-sm text-slate-600 leading-relaxed">
            しばらく操作がなかったため、セキュリティ保護によりセッションがタイムアウトしました。前の画面に戻ってページを再読み込みしてからもう一度お試しください。
        </p>
        <div class="pt-3 flex items-center justify-center gap-3">
            <a href="{{ url()->previous() ?: route('home') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">
                前のページへ戻る
            </a>
        </div>
    </div>
</div>
@endsection

