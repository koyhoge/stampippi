@extends('layouts.app')

@section('title', 'プロフィール設定 | Stampippi')

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 space-y-6 shadow-sm">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">👤 プロフィール設定</h1>
            <p class="text-xs text-slate-500 mt-1">
                ユーザーID（@handle）と表示名を変更できます。グループへのメンバー追加時はここで設定した <strong>{{ '@' . $user->handle }}</strong> を使用します。
            </p>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">ユーザーID (@handle) ※半角英数・ハイフン・アンダースコア</label>
                <div class="flex items-center">
                    <span class="bg-slate-100 border border-r-0 border-slate-300 rounded-l-xl px-3 py-2 text-sm text-slate-500">@</span>
                    <input type="text" name="handle" value="{{ old('handle', $user->handle) }}" required class="w-full rounded-r-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">表示名</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">登録メールアドレス</label>
                <input type="email" value="{{ $user->email }}" disabled class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-500 px-3.5 py-2 text-sm cursor-not-allowed">
            </div>

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('dashboard') }}" class="text-xs text-slate-500 hover:text-slate-800">← マイページへ戻る</a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl transition">
                    保存する
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

