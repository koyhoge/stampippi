<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Stampippi - Webスタンプラリープラットフォーム')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col pb-16 sm:pb-0">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-1.5 font-extrabold text-lg sm:text-xl text-indigo-600 tracking-tight">
                    <span class="text-xl sm:text-2xl">💮</span>
                    <span>Stampippi</span>
                </a>
                <nav class="hidden sm:flex items-center gap-4 text-sm font-medium text-slate-600">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition">マイページ・管理</a>
                        <a href="{{ route('my.rallies.create') }}" class="hover:text-indigo-600 transition">＋ラリー作成</a>
                    @endauth
                </nav>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 px-2.5 sm:px-3 py-1.5 rounded-full transition">
                        <span class="font-bold text-indigo-600">{{ '@' . auth()->user()->handle }}</span>
                        <span class="hidden md:inline text-slate-500">({{ auth()->user()->name }})</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="hidden sm:inline-block text-xs font-medium text-slate-600 hover:text-indigo-600 px-2 py-1.5 rounded-lg hover:bg-slate-100 transition">
                        プロフィール設定
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:text-red-600 px-1.5 sm:px-2 py-1">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-semibold px-3.5 sm:px-4 py-2 rounded-xl shadow-sm transition">
                        ログイン / 登録
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800 text-sm font-medium flex items-center justify-between">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 mt-12 py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <div>Stampippi — みんなでつくる・参加するGPSスタンプラリープラットフォーム</div>
            <div class="flex items-center gap-4">
                <a href="{{ route('home') }}" class="hover:text-slate-800">トップ</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:text-slate-800">マイページ</a>
                    <a href="{{ route('profile.edit') }}" class="hover:text-slate-800">プロフィール設定</a>
                @else
                    <a href="{{ route('login') }}" class="hover:text-slate-800">ログイン</a>
                @endauth
            </div>
        </div>
    </footer>

    <!-- Smartphone Bottom Navigation Bar -->
    <nav class="sm:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur border-t border-slate-200 z-50 flex items-center justify-around h-14 px-2 shadow-lg">
        @auth
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-[11px] font-semibold {{ request()->routeIs('dashboard') ? 'text-indigo-600' : 'text-slate-600' }}">
                <span class="text-base leading-none">📖</span>
                <span class="mt-0.5">マイページ</span>
            </a>
            <a href="{{ route('my.rallies.create') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-[11px] font-semibold {{ request()->routeIs('my.rallies.create') ? 'text-indigo-600' : 'text-slate-600' }}">
                <span class="text-base leading-none">➕</span>
                <span class="mt-0.5">ラリー作成</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-[11px] font-semibold {{ request()->routeIs('profile.*') ? 'text-indigo-600' : 'text-slate-600' }}">
                <span class="text-base leading-none">👤</span>
                <span class="mt-0.5">プロフィール</span>
            </a>
        @else
            <a href="{{ route('home') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-[11px] font-semibold {{ request()->routeIs('home', 'login') ? 'text-indigo-600' : 'text-slate-600' }}">
                <span class="text-base leading-none">🔑</span>
                <span class="mt-0.5">ログイン</span>
            </a>
        @endauth
    </nav>

    @stack('scripts')
</body>
</html>

