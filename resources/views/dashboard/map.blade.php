@extends('layouts.app')

@section('title', '獲得スタンプ全地点マップ | Stampippi')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider">MY STAMP FOOTPRINT MAP</div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">
                🗺️ {{ $user->name }} さんの獲得スタンプ全地点マップ
            </h1>
            <p class="text-xs text-slate-500">
                これまでに獲得した全 <strong>{{ $stamps->count() }}個</strong> のスタンプ（{{ $rallyCount }} ラリー）を1枚の地図に表示しています。アイコンをタップすると打刻日時とラリー名を確認できます。
            </p>
        </div>

        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl shrink-0 transition">
            ← マイページへ戻る
        </a>
    </div>

    <!-- All-in-One Stamp Map -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-3">
        @if ($stamps->isEmpty())
            <div class="py-12 text-center space-y-3">
                <div class="text-4xl">💮</div>
                <p class="text-sm font-bold text-slate-700">まだ獲得したスタンプがありません</p>
                <p class="text-xs text-slate-500">スタンプラリーに参加してベニューでチェックインすると、ここに足跡が記録されます。</p>
                <a href="{{ route('dashboard') }}" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-xl">
                    開催中のスタンプラリーを探す →
                </a>
            </div>
        @else
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-600">📍 マップ上のスタンプアイコンをクリックすると詳細（打刻日時・スタンプラリー名）が表示されます</span>
                <button type="button" onclick="fitAllStamps()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1 rounded-lg">
                    全体を表示
                </button>
            </div>
            <div id="all-stamps-map" class="w-full h-[420px] sm:h-[540px] rounded-xl border border-slate-200 z-10"></div>
        @endif
    </div>

    <!-- Chronological Stamp History List -->
    @if ($stamps->isNotEmpty())
        <section class="space-y-3">
            <h2 class="text-base font-bold text-slate-900">🕒 獲得スタンプ履歴一覧（新しい順）</h2>
            <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
                @foreach ($stamps as $stamp)
                    <div class="px-4 py-3 hover:bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-2 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <button type="button"
                                onclick="focusStampMarker({{ $stamp->id }})"
                                class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 border-2 overflow-hidden"
                                style="border-color: {{ $stamp->location->stamp_color }}; background-color: {{ $stamp->location->stamp_color }}15;">
                                @if ($stamp->location->hasCustomImage())
                                    <img src="{{ $stamp->location->iconImageUrl() }}" alt="" class="w-7 h-7 object-contain">
                                @else
                                    {{ $stamp->location->iconEmoji() }}
                                @endif
                            </button>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                    <button type="button"
                                        onclick="focusStampMarker({{ $stamp->id }})"
                                        class="text-sm font-bold text-slate-900 hover:text-indigo-600 text-left truncate">
                                        {{ $stamp->location->name }}
                                    </button>
                                    <span class="text-[11px] font-semibold text-slate-500">
                                        🕒 {{ $stamp->stamped_at->format('Y/m/d H:i') }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 truncate">
                                    スタンプラリー:
                                    <a href="{{ route('rallies.show', $stamp->rally) }}" class="text-indigo-600 font-semibold hover:underline">
                                        {{ $stamp->rally->title }}
                                    </a>
                                    @if ($stamp->rally->series)
                                        <span class="text-slate-400">（📚 {{ $stamp->rally->series->title }}）</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                            <button type="button"
                                onclick="focusStampMarker({{ $stamp->id }})"
                                class="text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg">
                                📍 マップで見る
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

@if ($stamps->isNotEmpty())
@push('scripts')
<script>
    const mapStamps = @json($mapStamps);

    const defaultCenter = [mapStamps[0].latitude, mapStamps[0].longitude];
    const map = L.map('all-stamps-map').setView(defaultCenter, 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    // Group stamps by coordinate (rounded to 5 decimal places ~1m) so multiple stamps at the same venue are all visible in one popup
    const coordGroups = new Map();
    mapStamps.forEach(s => {
        const key = `${s.latitude.toFixed(5)},${s.longitude.toFixed(5)}`;
        if (!coordGroups.has(key)) {
            coordGroups.set(key, []);
        }
        coordGroups.get(key).push(s);
    });

    const bounds = [];
    const markerByStampId = new Map();

    coordGroups.forEach(group => {
        const primary = group[0];
        const latlng = [primary.latitude, primary.longitude];
        bounds.push(latlng);

        const countBadge = group.length > 1
            ? `<span style="position:absolute;top:-6px;right:-6px;background:#4F46E5;color:#fff;font-size:10px;font-weight:800;border-radius:9999px;padding:1px 5px;border:1.5px solid #fff;">${group.length}</span>`
            : '';

        const innerIconHtml = primary.icon_image_url
            ? `<img src="${escapeHtml(primary.icon_image_url)}" alt="" style="width:28px;height:28px;object-fit:contain;">`
            : `<span>${primary.icon_emoji}</span>`;

        const icon = L.divIcon({
            className: '',
            html: `
                <div style="position:relative;width:40px;height:40px;border-radius:12px;background:#ffffff;border:3px solid ${primary.stamp_color};box-shadow:0 3px 8px rgba(0,0,0,0.22);display:flex;align-items:center;justify-content:center;font-size:21px;">
                    ${innerIconHtml}
                    ${countBadge}
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -18]
        });

        const itemsHtml = group.map(item => {
            const itemIconHtml = item.icon_image_url
                ? `<img src="${escapeHtml(item.icon_image_url)}" alt="" style="width:18px;height:18px;object-fit:contain;display:inline-block;vertical-align:middle;margin-right:4px;">`
                : `${item.icon_emoji} `;
            return `
                <div style="padding:6px 0;border-top:1px solid #f1f5f9;">
                    <div style="font-weight:700;font-size:13px;color:#0f172a;">
                        ${itemIconHtml}${escapeHtml(item.location_name)}
                    </div>
                    <div style="font-size:12px;margin-top:2px;">
                        🗺️ ラリー: <a href="${escapeHtml(item.rally_url)}" style="color:#4F46E5;font-weight:700;text-decoration:underline;">${escapeHtml(item.rally_title)}</a>
                    </div>
                    <div style="font-size:11px;color:#64748b;margin-top:2px;">
                        🕒 打刻日時: <strong>${escapeHtml(item.stamped_at)}</strong>
                    </div>
                </div>
            `;
        }).join('');

        const popupHtml = `
            <div style="min-width:220px;max-width:280px;">
                <div style="font-size:11px;font-weight:700;color:#6366f1;margin-bottom:4px;">
                    💮 獲得スタンプ (${group.length}件)
                </div>
                ${itemsHtml}
            </div>
        `;

        const marker = L.marker(latlng, { icon }).addTo(map);
        marker.bindPopup(popupHtml);

        group.forEach(item => {
            markerByStampId.set(item.id, marker);
        });
    });

    function fitAllStamps() {
        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [40, 40] });
        } else if (bounds.length === 1) {
            map.setView(bounds[0], 15);
        }
    }

    fitAllStamps();

    function focusStampMarker(stampId) {
        const marker = markerByStampId.get(stampId);
        if (!marker) return;
        document.getElementById('all-stamps-map')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => {
            map.setView(marker.getLatLng(), 16);
            marker.openPopup();
        }, 200);
    }
</script>
@endpush
@endif
