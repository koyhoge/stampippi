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

    <!-- Social OAuth Login -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
        <div class="space-y-2.5">
            <a href="{{ route('auth.google.redirect') }}" class="flex items-center justify-center gap-2.5 w-full bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 font-bold text-sm px-5 py-3 rounded-xl shadow-sm transition">
                <span>🌐 Google アカウントでログイン</span>
            </a>

            <a href="{{ route('auth.github.redirect') }}" class="flex items-center justify-center gap-2.5 w-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm px-5 py-3 rounded-xl shadow-sm transition">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                </svg>
                <span>GitHub アカウントでログイン</span>
            </a>
        </div>

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
