@extends('layouts.app')

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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">所有オーナー（個人 / グループ）</label>
                    <select name="group_id" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="">👤 個人所有 ({{ '@' . auth()->user()->handle }})</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected(old('group_id', $rally->group_id) == $group->id)>
                                👥 グループ: {{ $group->name }} ({{ '@' . $group->slug }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">所属ラリーシリーズ（任意・単発ラリーなら未選択）</label>
                    <select name="rally_series_id" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="">-- シリーズなし（単発スタンプラリー） --</option>
                        @foreach ($seriesList as $s)
                            <option value="{{ $s->id }}" @selected(old('rally_series_id', $rally->rally_series_id) == $s->id)>
                                📚 {{ $s->title }}
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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">公開ステータス</label>
                    <select name="visibility" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm bg-white">
                        <option value="public" @selected(old('visibility', $rally->visibility) === 'public')>全体公開 (public)</option>
                        <option value="unlisted" @selected(old('visibility', $rally->visibility) === 'unlisted')>限定公開 (unlisted)</option>
                        <option value="draft" @selected(old('visibility', $rally->visibility) === 'draft')>下書き (draft)</option>
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
                    <label class="block text-xs font-bold text-slate-700 mb-1">開催開始日時（空欄なら開始日制限なし・常設）</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $rally->starts_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm bg-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">開催終了日時（空欄なら終了日制限なし・常設）</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $rally->ends_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-sm bg-white">
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
        <!-- Venue (StampLocation) Addition & Map Picker -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
                <h2 class="text-lg font-bold text-slate-900">📍 スタンプロケーション（ベニュー）を追加</h2>
                <p class="text-xs text-slate-500">
                    右側の地図をクリックすると緯度・経度が自動入力されます。打刻可能期間を空欄にすると「常設ベニュー（いつでも打刻可）」になります。
                </p>

                <form method="POST" action="{{ route('my.rallies.locations.store', $rally) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">ベニュー名</label>
                        <input type="text" name="name" required placeholder="第1チェックポイント・中央広場" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">説明・ヒント（任意）</label>
                        <input type="text" name="description" placeholder="1F 大展示ホール入口付近" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">緯度 (Latitude)</label>
                            <input type="number" step="0.0000001" id="new-lat" name="latitude" required value="35.5588810" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">経度 (Longitude)</label>
                            <input type="number" step="0.0000001" id="new-lng" name="longitude" required value="139.7240960" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">判定半径 (m)</label>
                            <input type="number" min="10" max="10000" id="new-radius" name="radius_meters" required value="150" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">打刻可能 開始日時 (空欄=常設)</label>
                            <input type="datetime-local" name="available_from" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">打刻可能 終了日時 (空欄=常設)</label>
                            <input type="datetime-local" name="available_until" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">スタンプ絵柄</label>
                            <select name="stamp_icon_value" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white">
                                @foreach ($presetIcons as $key => $icon)
                                    <option value="{{ $key }}">{{ $icon['emoji'] }} {{ $icon['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">スタンプカラー</label>
                            <input type="color" name="stamp_color" value="#4F46E5" class="w-full h-9 rounded-xl border border-slate-300 px-1 py-1 bg-white">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm py-2.5 rounded-xl">
                        ＋ このベニューを追加する
                    </button>
                </form>

                @if ($copyableLocations->isNotEmpty())
                    <form method="POST" action="{{ route('my.rallies.locations.copy', $rally) }}" class="pt-4 border-t border-slate-200 space-y-2">
                        @csrf
                        <label class="block text-xs font-bold text-slate-700">📋 過去のスタンプラリーからベニューをコピーして追加</label>
                        <div class="flex gap-2">
                            <select name="source_location_id" class="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white">
                                @foreach ($copyableLocations as $copyLoc)
                                    <option value="{{ $copyLoc->id }}">
                                        {{ $copyLoc->iconEmoji() }} {{ $copyLoc->name }} （{{ $copyLoc->rally->title }}）
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold px-4 py-2 rounded-xl shrink-0">
                                コピー追加
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- Interactive Map Picker -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col space-y-3">
                <div class="text-xs font-bold text-slate-700">🗺️ 住所・施設名検索 または マップクリックで座標を指定</div>

                <!-- Address / Place Search Box -->
                <div class="space-y-2">
                    <div class="flex gap-2">
                        <input type="text" id="address-search-input" placeholder="住所や施設名を入力（例: 東京都大田区南蒲田1-20-20 / 東京駅）" class="flex-1 rounded-xl border border-slate-300 px-3 py-2 text-xs">
                        <button type="button" id="address-search-btn" class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold px-4 py-2 rounded-xl shrink-0 transition">
                            🔍 検索
                        </button>
                    </div>
                    <div id="address-search-results" class="hidden max-h-36 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 divide-y divide-slate-200 text-xs"></div>
                </div>

                <div id="picker-map" class="w-full flex-1 min-h-[380px] rounded-xl border border-slate-200 z-10"></div>
            </div>
        </div>

        <!-- Registered Venues List -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
            <h2 class="text-lg font-bold text-slate-900">登録済みスタンプロケーション ({{ $rally->locations->count() }}箇所)</h2>

            @if ($rally->locations->isEmpty())
                <p class="text-sm text-slate-500">まだベニューがありません。上のフォームから追加してください。</p>
            @else
                <div class="space-y-4">
                    @foreach ($rally->locations as $loc)
                        <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/60 space-y-3">
                            <form method="POST" action="{{ route('my.rallies.locations.update', [$rally, $loc]) }}" class="grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
                                @csrf
                                @method('PUT')
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-bold text-slate-600">ベニュー名</label>
                                    <input type="text" name="name" value="{{ $loc->name }}" required class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600">緯度 / 経度</label>
                                    <div class="flex gap-1">
                                        <input type="number" step="0.0000001" name="latitude" value="{{ $loc->latitude }}" required class="w-1/2 rounded-lg border border-slate-300 px-1.5 py-1.5 text-[11px] bg-white">
                                        <input type="number" step="0.0000001" name="longitude" value="{{ $loc->longitude }}" required class="w-1/2 rounded-lg border border-slate-300 px-1.5 py-1.5 text-[11px] bg-white">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600">半径(m) / 絵柄</label>
                                    <div class="flex gap-1">
                                        <input type="number" name="radius_meters" value="{{ $loc->radius_meters }}" required class="w-16 rounded-lg border border-slate-300 px-2 py-1.5 text-xs bg-white">
                                        <select name="stamp_icon_value" class="flex-1 rounded-lg border border-slate-300 px-1.5 py-1.5 text-xs bg-white">
                                            @foreach ($presetIcons as $key => $icon)
                                                <option value="{{ $key }}" @selected($loc->stamp_icon_value === $key)>{{ $icon['emoji'] }}</option>
                                            @endforeach
                                        </select>
                                        <input type="color" name="stamp_color" value="{{ $loc->stamp_color }}" class="w-8 h-8 rounded border border-slate-300 bg-white">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600">開始〜終了(空欄=常設)</label>
                                    <div class="space-y-1">
                                        <input type="datetime-local" name="available_from" value="{{ $loc->available_from?->format('Y-m-d\TH:i') }}" class="w-full rounded border border-slate-300 px-1.5 py-0.5 text-[10px] bg-white">
                                        <input type="datetime-local" name="available_until" value="{{ $loc->available_until?->format('Y-m-d\TH:i') }}" class="w-full rounded border border-slate-300 px-1.5 py-0.5 text-[10px] bg-white">
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-1.5">
                                    <input type="hidden" name="description" value="{{ $loc->description }}">
                                    <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg">保存</button>
                                </div>
                            </form>
                            <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-200/70">
                                <span>期間設定: {{ $loc->availabilityLabel() }}</span>
                                <form method="POST" action="{{ route('my.rallies.locations.destroy', [$rally, $loc]) }}" onsubmit="return confirm('このロケーションを削除しますか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:underline font-semibold">削除</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

@if ($isEdit)
@push('scripts')
<script>
    const existingLocations = @json($rally->locations);
    const latInput = document.getElementById('new-lat');
    const lngInput = document.getElementById('new-lng');
    const radiusInput = document.getElementById('new-radius');

    const pickerMap = L.map('picker-map').setView([parseFloat(latInput.value), parseFloat(lngInput.value)], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(pickerMap);

    existingLocations.forEach(loc => {
        L.marker([loc.latitude, loc.longitude]).addTo(pickerMap).bindPopup(loc.name);
        L.circle([loc.latitude, loc.longitude], { radius: loc.radius_meters, color: '#6366F1', fillOpacity: 0.15 }).addTo(pickerMap);
    });

    let previewMarker = L.marker([parseFloat(latInput.value), parseFloat(lngInput.value)]).addTo(pickerMap);
    let previewCircle = L.circle([parseFloat(latInput.value), parseFloat(lngInput.value)], {
        radius: parseInt(radiusInput.value || 150, 10),
        color: '#EF4444',
        fillOpacity: 0.2
    }).addTo(pickerMap);

    function syncPreview() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        const rad = parseInt(radiusInput.value || 100, 10);
        if (!isNaN(lat) && !isNaN(lng)) {
            previewMarker.setLatLng([lat, lng]);
            previewCircle.setLatLng([lat, lng]);
            previewCircle.setRadius(rad);
        }
    }

    pickerMap.on('click', function (e) {
        latInput.value = e.latlng.lat.toFixed(7);
        lngInput.value = e.latlng.lng.toFixed(7);
        syncPreview();
    });

    latInput.addEventListener('input', syncPreview);
    lngInput.addEventListener('input', syncPreview);
    radiusInput.addEventListener('input', syncPreview);

    // Address / Place Search via OpenStreetMap Nominatim + GSI Fallback
    const searchInput = document.getElementById('address-search-input');
    const searchBtn = document.getElementById('address-search-btn');
    const searchResults = document.getElementById('address-search-results');

    function applyLocationResult(lat, lng, label) {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
        syncPreview();
        pickerMap.setView([lat, lng], 16);
        previewMarker.bindPopup(label).openPopup();
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
                btn.innerHTML = `<span class="truncate text-slate-800">📍 ${item.display_name}</span><span class="text-[10px] text-indigo-600 font-bold shrink-0">選択</span>`;
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

    searchBtn.addEventListener('click', searchAddress);
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchAddress();
        }
    });
</script>
@endpush
@endif

