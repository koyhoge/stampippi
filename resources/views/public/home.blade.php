@extends('layouts.app')

@section('title', 'Stampippi - ログイン')

@section('content')
<div class="max-w-md mx-auto py-6 sm:py-12 space-y-6">
    <div class="text-center space-y-2">
        <div class="text-5xl">💮</div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Stampippi</h1>
        <p class="text-xs sm:text-sm text-slate-500">
            Webスタンプラリープラットフォーム
        </p>
    </div>

    <!-- Google OAuth Login -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
        <a href="{{ route('auth.google.redirect') }}" class="flex items-center justify-center gap-2.5 w-full bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 font-bold text-sm px-5 py-3 rounded-xl shadow-sm transition">
            <span>🌐 Google アカウントでログイン</span>
        </a>

        <div class="relative flex py-1 items-center">
            <div class="flex-grow border-t border-slate-200"></div>
            <span class="flex-shrink mx-3 text-xs text-slate-400">または</span>
            <div class="flex-grow border-t border-slate-200"></div>
        </div>

        <!-- @handle / Email Login Form -->
        <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">@handle または メールアドレス</label>
                <input type="text" name="login" value="{{ old('login') }}" required placeholder="@handle / email" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">パスワード</label>
                <input type="password" name="password" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-3 rounded-xl shadow-sm transition">
                ログイン
            </button>
        </form>

        <div class="pt-2 border-t border-slate-100 text-center">
            <a href="{{ route('login') }}" class="text-xs font-semibold text-indigo-600 hover:underline">
                新規アカウント作成はこちら →
            </a>
        </div>
    </div>

    @if ($devUsers->isNotEmpty())
        <details class="bg-amber-50 border border-amber-300 rounded-2xl p-4 text-xs">
            <summary class="font-bold text-amber-900 cursor-pointer flex items-center justify-between">
                <span>🚀 開発用ワンクリックログイン</span>
                <span class="text-[10px] bg-amber-200 text-amber-900 px-1.5 py-0.5 rounded font-bold">LOCAL</span>
            </summary>
            <div class="flex flex-col gap-2 pt-3 mt-2 border-t border-amber-200">
                @foreach ($devUsers as $devUser)
                    <form method="POST" action="{{ route('dev.login') }}">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $devUser->id }}">
                        <button type="submit" class="w-full bg-white hover:bg-amber-100 text-slate-800 border border-amber-300 text-xs font-bold px-3 py-2 rounded-xl transition flex items-center justify-between">
                            <span class="text-indigo-600">{{ '@' . $devUser->handle }}</span>
                            <span class="text-slate-500">{{ $devUser->name }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </details>
    @endif
</div>
@endsection
