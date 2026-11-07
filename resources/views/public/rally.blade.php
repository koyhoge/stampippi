@extends('layouts.app')

@section('title', $rally->title . ' | Stampippi')

@section('content')
<div class="space-y-6 sm:space-y-8">
    <!-- Rally Header -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-8 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                @if ($rally->series)
                    <a href="{{ route('series.show', $rally->series) }}" class="font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-full transition">
                        📚 {{ $rally->series->title }}
                    </a>
                @endif
                @if ($rally->group)
                    <a href="{{ route('groups.show', $rally->group) }}" class="font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-full transition">
                        👥 {{ $rally->group->name }}
                    </a>
                @else
                    <span class="text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                        作成者: {{ '@' . $rally->creator->handle }}
                    </span>
                @endif
                <span class="font-semibold px-2.5 py-1 rounded-full {{ $rally->isCurrentlyActive() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                    📅 {{ $rally->periodLabel() }}
                </span>
            </div>

            @auth
                @if (auth()->user()->canManageRally($rally))
                    <a href="{{ route('my.rallies.edit', $rally) }}" class="text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg transition">
                        ⚙️ 管理
                    </a>
                @endif
            @endauth
        </div>

        <h1 class="text-xl sm:text-3xl font-extrabold text-slate-900 mb-2 leading-snug">{{ $rally->title }}</h1>
        @if ($rally->description)
            <p class="text-xs sm:text-base text-slate-600 whitespace-pre-line mb-4 sm:mb-6">{{ $rally->description }}</p>
        @endif

        <!-- Player Progress Bar -->
        @php
            $totalCount = $rally->locations->count();
            $requiredCount = $rally->requiredCount();
            $stampedCount = $userStampsByLocation->count();
            $progressPercent = $requiredCount > 0 ? min(100, round(($stampedCount / $requiredCount) * 100)) : 0;
        @endphp
        <div class="bg-slate-50 rounded-xl p-3.5 sm:p-4 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="space-y-1.5 flex-1">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="text-slate-700">
                        @if ($rally->completion_condition === 'count')
                            全{{ $totalCount }}箇所中 {{ $requiredCount }}個以上で達成
                        @else
                            全{{ $totalCount }}箇所制覇で達成
                        @endif
                    </span>
                    <span class="text-indigo-600 font-bold">{{ $stampedCount }} / {{ $requiredCount }} 獲得 ({{ $progressPercent }}%)</span>
                </div>
                <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-600 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>

            <div class="shrink-0">
                @auth
                    @if ($participation && $participation->completed_at)
                        <a href="{{ route('rallies.certificate', $rally) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs sm:text-sm font-bold px-4 py-3 sm:py-2.5 rounded-xl shadow transition">
                            🏆 コンプリート達成証を見る
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-bold px-4 py-3 sm:py-2.5 rounded-xl shadow transition">
                        🔑 ログインしてスタンプを押す
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Smartphone Quick GPS Check-in Banner -->
    @auth
        @if ($rally->isCurrentlyActive() && $stampedCount < $totalCount)
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl p-4 sm:p-5 text-white shadow-md flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                    <div class="text-sm sm:text-base font-extrabold flex items-center gap-1.5">
                        <span>📍 現在地から最寄りのベニューにチェックイン</span>
                    </div>
                    <p class="text-xs text-indigo-100" id="quick-checkin-status">
                        今いる場所のGPS座標から一番近い未獲得ベニューを自動判定してスタンプを押します。
                    </p>
                </div>
                <button type="button"
                    id="btn-quick-checkin"
                    onclick="quickCheckinNearest()"
                    class="min-h-[48px] bg-white hover:bg-indigo-50 active:scale-[0.99] text-indigo-700 font-extrabold text-sm px-5 py-3 rounded-xl shadow-sm shrink-0 transition flex items-center justify-center gap-2">
                    <span>💮 現在地でスタンプを押す</span>
                </button>
            </div>
        @endif
    @endauth

    <!-- Dynamic Check-in Feedback Banner (AJAX / Mobile Friendly) -->
    <div id="checkin-feedback-modal" class="hidden rounded-2xl border-2 p-4 sm:p-5 shadow-md transition-all"></div>

    <!-- Stamp Book / Locations List (Placed ABOVE map on mobile for fast check-in!) -->
    <div class="space-y-3 sm:space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg sm:text-xl font-bold text-slate-900">📖 スタンプ台帳・ベニュー ({{ $rally->locations->count() }}箇所)</h2>
            <button type="button" onclick="measureAllDistances()" class="text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition">
                📏 現在地からの距離を計測
            </button>
        </div>

        @if ($rally->locations->isEmpty())
            <p class="text-sm text-slate-500 bg-white rounded-xl p-6 border border-slate-200">まだスタンプロケーションが登録されていません。</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($rally->locations as $location)
                    @php
                        $stamp = $userStampsByLocation->get($location->id);
                        $isAvailable = $location->isAvailableAt();
                    @endphp
                    <div id="location-card-{{ $location->id }}"
                         class="bg-white rounded-2xl border-2 {{ $stamp ? 'border-indigo-500 shadow-sm' : 'border-slate-200' }} p-4 sm:p-5 flex flex-col justify-between gap-4">
                        <div class="flex items-start gap-3.5 sm:gap-4">
                            <!-- Stamp Badge Visual -->
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl flex flex-col items-center justify-center shrink-0 border-2 {{ $stamp ? 'bg-indigo-50 border-indigo-500' : 'bg-slate-50 border-dashed border-slate-300 opacity-50' }}"
                                 style="{{ $stamp ? 'border-color:' . $location->stamp_color . '; background-color:' . $location->stamp_color . '15;' : '' }}">
                                <span class="text-2xl sm:text-3xl">{{ $location->iconEmoji() }}</span>
                                <span class="text-[9px] sm:text-[10px] font-extrabold mt-1 uppercase tracking-wider {{ $stamp ? 'text-indigo-900' : 'text-slate-400' }}">
                                    {{ $stamp ? 'STAMPED' : 'UNSTAMPED' }}
                                </span>
                            </div>

                            <div class="flex-1 min-w-0 space-y-1.5">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-snug">{{ $location->name }}</h3>
                                    <button type="button"
                                        onclick="focusMapLocation({{ $location->latitude }}, {{ $location->longitude }})"
                                        class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg shrink-0">
                                        地図
                                    </button>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span>半径 {{ $location->radius_meters }}m</span>
                                    <span>• {{ $location->availabilityLabel() }}</span>
                                    <span id="distance-badge-{{ $location->id }}" class="hidden font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded"></span>
                                </div>

                                @if ($location->description)
                                    <p class="text-xs text-slate-600 leading-relaxed">{{ $location->description }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Touch-friendly Action Area -->
                        <div class="pt-2 border-t border-slate-100">
                            @if ($stamp)
                                <div class="w-full text-center text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-2.5 rounded-xl">
                                    💮 スタンプ獲得済み ({{ $stamp->stamped_at->format('Y/m/d H:i') }} / 中心から {{ $stamp->distance_meters }}m)
                                </div>
                            @else
                                @auth
                                    <form method="POST"
                                          action="{{ route('rallies.checkin', [$rally, $location]) }}"
                                          class="checkin-form w-full"
                                          data-location-id="{{ $location->id }}"
                                          data-location-name="{{ $location->name }}">
                                        @csrf
                                        <input type="hidden" name="latitude" class="input-lat">
                                        <input type="hidden" name="longitude" class="input-lng">
                                        <input type="hidden" name="accuracy" class="input-acc">
                                        <button type="button"
                                            onclick="submitCheckin(this.form, this)"
                                            @if (! $isAvailable || ! $rally->isCurrentlyActive()) disabled @endif
                                            class="w-full min-h-[46px] inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 disabled:bg-slate-300 text-white text-xs sm:text-sm font-bold px-4 py-3 rounded-xl shadow-sm transition">
                                            <span>📍 このベニューでスタンプを押す</span>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('login') }}" class="w-full min-h-[44px] inline-flex items-center justify-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-indigo-700 text-xs font-bold px-4 py-2.5 rounded-xl transition">
                                        🔑 ログインしてスタンプを押す →
                                    </a>
                                @endauth
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Map & GPS Status Section -->
    <div id="map-section" class="grid grid-cols-1 lg:grid-cols-3 gap-6 scroll-mt-20">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900">🗺️ ベニューマップ</h2>
                <button type="button" id="btn-current-location" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-3 py-1.5 rounded-lg">
                    📍 現在地をマップに表示
                </button>
            </div>
            <div id="rally-map" class="w-full h-72 sm:h-96 rounded-xl border border-slate-200 z-10"></div>
            <p class="text-[11px] text-slate-500">
                ※円は各ベニューのスタンプ判定半径です。円の内側でチェックインするとスタンプを獲得できます。
            </p>
        </div>

        <!-- GPS Status & Collapsible Local Simulator -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm flex flex-col justify-between space-y-4">
            <div class="space-y-3">
                <h3 class="text-sm sm:text-base font-bold text-slate-900">📡 GPS現在地ステータス</h3>
                <div id="gps-status-box" class="rounded-xl bg-slate-50 border border-slate-200 p-3.5 text-xs space-y-1">
                    <div class="font-semibold text-slate-700" id="gps-mode-label">実機スマホ・ブラウザGPSモード</div>
                    <div class="text-slate-500" id="gps-coords-display">スタンプ押印時にスマートフォンの位置情報を取得します。</div>
                </div>

                @if (app()->environment('local', 'testing'))
                    <details class="rounded-xl bg-amber-50 border border-amber-200 p-3.5 text-xs group">
                        <summary class="font-extrabold text-amber-900 cursor-pointer flex items-center justify-between list-none">
                            <span>🛠️ 開発用 GPSシミュレーター (タップで開閉)</span>
                            <span class="text-[10px] bg-amber-200 text-amber-900 px-1.5 py-0.5 rounded font-bold">LOCAL</span>
                        </summary>
                        <div class="mt-3 space-y-3 pt-2 border-t border-amber-200/70">
                            <p class="text-amber-800 leading-relaxed">
                                現地に行かずにスマホ・PC画面から擬似座標をセットしてチェックイン動作をテストできます。
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-amber-900">擬似 緯度 (Lat)</label>
                                    <input type="number" step="0.0000001" id="sim-lat" placeholder="未設定(実GPS)" class="w-full text-xs rounded border border-amber-300 px-2 py-1.5 bg-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-amber-900">擬似 経度 (Lng)</label>
                                    <input type="number" step="0.0000001" id="sim-lng" placeholder="未設定(実GPS)" class="w-full text-xs rounded border border-amber-300 px-2 py-1.5 bg-white">
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach ($rally->locations as $loc)
                                    <button type="button"
                                        onclick="setSimulatedLocation({{ $loc->latitude }}, {{ $loc->longitude }}, '{{ addslashes($loc->name) }}')"
                                        class="text-[11px] bg-white hover:bg-amber-100 text-amber-900 border border-amber-300 px-2.5 py-1.5 rounded-lg font-medium transition">
                                        📍 {{ $loc->name }} へ移動
                                    </button>
                                @endforeach
                                <button type="button"
                                    onclick="clearSimulatedLocation()"
                                    class="text-[11px] bg-amber-200/70 hover:bg-amber-300 text-amber-900 px-2.5 py-1.5 rounded-lg font-medium transition">
                                    実GPSに戻す
                                </button>
                            </div>
                        </div>
                    </details>
                @endif
            </div>

            <div class="text-xs text-slate-400 border-t border-slate-100 pt-3">
                スマートフォンのブラウザで位置情報へのアクセスを「許可」してご利用ください。
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const mapLocations = @json($mapLocations);
    const defaultCenter = mapLocations.length > 0
        ? [mapLocations[0].latitude, mapLocations[0].longitude]
        : [35.681236, 139.767125];

    const map = L.map('rally-map').setView(defaultCenter, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const bounds = [];
    mapLocations.forEach(loc => {
        const latlng = [loc.latitude, loc.longitude];
        bounds.push(latlng);

        const marker = L.marker(latlng).addTo(map);
        marker.bindPopup(`
            <div class="text-xs space-y-1">
                <div class="font-bold text-sm">${loc.icon_emoji} ${loc.name}</div>
                <div class="text-slate-600">判定半径: ${loc.radius_meters}m</div>
                <div class="text-slate-500">${loc.availability_label}</div>
                <div class="font-bold ${loc.is_stamped ? 'text-emerald-600' : 'text-indigo-600'}">
                    ${loc.is_stamped ? '💮 獲得済み (' + loc.stamped_at + ')' : '未獲得'}
                </div>
            </div>
        `);

        L.circle(latlng, {
            radius: loc.radius_meters,
            color: loc.is_stamped ? '#10B981' : loc.stamp_color,
            fillColor: loc.is_stamped ? '#10B981' : loc.stamp_color,
            fillOpacity: 0.18,
            weight: 2
        }).addTo(map);
    });

    if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [30, 30] });
    }

    let userMarker = null;

    function haversineMeters(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const toRad = deg => deg * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2 +
                  Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
        return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }

    function updateDistanceBadges(lat, lng) {
        mapLocations.forEach(loc => {
            const badge = document.getElementById(`distance-badge-${loc.id}`);
            if (!badge) return;
            const dist = haversineMeters(lat, lng, loc.latitude, loc.longitude);
            const inRange = dist <= loc.radius_meters;
            badge.classList.remove('hidden', 'text-indigo-600', 'bg-indigo-50', 'text-emerald-700', 'bg-emerald-100');
            badge.classList.add(inRange ? 'text-emerald-700' : 'text-indigo-600', inRange ? 'bg-emerald-100' : 'bg-indigo-50');
            badge.textContent = inRange
                ? `📍 現在地から約 ${dist}m（打刻圏内！）`
                : `現在地から約 ${dist}m`;
        });
    }

    function updateUserPin(lat, lng, label) {
        if (userMarker) {
            userMarker.setLatLng([lat, lng]);
        } else {
            userMarker = L.circleMarker([lat, lng], {
                radius: 8,
                color: '#EF4444',
                fillColor: '#EF4444',
                fillOpacity: 0.9
            }).addTo(map);
        }
        userMarker.bindPopup(label).openPopup();
        updateDistanceBadges(lat, lng);
    }

    function focusMapLocation(lat, lng) {
        document.getElementById('map-section')?.scrollIntoView({ behavior: 'smooth' });
        setTimeout(() => map.setView([lat, lng], 16), 200);
    }

    function setSimulatedLocation(lat, lng, name) {
        const latInput = document.getElementById('sim-lat');
        const lngInput = document.getElementById('sim-lng');
        if (latInput && lngInput) {
            latInput.value = lat;
            lngInput.value = lng;
            document.getElementById('gps-mode-label').textContent = '🛠️ シミュレーター座標を使用中 (' + name + ')';
            document.getElementById('gps-coords-display').textContent = `Lat: ${lat}, Lng: ${lng}`;
            updateUserPin(lat, lng, `📍 擬似現在地 (${name})`);
        }
    }

    function clearSimulatedLocation() {
        const latInput = document.getElementById('sim-lat');
        const lngInput = document.getElementById('sim-lng');
        if (latInput && lngInput) {
            latInput.value = '';
            lngInput.value = '';
            document.getElementById('gps-mode-label').textContent = '実機スマホ・ブラウザGPSモード';
            document.getElementById('gps-coords-display').textContent = 'スタンプ押印時にスマートフォンの位置情報を取得します。';
        }
    }

    @if (app()->environment('local', 'testing'))
    map.on('click', function(e) {
        const lat = Number(e.latlng.lat.toFixed(7));
        const lng = Number(e.latlng.lng.toFixed(7));
        setSimulatedLocation(lat, lng, 'マップ指定地点');
    });
    @endif

    function acquireCoordinates(onSuccess, onError) {
        const simLat = document.getElementById('sim-lat')?.value;
        const simLng = document.getElementById('sim-lng')?.value;

        if (simLat && simLng) {
            onSuccess({
                latitude: parseFloat(simLat),
                longitude: parseFloat(simLng),
                accuracy: 5
            });
            return;
        }

        if (!navigator.geolocation) {
            onError('お使いのブラウザはGPS位置情報に対応していません。');
            return;
        }

        navigator.geolocation.getCurrentPosition(function (pos) {
            const coords = {
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
                accuracy: pos.coords.accuracy || null
            };
            document.getElementById('gps-coords-display').textContent =
                `現在地 Lat: ${coords.latitude.toFixed(6)}, Lng: ${coords.longitude.toFixed(6)}`;
            updateUserPin(coords.latitude, coords.longitude, '📍 現在のあなたの位置');
            onSuccess(coords);
        }, function (err) {
            onError('現在地を取得できませんでした（' + err.message + '）。スマートフォンの位置情報許可設定を確認してください。');
        }, {
            enableHighAccuracy: true,
            timeout: 12000,
            maximumAge: 0
        });
    }

    function measureAllDistances() {
        acquireCoordinates(function (coords) {
            updateDistanceBadges(coords.latitude, coords.longitude);
        }, function (errMsg) {
            alert(errMsg);
        });
    }

    document.getElementById('btn-current-location')?.addEventListener('click', function () {
        acquireCoordinates(function (coords) {
            map.setView([coords.latitude, coords.longitude], 16);
        }, function (errMsg) {
            alert(errMsg);
        });
    });

    function showFeedbackBanner(success, message) {
        const box = document.getElementById('checkin-feedback-modal');
        if (!box) return;
        box.className = success
            ? 'rounded-2xl border-2 border-emerald-400 bg-emerald-50 text-emerald-900 p-4 sm:p-5 shadow-md font-bold text-sm'
            : 'rounded-2xl border-2 border-rose-300 bg-rose-50 text-rose-900 p-4 sm:p-5 shadow-md font-bold text-sm';
        box.textContent = message;
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function submitCheckin(form, btnElement) {
        const originalText = btnElement ? btnElement.innerHTML : '';
        if (btnElement) {
            btnElement.disabled = true;
            btnElement.innerHTML = '<span>📡 GPS位置情報を取得中...</span>';
        }

        acquireCoordinates(function (coords) {
            if (btnElement) {
                btnElement.innerHTML = '<span>💮 スタンプを判定・押印中...</span>';
            }
            form.querySelector('.input-lat').value = coords.latitude;
            form.querySelector('.input-lng').value = coords.longitude;
            form.querySelector('.input-acc').value = coords.accuracy || '';
            form.submit();
        }, function (errMsg) {
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;
            }
            showFeedbackBanner(false, errMsg);
        });
    }

    function quickCheckinNearest() {
        const quickBtn = document.getElementById('btn-quick-checkin');
        const originalHtml = quickBtn ? quickBtn.innerHTML : '';
        if (quickBtn) {
            quickBtn.disabled = true;
            quickBtn.innerHTML = '<span>📡 GPS現在地を取得中...</span>';
        }

        acquireCoordinates(function (coords) {
            const unstampedForms = Array.from(document.querySelectorAll('.checkin-form'));
            if (unstampedForms.length === 0) {
                if (quickBtn) {
                    quickBtn.disabled = false;
                    quickBtn.innerHTML = originalHtml;
                }
                return;
            }

            let nearestForm = null;
            let minDistance = Infinity;
            let nearestLoc = null;

            unstampedForms.forEach(form => {
                const locId = parseInt(form.dataset.locationId, 10);
                const loc = mapLocations.find(l => l.id === locId);
                if (!loc || !loc.is_available_now) return;
                const dist = haversineMeters(coords.latitude, coords.longitude, loc.latitude, loc.longitude);
                if (dist < minDistance) {
                    minDistance = dist;
                    nearestForm = form;
                    nearestLoc = loc;
                }
            });

            if (!nearestForm || !nearestLoc) {
                if (quickBtn) {
                    quickBtn.disabled = false;
                    quickBtn.innerHTML = originalHtml;
                }
                showFeedbackBanner(false, '現在打刻可能な未獲得ベニューがありません。');
                return;
            }

            if (quickBtn) {
                quickBtn.innerHTML = `<span>💮 「${nearestLoc.name}」(約${minDistance}m) にチェックイン中...</span>`;
            }

            nearestForm.querySelector('.input-lat').value = coords.latitude;
            nearestForm.querySelector('.input-lng').value = coords.longitude;
            nearestForm.querySelector('.input-acc').value = coords.accuracy || '';
            nearestForm.submit();
        }, function (errMsg) {
            if (quickBtn) {
                quickBtn.disabled = false;
                quickBtn.innerHTML = originalHtml;
            }
            showFeedbackBanner(false, errMsg);
        });
    }
</script>
@endpush
