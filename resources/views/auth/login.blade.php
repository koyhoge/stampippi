@extends('layouts.app')

@section('title', 'ログイン / アカウント登録 | Stampippi')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <div class="text-center space-y-2">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Stampippi にログイン</h1>
        <p class="text-sm text-slate-600">
            複数のスタンプラリーシリーズへの参加記録や獲得スタンプをアカウントで一元管理できます。
        </p>
    </div>

    @if ($devUsers->isNotEmpty())
        <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-6 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-extrabold text-amber-900">🚀 ローカル開発用 ワンクリックログイン</h2>
                <span class="text-[10px] font-bold bg-amber-200 text-amber-900 px-2 py-0.5 rounded">LOCAL DEV</span>
            </div>
            <p class="text-xs text-amber-800">
                Google OAuth の設定前でも、シード済みのテストユーザーで即座にログインして全機能（グループ管理・ラリー作成・参加打刻）を検証できます。
            </p>
            <div class="flex flex-wrap gap-3 pt-1">
                @foreach ($devUsers as $devUser)
                    <form method="POST" action="{{ route('dev.login') }}">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $devUser->id }}">
                        <button type="submit" class="bg-white hover:bg-amber-100 text-slate-800 border border-amber-400 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition flex items-center gap-2">
                            <span class="text-indigo-600">{{ '@' . $devUser->handle }}</span>
                            <span>({{ $devUser->name }}) でログイン</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Google OAuth Button -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 text-center space-y-3 shadow-sm">
        <a href="{{ route('auth.google.redirect') }}" class="inline-flex items-center justify-center gap-3 w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 font-bold text-sm px-6 py-3 rounded-xl shadow-sm transition">
            <span>🌐 Google アカウントでログイン / 新規登録</span>
        </a>
        <p class="text-xs text-slate-400">Google OAuth 2.0 (Laravel Socialite)</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Login Form -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-slate-900">🔑 @handle / メールアドレスでログイン</h2>
            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">@handle または メールアドレス</label>
                    <input type="text" name="login" value="{{ old('login') }}" required placeholder="koyhoge または user@example.com" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">パスワード</label>
                    <input type="password" name="password" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-2.5 rounded-xl transition">
                    ログイン
                </button>
            </form>
        </div>

        <!-- Register Form -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-slate-900">✨ 新規アカウント作成</h2>
            <form method="POST" action="{{ route('register.submit') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">ユーザーID (@handle) ※半角英数・ハイフン・アンダースコア</label>
                    <div class="flex items-center">
                        <span class="bg-slate-100 border border-r-0 border-slate-300 rounded-l-xl px-3 py-2 text-sm text-slate-500">@</span>
                        <input type="text" name="handle" value="{{ old('handle') }}" required placeholder="your_handle" class="w-full rounded-r-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">表示名</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="山田 太郎" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">メールアドレス</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">パスワード (6文字以上)</label>
                        <input type="password" name="password" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">パスワード確認</label>
                        <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                    </div>
                </div>
                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm py-2.5 rounded-xl transition">
                    登録してはじめる
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

