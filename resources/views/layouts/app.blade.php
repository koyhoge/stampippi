<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Stampippi - Webスタンプラリープラットフォーム')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col pb-16 sm:pb-0">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-extrabold text-lg sm:text-xl text-indigo-600 tracking-tight">
                    <img src="{{ asset('favicon.svg') }}" alt="Stampippi" class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg shadow-xs">
                    <span>Stampippi</span>
                </a>
                <nav class="hidden sm:flex items-center gap-4 text-sm font-medium text-slate-600">
                    @auth
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'text-indigo-600 font-bold' : 'hover:text-indigo-600' }} transition">
                            📖 マイページ
                        </a>
                        @if (auth()->user()->canAccessManagement())
                            <a href="{{ route('my.index') }}" class="{{ request()->routeIs('my.index', 'my.rallies.*', 'my.series.*') ? 'text-indigo-600 font-bold' : 'hover:text-indigo-600' }} transition">
                                ⚙️ ラリー管理
                            </a>
                            @if (auth()->user()->canManageAnyGroup())
                                <a href="{{ route('my.groups.index') }}" class="{{ request()->routeIs('my.groups.*') ? 'text-indigo-600 font-bold' : 'hover:text-indigo-600' }} transition">
                                    👥 グループ管理
                                </a>
                            @endif
                        @endif
                    @endauth
                </nav>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-xs sm:text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 px-2 sm:px-3 py-1 rounded-full transition">
                        @if (auth()->user()->avatarUrl())
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="w-6 h-6 rounded-full object-cover border border-slate-300 shrink-0">
                        @else
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        @endif
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
            <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3.5 text-rose-800 text-sm space-y-1.5">
                <div class="font-bold flex items-center gap-1.5">
                    <span>⚠️</span>
                    <span>入力内容または操作を確認してください</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm">
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
            <div><a href="https://github.com/koyhoge/stampippi" target="_blank" rel="noopener noreferrer" class="font-bold text-slate-700 hover:text-indigo-600 transition underline underline-offset-2">Stampippi</a> — みんなでつくる・参加するGPSスタンプラリープラットフォーム</div>
            <div class="flex items-center gap-4">
                <a href="{{ route('home') }}" class="hover:text-slate-800">トップ</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:text-slate-800">マイページ</a>
                    @if (auth()->user()->canAccessManagement())
                        <a href="{{ route('my.index') }}" class="hover:text-slate-800">ラリー管理</a>
                    @endif
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
            @if (auth()->user()->canAccessManagement())
                <a href="{{ route('my.index') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-[11px] font-semibold {{ request()->routeIs('my.*') ? 'text-indigo-600' : 'text-slate-600' }}">
                    <span class="text-base leading-none">⚙️</span>
                    <span class="mt-0.5">ラリー管理</span>
                </a>
            @endif
            <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-[11px] font-semibold {{ request()->routeIs('profile.*') ? 'text-indigo-600' : 'text-slate-600' }}">
                @if (auth()->user()->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="w-5 h-5 rounded-full object-cover">
                @else
                    <span class="text-base leading-none">👤</span>
                @endif
                <span class="mt-0.5">プロフィール</span>
            </a>
        @else
            <a href="{{ route('home') }}" class="flex flex-col items-center justify-center flex-1 py-1 text-[11px] font-semibold {{ request()->routeIs('home', 'login') ? 'text-indigo-600' : 'text-slate-600' }}">
                <span class="text-base leading-none">🔑</span>
                <span class="mt-0.5">ログイン</span>
            </a>
        @endauth
    </nav>

    <script>
        function shareStampippi(title, text, url) {
            const shareData = {
                title: title,
                text: text,
                url: url || window.location.href
            };
            if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
                navigator.share(shareData).catch(() => {});
            } else {
                const copyText = (text ? text + '\n' : '') + shareData.url;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(copyText).then(() => {
                        alert('共有用テキストとURLをクリップボードにコピーしました！');
                    }).catch(() => {
                        window.prompt('以下のURLをコピーしてシェアしてください:', shareData.url);
                    });
                } else {
                    window.prompt('以下のURLをコピーしてシェアしてください:', shareData.url);
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>
