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

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- User Avatar Section -->
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5 space-y-4">
                <label class="block text-xs font-bold text-slate-700">プロフィールアイコン</label>
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="shrink-0">
                        @if ($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-full object-cover border-2 border-indigo-200 shadow-sm bg-white">
                        @else
                            <div class="w-20 h-20 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-2xl shadow-sm">
                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 space-y-2">
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif"
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        <p class="text-[11px] text-slate-500">
                            JPEG, PNG, WebP, GIF形式（最大 2MB）。アップロードすると独自アイコンとして上書き保存されます。
                        </p>
                        @if ($user->hasCustomAvatar())
                            <div class="pt-1 flex items-center gap-2">
                                <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">独自アイコン設定中</span>
                                @if ($user->avatar_url)
                                    <label class="inline-flex items-center gap-1.5 text-xs text-rose-600 font-semibold cursor-pointer hover:underline">
                                        <input type="checkbox" name="remove_avatar" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                        <span>削除して連携元 (OAuth) のアイコンに戻す</span>
                                    </label>
                                @endif
                            </div>
                        @elseif ($user->avatar_url)
                            <div class="pt-0.5">
                                <span class="text-[11px] font-medium text-slate-500 bg-slate-200/60 px-2 py-0.5 rounded">連携サービス (OAuth) のアイコンを使用中</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

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

