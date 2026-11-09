@extends('layouts.app')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/flatpickr@4.6.13/dist/flatpickr.min.css">
<script src="https://unpkg.com/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://unpkg.com/flatpickr@4.6.13/dist/l10n/ja.js"></script>
<style>
    .flatpickr-time {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    }
    .flatpickr-calendar {
        z-index: 99999 !important;
    }
    /* Hide spin buttons for input fields */
    input.no-spin::-webkit-outer-spin-button,
    input.no-spin::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input.no-spin[type=number] {
        -moz-appearance: textfield;
    }
</style>
@endpush

@section('title', ($isEdit ? $rally->title . ' の管理' : 'スタンプラリー作成') . ' | Stampippi')

@section('content')
<div class="space-y-8">
    <!-- Basic Rally Form -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">
                    🗺️ {{ $isEdit ? 'スタンプラリー設定・ベニュー管理' : '新しいスタンプラリーを作成' }}
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    開催期間を指定しない「常設ラリー」としても、期間付きのイベントラリーとしても作成できます。
                </p>
            </div>
            @if ($isEdit)
                <a href="{{ route('rallies.show', $rally) }}" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold px-4 py-2 rounded-xl">
                    公開ページ・スタンプ帳を確認する →
                </a>
            @endif
        </div>

        <form method="POST" action="{{ $isEdit ? route('my.rallies.update', $rally) : route('my.rallies.store') }}" class="space-y-4">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            @php
                $canTransfer = ! $isEdit || auth()->user()->canTransferRallyOwnership($rally);
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">所有オーナー（グループ / 個人）</label>
                    @if ($canTransfer)
                        <select name="group_id" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-indigo-500">
                            <option value="">👤 個人所有 ({{ '@' . ($rally->creator?->handle ?? auth()->user()->handle) }})</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}" @selected(old('group_id', $rally->group_id) == $group->id)>
                                    👥 グループ: {{ $group->name }} ({{ '@' . $group->slug }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">
                            個人所有とあなたの所属グループ間で所有権限を移管できます。
                        </p>
                    @else
                        <input type="hidden" name="group_id" value="{{ $rally->group_id }}">
                        <div class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-sm text-slate-700 flex items-center justify-between">
                            <span>👥 グループ: {{ $rally->group?->name ?? '所属グループ' }}</span>
                            <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded font-semibold">変更不可</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            ※ 所有オーナーの変更は作成者本人（{{ '@' . ($rally->creator?->handle ?? '作成者') }}）のみ可能です。
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        所属ラリーシリーズ（任意・単発ラリーなら未選択）
                    </label>
                    <select name="rally_series_id" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- シリーズなし（単発スタンプラリー） --</option>
                        @foreach ($seriesList as $s)
                            <option value="{{ $s->id }}" @selected(old('rally_series_id', $rally->rally_series_id) == $s->id)>
                                📚 {{ $s->title }}{{ $s->group ? ' (' . $s->group->name . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">スタンプラリー名</label>
                    <input type="text" name="title" value="{{ old('title', $rally->title) }}" required placeholder="春の街歩きスタンプラリー 2026" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">URLスラッグ (半角英数・ハイフン)</label>
                    <input type="text" name="slug" value="{{ old('slug', $rally->slug) }}" required placeholder="spring-walk-2026" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">説明文</label>
                <textarea name="description" rows="2" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">{{ old('description', $rally->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">公開ステータス</label>
                    <select name="visibility" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="public" @selected(old('visibility', $rally->visibility) === 'public')>全体公開 (public)</option>
                        <option value="unlisted" @selected(old('visibility', $rally->visibility) === 'unlisted')>限定公開 (unlisted)</option>
                        <option value="draft" @selected(old('visibility', $rally->visibility) === 'draft')>下書き (draft)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">参加者の表示設定</label>
                    <select name="participant_display_mode" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="icons" @selected(old('participant_display_mode', $rally->participant_display_mode ?? 'icons') === 'icons')>👥 アイコンと人数を表示</option>
                        <option value="count_only" @selected(old('participant_display_mode', $rally->participant_display_mode ?? 'icons') === 'count_only')>🔢 人数のみ表示</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">コンプリート条件</label>
                    <select name="completion_condition" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="all" @selected(old('completion_condition', $rally->completion_condition) === 'all')>全ベニュー制覇で達成</option>
                        <option value="count" @selected(old('completion_condition', $rally->completion_condition) === 'count')>指定数以上の獲得で達成</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">必要獲得数 (指定数達成時のみ)</label>
                    <input type="number" min="1" name="required_locations_count" value="{{ old('required_locations_count', $rally->required_locations_count) }}" placeholder="例: 3" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">開催開始日時（24時間表記・空欄なら常設）</label>
                    <input type="text" name="starts_at" value="{{ old('starts_at', $rally->starts_at?->format('Y-m-d H:i')) }}" placeholder="例: 2026-05-10 09:00" class="flatpickr-datetime w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm bg-white font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">開催終了日時（24時間表記・空欄なら常設）</label>
                    <input type="text" name="ends_at" value="{{ old('ends_at', $rally->ends_at?->format('Y-m-d H:i')) }}" placeholder="例: 2026-05-10 18:00" class="flatpickr-datetime w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm bg-white font-mono">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-6 py-2.5 rounded-xl">
                    {{ $isEdit ? '基本設定を保存する' : '作成してベニュー登録へ進む' }}
                </button>
            </div>
        </form>
    </div>

    @if ($isEdit)
        <!-- Registered Venues Section (Directly under Stamp Rally settings) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900 flex items-center gap-2">
                        <span>📍</span>
                        <span>スタンプロケーション（ベニュー）一覧</span>
                        <span class="text-xs font-bold bg-indigo-50 text-indigo-700 px-2.5 py-0.5 rounded-full">
                            {{ $rally->locations->count() }} 箇所
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        参加者がチェックインしてスタンプを獲得する各スポットです。
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button type="button" onclick="openCreateVenueModal()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition">
                        <span>＋</span>
                        <span>新規ベニューを登録</span>
                    </button>
                </div>
            </div>

            @if ($rally->locations->isEmpty())
                <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <p class="text-3xl mb-2">📍</p>
                    <p class="text-sm font-semibold text-slate-700">まだベニューが登録されていません</p>
                    <p class="text-xs text-slate-500 mt-1">「＋ 新規ベニューを登録」ボタンからチェックイン地点を追加しましょう。</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($rally->locations as $loc)
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <!-- Venue Summary Row -->
                            <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/60">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-11 h-11 rounded-xl border-2 flex items-center justify-center shrink-0 bg-white shadow-sm overflow-hidden"
                                         style="border-color: {{ $loc->stamp_color }};">
                                        @if ($loc->hasCustomImage())
                                            <img src="{{ $loc->iconImageUrl() }}" alt="{{ $loc->name }}" class="w-9 h-9 object-contain">
                                        @else
                                            <span class="text-2xl">{{ $loc->iconEmoji() }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-bold text-slate-900 text-sm sm:text-base truncate">{{ $loc->name }}</h3>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-200/80 text-slate-700">
                                                半径 {{ $loc->radius_meters }}m
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 mt-1 font-mono">
                                            <span>緯度: {{ number_format($loc->latitude, 6) }}</span>
                                            <span>経度: {{ number_format($loc->longitude, 6) }}</span>
                                            <span class="font-sans text-indigo-600 bg-indigo-50 px-1.5 py-0.2 rounded font-semibold text-[11px]">
                                                期間: {{ $loc->availabilityLabel() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                    <button type="button"
                                        onclick="openEditVenueModal({{ json_encode([
                                            'id' => $loc->id,
                                            'name' => $loc->name,
                                            'latitude' => (float)$loc->latitude,
                                            'longitude' => (float)$loc->longitude,
                                            'radius_meters' => (int)$loc->radius_meters,
                                            'stamp_color' => $loc->stamp_color,
                                            'stamp_icon_value' => $loc->stamp_icon_value,
                                            'has_custom_image' => $loc->hasCustomImage(),
                                            'available_from' => $loc->available_from?->format('Y-m-d H:i') ?? '',
                                            'available_until' => $loc->available_until?->format('Y-m-d H:i') ?? '',
                                            'description' => $loc->description ?? '',
                                            'update_url' => route('my.rallies.locations.update', [$rally, $loc]),
                                        ]) }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-sm transition">
                                        <span>✏️</span>
                                        <span>マップで編集</span>
                                    </button>
                                    <form method="POST" action="{{ route('my.rallies.locations.destroy', [$rally, $loc]) }}" onsubmit="return confirm('このベニュー「{{ $loc->name }}」を削除しますか？')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition">
                                            <span>🗑️</span>
                                            <span>削除</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Venue Modal Dialog (Create & Edit with Map Picker) -->
        <div id="venue-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="venue-modal-title" role="dialog" aria-modal="true">
            <!-- Modal Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeVenueModal()"></div>

            <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-4xl border border-slate-200">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 bg-slate-50">
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold text-slate-900 flex items-center gap-2" id="venue-modal-title">
                                <span>📍</span>
                                <span id="venue-modal-title-text">新規ベニュー（スタンプロケーション）の追加</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5" id="venue-modal-subtitle">
                                右側の地図をクリックするか住所・施設名で検索して位置を決定してください。
                            </p>
                        </div>
                        <button type="button" onclick="closeVenueModal()" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition">
                            <span class="sr-only">閉じる</span>
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body (Form) -->
                    <form id="venue-modal-form" method="POST" action="{{ route('my.rallies.locations.store', $rally) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_method" id="venue-form-method" value="POST">

                        <div class="p-6 space-y-6 max-h-[calc(85vh-130px)] overflow-y-auto">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Left Column: Venue Details -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            ベニュー名 <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="text" id="venue-name" name="name" required placeholder="例: 正門インフォメーション / 受付ブース" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            位置情報（緯度 / 経度） <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <span class="text-[10px] text-slate-500">緯度 (Latitude)</span>
                                                <input type="text" inputmode="decimal" id="venue-lat" name="latitude" value="{{ old('latitude', 35.681236) }}" required class="no-spin w-full rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-mono">
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-slate-500">経度 (Longitude)</span>
                                                <input type="text" inputmode="decimal" id="venue-lng" name="longitude" value="{{ old('longitude', 139.767125) }}" required class="no-spin w-full rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-mono">
                                            </div>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-1">※ 右側の地図をクリックで自動入力されます。Google Map からコピーした座標（15桁や「緯度, 経度」形式）をそのまま貼り付けることも可能です（保存時に小数点以下6桁に丸められます）。</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                                チェックイン有効半径 (m) <span class="text-rose-500">*</span>
                                            </label>
                                            <input type="number" id="venue-radius" name="radius_meters" value="{{ old('radius_meters', 100) }}" min="10" max="10000" required class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm">
                                            <p class="text-[10px] text-slate-500 mt-0.5">推奨: 屋内・小規模は 50〜100m</p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                                スタンプ枠カラー <span class="text-rose-500">*</span>
                                            </label>
                                            <div class="flex items-center gap-2">
                                                <input type="color" id="venue-color" name="stamp_color" value="{{ old('stamp_color', '#6366F1') }}" class="w-10 h-10 rounded-xl border border-slate-300 cursor-pointer p-0.5 bg-white">
                                                <span class="text-xs text-slate-500">スタンプ枠とアイコンの色</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            スタンプ絵柄（プリセット絵文字）
                                        </label>
                                        <select id="venue-icon" name="stamp_icon_value" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                                            <option id="venue-custom-image-option" value="__custom__" class="hidden">🖼️ 現在のカスタム画像を使用中</option>
                                            @foreach ($presetIcons as $key => $icon)
                                                <option value="{{ $key }}" @selected(old('stamp_icon_value', 'star') === $key)>{{ $icon['emoji'] }} {{ $icon['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            <span id="venue-image-label">スタンプ画像 (任意)</span>
                                        </label>
                                        <input type="file" id="venue-image" name="stamp_icon_image" accept="image/*" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                        <p class="text-[10px] text-slate-500 mt-1" id="venue-image-help">画像をアップロードすると絵文字の代わりにスタンプ画像が使われます（編集時に変更しない場合は空欄）。</p>
                                    </div>

                                    <div class="border-t border-slate-200 pt-3">
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            限定開放期間 (24時間表記・空欄=ラリー開催期間中常設)
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <span class="text-[10px] text-slate-500">開始日時</span>
                                                <input type="text" id="venue-available-from" name="available_from" value="{{ old('available_from') }}" placeholder="2026-05-10 09:00" class="flatpickr-datetime w-full rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-mono">
                                            </div>
                                            <div>
                                                <span class="text-[10px] text-slate-500">終了日時</span>
                                                <input type="text" id="venue-available-until" name="available_until" value="{{ old('available_until') }}" placeholder="2026-05-10 18:00" class="flatpickr-datetime w-full rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-mono">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">
                                            説明・案内 (任意)
                                        </label>
                                        <textarea id="venue-description" name="description" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs" placeholder="ヒントや行き方、開場時間など">{{ old('description') }}</textarea>
                                    </div>
                                </div>

                                <!-- Right Column: Interactive Map & Search -->
                                <div class="space-y-3">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-slate-700">📍 住所・施設名で検索</label>
                                        <div class="flex gap-2">
                                            <input type="text" id="address-search-input" placeholder="例: 東京駅, 日本武道館, 渋谷区神南..." class="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-xs">
                                            <button type="button" id="address-search-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition shrink-0">
                                                🔍 検索
                                            </button>
                                        </div>
                                        <div id="address-search-results" class="hidden max-h-40 overflow-y-auto rounded-xl border border-slate-200 bg-white text-xs divide-y divide-slate-100 shadow-sm"></div>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between text-xs text-slate-600 mb-1">
                                            <span class="font-bold">🗺️ マップからピン指定</span>
                                            <span class="text-[11px] text-slate-400">クリックで位置設定</span>
                                        </div>
                                        <div id="picker-map" class="w-full h-80 sm:h-96 rounded-xl border border-slate-300 shadow-inner"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-200">
                            <button type="button" onclick="closeVenueModal()" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                キャンセル
                            </button>
                            <button type="submit" id="venue-modal-submit-btn" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700 shadow-sm transition">
                                ＋ ベニューを追加
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@if ($isEdit)
@push('scripts')
<script>
    const existingLocations = @json($rally->locations);
    const createVenueStoreUrl = "{{ route('my.rallies.locations.store', $rally) }}";

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    // Modal & Form Elements
    const venueModal = document.getElementById('venue-modal');
    const venueForm = document.getElementById('venue-modal-form');
    const venueFormMethod = document.getElementById('venue-form-method');
    const modalTitleText = document.getElementById('venue-modal-title-text');
    const modalSubtitle = document.getElementById('venue-modal-subtitle');
    const submitBtn = document.getElementById('venue-modal-submit-btn');

    const nameInput = document.getElementById('venue-name');
    const latInput = document.getElementById('venue-lat');
    const lngInput = document.getElementById('venue-lng');
    const radiusInput = document.getElementById('venue-radius');
    const colorInput = document.getElementById('venue-color');
    const iconSelect = document.getElementById('venue-icon');
    const customImageOption = document.getElementById('venue-custom-image-option');
    const imageInput = document.getElementById('venue-image');
    const descInput = document.getElementById('venue-description');

    let pickerMap = null;
    let previewMarker = null;
    let previewCircle = null;

    function setFlatpickrVal(elementId, val) {
        const el = document.getElementById(elementId);
        if (!el) return;
        if (el._flatpickr) {
            el._flatpickr.setDate(val || null, true);
        } else {
            el.value = val || '';
        }
    }

    function syncPreview() {
        if (!pickerMap || !previewMarker || !previewCircle) return;
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        const rad = parseInt(radiusInput ? radiusInput.value : 100, 10);
        if (!isNaN(lat) && !isNaN(lng)) {
            previewMarker.setLatLng([lat, lng]);
            previewCircle.setLatLng([lat, lng]);
            previewCircle.setRadius(rad);
        }
    }

    function showModalCommon(targetLat, targetLng) {
        venueModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        setTimeout(() => {
            if (!pickerMap) {
                initPickerMap(targetLat, targetLng);
            } else {
                pickerMap.invalidateSize();
                if (!isNaN(targetLat) && !isNaN(targetLng)) {
                    pickerMap.setView([targetLat, targetLng], 15);
                }
                syncPreview();
            }
        }, 150);
    }

    function openCreateVenueModal() {
        if (!venueModal) return;

        venueForm.action = createVenueStoreUrl;
        venueFormMethod.value = 'POST';
        modalTitleText.textContent = '新規ベニュー（スタンプロケーション）の追加';
        modalSubtitle.textContent = '右側の地図をクリックするか住所・施設名で検索して位置を決定してください。';
        submitBtn.textContent = '＋ ベニューを追加';

        nameInput.value = '';
        const defaultLat = existingLocations.length > 0 ? parseFloat(existingLocations[0].latitude) : 35.681236;
        const defaultLng = existingLocations.length > 0 ? parseFloat(existingLocations[0].longitude) : 139.767125;
        latInput.value = defaultLat.toFixed(7);
        lngInput.value = defaultLng.toFixed(7);
        radiusInput.value = 100;
        colorInput.value = '#6366F1';
        customImageOption.classList.add('hidden');
        iconSelect.value = 'star';
        imageInput.value = '';
        setFlatpickrVal('venue-available-from', '');
        setFlatpickrVal('venue-available-until', '');
        descInput.value = '';

        showModalCommon(defaultLat, defaultLng);
    }

    function openEditVenueModal(loc) {
        if (!venueModal) return;

        venueForm.action = loc.update_url;
        venueFormMethod.value = 'PUT';
        modalTitleText.textContent = `ベニュー「${loc.name}」の編集`;
        modalSubtitle.textContent = '位置情報やスタンプ設定を変更できます。右側の地図をクリックしてピン位置を調整できます。';
        submitBtn.textContent = '変更を保存';

        nameInput.value = loc.name || '';
        const lat = parseFloat(loc.latitude) || 35.681236;
        const lng = parseFloat(loc.longitude) || 139.767125;
        latInput.value = loc.latitude ?? lat;
        lngInput.value = loc.longitude ?? lng;
        radiusInput.value = loc.radius_meters || 100;
        colorInput.value = loc.stamp_color || '#6366F1';

        if (loc.has_custom_image) {
            customImageOption.classList.remove('hidden');
            iconSelect.value = '__custom__';
        } else {
            customImageOption.classList.add('hidden');
            iconSelect.value = loc.stamp_icon_value || 'star';
        }

        imageInput.value = '';
        setFlatpickrVal('venue-available-from', loc.available_from || '');
        setFlatpickrVal('venue-available-until', loc.available_until || '');
        descInput.value = loc.description || '';

        showModalCommon(lat, lng);
    }

    // Backwards compatibility
    function openVenueModal() {
        openCreateVenueModal();
    }

    function closeVenueModal() {
        if (!venueModal) return;
        venueModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && venueModal && !venueModal.classList.contains('hidden')) {
            closeVenueModal();
        }
    });

    function initPickerMap(initialLat, initialLng) {
        const defaultLat = initialLat || parseFloat(latInput.value) || 35.681236;
        const defaultLng = initialLng || parseFloat(lngInput.value) || 139.767125;

        pickerMap = L.map('picker-map').setView([defaultLat, defaultLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(pickerMap);

        // Render all existing locations in subdued blue
        existingLocations.forEach(loc => {
            L.marker([loc.latitude, loc.longitude]).addTo(pickerMap).bindPopup(escapeHtml(loc.name));
            L.circle([loc.latitude, loc.longitude], { radius: loc.radius_meters, color: '#6366F1', fillOpacity: 0.12 }).addTo(pickerMap);
        });

        // Current target preview marker and radius in red
        previewMarker = L.marker([defaultLat, defaultLng]).addTo(pickerMap);
        previewCircle = L.circle([defaultLat, defaultLng], {
            radius: parseInt(radiusInput ? radiusInput.value : 100, 10),
            color: '#EF4444',
            fillOpacity: 0.25
        }).addTo(pickerMap);

        pickerMap.on('click', function (e) {
            latInput.value = e.latlng.lat.toFixed(6);
            lngInput.value = e.latlng.lng.toFixed(6);
            syncPreview();
        });

        latInput.addEventListener('input', syncPreview);
        lngInput.addEventListener('input', syncPreview);
        if (radiusInput) radiusInput.addEventListener('input', syncPreview);

        // Allow pasting Google Maps "lat, lng" coordinates directly into either field
        function handleCoordinatePaste(e) {
            const text = (e.clipboardData || window.clipboardData)?.getData('text');
            if (text && text.includes(',')) {
                const parts = text.split(',').map(s => s.trim());
                if (parts.length >= 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
                    e.preventDefault();
                    latInput.value = parseFloat(parts[0]).toFixed(6);
                    lngInput.value = parseFloat(parts[1]).toFixed(6);
                    syncPreview();
                    if (pickerMap) {
                        pickerMap.setView([parseFloat(parts[0]), parseFloat(parts[1])], 16);
                    }
                }
            }
        }
        latInput.addEventListener('paste', handleCoordinatePaste);
        lngInput.addEventListener('paste', handleCoordinatePaste);

        // Address / Place Search via OpenStreetMap Nominatim + GSI Fallback
        const searchInput = document.getElementById('address-search-input');
        const searchBtn = document.getElementById('address-search-btn');
        const searchResults = document.getElementById('address-search-results');

        function applyLocationResult(lat, lng, label) {
            latInput.value = Number(lat).toFixed(6);
            lngInput.value = Number(lng).toFixed(6);
            syncPreview();
            pickerMap.setView([lat, lng], 16);
            previewMarker.bindPopup(escapeHtml(label)).openPopup();
        }

        async function searchAddress() {
            const query = searchInput.value.trim();
            if (!query) return;

            searchBtn.disabled = true;
            searchBtn.textContent = '検索中...';
            searchResults.classList.remove('hidden');
            searchResults.innerHTML = '<div class="p-2.5 text-slate-500">検索しています...</div>';

            try {
                const nominatimUrl = `https://nominatim.openstreetmap.org/search?format=json&accept-language=ja&limit=5&q=${encodeURIComponent(query)}`;
                const res = await fetch(nominatimUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                let items = await res.json();

                // Fallback to GSI (国土地理院) AddressSearch if Nominatim returns no results
                if (!items || items.length === 0) {
                    const gsiUrl = `https://msearch.gsi.go.jp/address-search/AddressSearch?q=${encodeURIComponent(query)}`;
                    const gsiRes = await fetch(gsiUrl);
                    const gsiItems = await gsiRes.json();
                    if (Array.isArray(gsiItems)) {
                        items = gsiItems.slice(0, 5).map(g => ({
                            lat: g.geometry.coordinates[1],
                            lon: g.geometry.coordinates[0],
                            display_name: g.properties.title
                        }));
                    }
                }

                if (!items || items.length === 0) {
                    searchResults.innerHTML = '<div class="p-2.5 text-rose-600">該当する場所が見つかりませんでした。別のキーワードや市区町村名でお試しください。</div>';
                    return;
                }

                searchResults.innerHTML = '';
                items.forEach((item, idx) => {
                    const lat = parseFloat(item.lat);
                    const lng = parseFloat(item.lon);
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'w-full text-left px-3 py-2 hover:bg-indigo-50 transition flex items-center justify-between gap-2';
                    btn.innerHTML = `<span class="truncate text-slate-800">📍 ${escapeHtml(item.display_name)}</span><span class="text-[10px] text-indigo-600 font-bold shrink-0">選択</span>`;
                    btn.addEventListener('click', () => {
                        applyLocationResult(lat, lng, item.display_name);
                    });
                    searchResults.appendChild(btn);

                    if (idx === 0) {
                        applyLocationResult(lat, lng, item.display_name);
                    }
                });
            } catch (err) {
                searchResults.innerHTML = '<div class="p-2.5 text-rose-600">検索中にエラーが発生しました。</div>';
            } finally {
                searchBtn.disabled = false;
                searchBtn.textContent = '🔍 検索';
            }
        }

        if (searchBtn && searchInput) {
            searchBtn.addEventListener('click', searchAddress);
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchAddress();
                }
            });
        }
    }
</script>
@endpush
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.flatpickr-datetime', {
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',
                locale: 'ja',
                allowInput: true,
                disableMobile: true,
            });
        }
    });
</script>
@endpush

