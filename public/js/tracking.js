document.addEventListener('DOMContentLoaded', function () {
    const mapElement = document.getElementById('shuttle-map');
    if (!mapElement) {
        return;
    }

    const apiEndpoint = mapElement.getAttribute('data-endpoint') || '/api/v1/shuttles/latest';
    const pollIntervalMs = 3000;

    let campusStops = [];
    try {
        const rawStops = mapElement.getAttribute('data-stops');
        if (rawStops) {
            campusStops = JSON.parse(rawStops);
        }
    } catch (e) {
        console.error('Gagal mem-parsing data halte:', e);
    }

    if (!Array.isArray(campusStops) || campusStops.length === 0) {
        campusStops = [
            { name: 'Halte Citra Raya (Bundaran 1)', latitude: -6.2365000, longitude: 106.5218000, order_index: 1 },
            { name: 'Halte Cikupa (Jl. Raya Serang Km. 13)', latitude: -6.2268000, longitude: 106.5335000, order_index: 2 },
            { name: 'Halte Kampus UNIPI (Gerbang Utama)', latitude: -6.2222578, longitude: 106.5603413, order_index: 3 },
            { name: 'Halte Simpang Bitung', latitude: -6.2212000, longitude: 106.5668000, order_index: 4 },
        ];
    }

    const corridorWaypoints = [
        [-6.2365000, 106.5218000],
        [-6.2330000, 106.5208000],
        [-6.2297500, 106.5198500],
        [-6.2283000, 106.5268000],
        [-6.2268000, 106.5335000],
        [-6.2254000, 106.5410000],
        [-6.2241000, 106.5482000],
        [-6.2229000, 106.5550000],
        [-6.2222578, 106.5603413],
        [-6.2217000, 106.5638000],
        [-6.2212000, 106.5668000],
        [-6.2265000, 106.5652000],
        [-6.2315000, 106.5595000],
        [-6.2348000, 106.5495000],
        [-6.2368000, 106.5365000],
        [-6.2365000, 106.5218000],
    ];

    const corridorCenter = [-6.2290000, 106.5435000];

    const map = L.map('shuttle-map').setView(corridorCenter, 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    const routePolyline = L.polyline(corridorWaypoints, {
        color: '#2563eb',
        weight: 5,
        opacity: 0.7,
        dashArray: '10, 8'
    }).addTo(map);

    map.fitBounds(routePolyline.getBounds(), { padding: [35, 35] });

    const fullscreenBtn = document.getElementById('btn-fullscreen-map');
    const mapWrapper = document.getElementById('map-card-wrapper') || mapElement.closest('.map-wrapper');

    function updateFullscreenButtonState() {
        if (!fullscreenBtn || !mapWrapper) {
            return;
        }
        const isNativeFull = document.fullscreenElement === mapWrapper;
        const isCssFull = mapWrapper.classList.contains('is-fullscreen');
        if (isNativeFull || isCssFull) {
            fullscreenBtn.textContent = '✕ Keluar Penuh';
        } else {
            fullscreenBtn.textContent = '⛶ Layar Penuh';
        }
        setTimeout(function () {
            map.invalidateSize();
        }, 180);
    }

    if (fullscreenBtn && mapWrapper) {
        fullscreenBtn.addEventListener('click', function () {
            const isNativeFull = document.fullscreenElement === mapWrapper;
            const isCssFull = mapWrapper.classList.contains('is-fullscreen');

            if (isNativeFull) {
                document.exitFullscreen();
            } else if (isCssFull) {
                mapWrapper.classList.remove('is-fullscreen');
                updateFullscreenButtonState();
            } else if (mapWrapper.requestFullscreen) {
                mapWrapper.requestFullscreen().catch(function () {
                    mapWrapper.classList.add('is-fullscreen');
                    updateFullscreenButtonState();
                });
            } else {
                mapWrapper.classList.add('is-fullscreen');
                updateFullscreenButtonState();
            }
        });

        document.addEventListener('fullscreenchange', updateFullscreenButtonState);
    }

    function isCampusMainStop(stopName) {
        const lower = String(stopName || '').toLowerCase();
        return lower.includes('kampus') || lower.includes('unipi') || lower.includes('universitas');
    }

    const stopMarkers = {};
    campusStops.forEach(function (stop, idx) {
        const coords = [Number(stop.latitude), Number(stop.longitude)];
        const stopOrder = stop.order_index || (idx + 1);
        const isCampus = isCampusMainStop(stop.name);

        const iconHtml = isCampus
            ? `
                <div class="stop-shelter-wrapper campus-landmark-wrapper">
                    <div class="stop-number-badge campus-badge">🎓 KAMPUS (H${stopOrder})</div>
                    <svg class="campus-building-svg" viewBox="0 0 60 52" xmlns="http://www.w3.org/2000/svg">

                        <line x1="30" y1="2" x2="30" y2="11" stroke="#1e3a8a" stroke-width="2" />
                        <path d="M30 2 L39 5 L30 8 Z" fill="#eab308" />

                        <polygon points="6,20 30,8 54,20" fill="#1e3a8a" stroke="#ffffff" stroke-width="1.6" />
                        <polygon points="12,19 30,10.8 48,19" fill="#2563eb" />

                        <polygon points="30,12.2 35,14.5 30,16.8 25,14.5" fill="#fde047" />
                        <rect x="28" y="16.5" width="4" height="1.6" rx="0.5" fill="#fef08a" />

                        <rect x="8" y="20" width="44" height="3.5" fill="#f8fafc" stroke="#1e3a8a" stroke-width="1.2" />

                        <rect x="10" y="23.5" width="40" height="21" fill="#dbeafe" stroke="#1e3a8a" stroke-width="1.2" />

                        <rect x="12.5" y="23.5" width="4" height="21" fill="#ffffff" stroke="#93c5fd" stroke-width="0.8" />
                        <rect x="20.5" y="23.5" width="4" height="21" fill="#ffffff" stroke="#93c5fd" stroke-width="0.8" />
                        <rect x="35.5" y="23.5" width="4" height="21" fill="#ffffff" stroke="#93c5fd" stroke-width="0.8" />
                        <rect x="43.5" y="23.5" width="4" height="21" fill="#ffffff" stroke="#93c5fd" stroke-width="0.8" />

                        <rect x="17" y="26" width="3" height="5" rx="0.6" fill="#1d4ed8" />
                        <rect x="17" y="33" width="3" height="5" rx="0.6" fill="#1d4ed8" />
                        <rect x="40" y="26" width="3" height="5" rx="0.6" fill="#1d4ed8" />
                        <rect x="40" y="33" width="3" height="5" rx="0.6" fill="#1d4ed8" />

                        <path d="M26 44.5 L26 32 C26 29.5, 34 29.5, 34 32 L34 44.5 Z" fill="#1e3a8a" stroke="#eab308" stroke-width="1.3" />
                        <line x1="30" y1="30.5" x2="30" y2="44.5" stroke="#eab308" stroke-width="1" />

                        <rect x="6" y="44.5" width="48" height="3" rx="1" fill="#64748b" stroke="#ffffff" stroke-width="1" />
                        <rect x="3" y="47.5" width="54" height="3.5" rx="1.5" fill="#334155" stroke="#ffffff" stroke-width="1" />
                    </svg>
                </div>
            `
            : `
                <div class="stop-shelter-wrapper">
                    <div class="stop-number-badge">H${stopOrder}</div>
                    <svg class="stop-shelter-svg" viewBox="0 0 52 44" xmlns="http://www.w3.org/2000/svg">

                        <rect x="4" y="37" width="44" height="4" rx="2" fill="#475569" stroke="#ffffff" stroke-width="1.2" />

                        <rect x="9" y="15" width="3.5" height="22" fill="#1e3a8a" />
                        <rect x="33" y="15" width="3.5" height="22" fill="#1e3a8a" />

                        <rect x="12.5" y="16" width="20.5" height="19" rx="1.5" fill="#dbeafe" stroke="#3b82f6" stroke-width="1.2" />

                        <rect x="14.5" y="29" width="16.5" height="3" rx="1" fill="#f59e0b" />
                        <rect x="16" y="32" width="2" height="5" fill="#64748b" />
                        <rect x="27.5" y="32" width="2" height="5" fill="#64748b" />

                        <path d="M5 15 Q22.5 6 40 15 L38 18 Q22.5 11 7 18 Z" fill="#2563eb" stroke="#ffffff" stroke-width="1.5" />

                        <rect x="43" y="12" width="2.2" height="25" fill="#334155" />
                        <circle cx="44.1" cy="9.5" r="5.5" fill="#0ea5e9" stroke="#ffffff" stroke-width="1.5" />
                        <rect x="41.6" y="7.5" width="5" height="4" rx="1" fill="#ffffff" />
                    </svg>
                </div>
            `;

        const stopIcon = L.divIcon({
            className: 'custom-stop-icon',
            html: iconHtml,
            iconSize: isCampus ? [58, 66] : [46, 56],
            iconAnchor: isCampus ? [29, 62] : [23, 52],
            popupAnchor: [0, -52]
        });

        const popupIcon = isCampus ? '🏛️' : '🚏';
        const marker = L.marker(coords, { icon: stopIcon }).addTo(map);
        marker.bindPopup(`
            <div style="font-family: sans-serif; min-width: 170px;">
                <div style="font-weight: 800; color: #1d4ed8; margin-bottom: 4px;">${popupIcon} ${stop.name}</div>
                <div style="font-size: 12px; color: #475569;">Urutan Rute: #${stop.order_index || (idx + 1)}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                    Koordinat: ${Number(stop.latitude).toFixed(5)}, ${Number(stop.longitude).toFixed(5)}
                </div>
            </div>
        `);
        stopMarkers[stop.name] = marker;
    });

    const shuttleMarkers = {};
    const shuttleAnimations = {};
    const shuttleFacingWest = {};

    function extractShuttleNumber(vehicleCode, fallbackIndex) {
        const match = String(vehicleCode || '').match(/(\d+)/);
        if (match && match[1]) {
            return String(parseInt(match[1], 10));
        }
        return String((fallbackIndex || 0) + 1);
    }

    function animateMarkerTo(code, marker, targetLatLng, durationMs = 1600) {
        if (shuttleAnimations[code]) {
            cancelAnimationFrame(shuttleAnimations[code]);
        }

        const startLatLng = marker.getLatLng();
        const startLat = startLatLng.lat;
        const startLng = startLatLng.lng;
        const endLat = targetLatLng[0];
        const endLng = targetLatLng[1];

        const dLng = endLng - startLng;
        if (Math.abs(dLng) > 0.00001) {
            shuttleFacingWest[code] = dLng < 0;
        }

        if (Math.abs(endLat - startLat) < 0.000001 && Math.abs(endLng - startLng) < 0.000001) {
            return;
        }

        const startTime = performance.now();

        function step(now) {
            const elapsed = now - startTime;
            const progress = Math.min(1, elapsed / durationMs);
            const currentLat = startLat + (endLat - startLat) * progress;
            const currentLng = startLng + (endLng - startLng) * progress;

            marker.setLatLng([currentLat, currentLng]);

            if (progress < 1) {
                shuttleAnimations[code] = requestAnimationFrame(step);
            }
        }

        shuttleAnimations[code] = requestAnimationFrame(step);
    }

    function calculateHaversineKm(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const toRad = deg => (deg * Math.PI) / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function createShuttleIcon(shuttle, idx) {
        const occupied = Number(shuttle.current_passengers ?? shuttle.occupied_seats ?? 0);
        const capacity = maxCapVal(shuttle);
        const isFull = occupied >= capacity;
        const badgeClass = isFull ? 'shuttle-number-full' : 'shuttle-number-available';
        const bodyColor = isFull ? '#dc2626' : '#16a34a';
        const roofColor = isFull ? '#991b1b' : '#15803d';
        const numberLabel = extractShuttleNumber(shuttle.vehicle_code, idx);
        const facingClass = shuttleFacingWest[shuttle.vehicle_code] ? 'facing-west' : '';

        return L.divIcon({
            className: 'custom-shuttle-icon',
            html: `
                <div class="shuttle-vehicle-wrapper">
                    <div class="shuttle-number-badge ${badgeClass}">${numberLabel}</div>
                    <svg class="shuttle-car-svg ${facingClass}" viewBox="0 0 64 36" xmlns="http://www.w3.org/2000/svg">

                        <ellipse cx="32" cy="33" rx="26" ry="2.5" fill="rgba(15,23,42,0.28)" />

                        <path d="M6 20 C6 13, 12 8, 20 8 L44 8 C52 8, 57 14, 59 20 L60 26 C60 28.5, 58 30, 55 30 L9 30 C6.5 30, 5 28.5, 5 26 Z"
                              fill="${bodyColor}" stroke="#ffffff" stroke-width="2" />

                        <path d="M18 8 L45 8 C48 8, 51 10, 53 13 L13 13 C14.5 10, 16 8, 18 8 Z"
                              fill="${roofColor}" />

                        <rect x="13" y="11" width="11" height="7" rx="2" fill="#e0f2fe" />
                        <rect x="26" y="11" width="11" height="7" rx="2" fill="#e0f2fe" />
                        <path d="M39 11 L49 11 C52 11, 54.5 14, 55.5 18 L39 18 Z" fill="#bae6fd" />

                        <rect x="57" y="21" width="3" height="4" rx="1" fill="#fde047" />
                        <rect x="5" y="21" width="2.5" height="4" rx="1" fill="#f87171" />

                        <circle cx="17" cy="29" r="5.5" fill="#1e293b" stroke="#ffffff" stroke-width="1.8" />
                        <circle cx="17" cy="29" r="2.2" fill="#94a3b8" />
                        <circle cx="47" cy="29" r="5.5" fill="#1e293b" stroke="#ffffff" stroke-width="1.8" />
                        <circle cx="47" cy="29" r="2.2" fill="#94a3b8" />
                    </svg>
                </div>
            `,
            iconSize: [52, 54],
            iconAnchor: [26, 44],
            popupAnchor: [0, -40]
        });
    }

    function maxCapVal(shuttle) {
        return Math.max(1, Number(shuttle.max_capacity ?? shuttle.capacity ?? 12));
    }

    function buildPopupHtml(shuttle) {
        const occupied = Number(shuttle.current_passengers ?? shuttle.occupied_seats ?? 0);
        const capacity = maxCapVal(shuttle);
        const available = Math.max(0, Number(shuttle.available_seats ?? (capacity - occupied)));
        const isFull = occupied >= capacity;
        const statusText = isFull ? 'Penuh (Full)' : 'Tersedia';
        const statusColor = isFull ? '#dc2626' : '#16a34a';
        const speedVal = Number(shuttle.speed || 0);
        const speedLabel = speedVal <= 0.1
            ? '0.0 km/h (🛑 Berhenti di Halte)'
            : `${speedVal.toFixed(1)} km/h`;

        return `
            <div style="min-width: 210px; font-family: sans-serif;">
                <div style="font-weight: 800; font-size: 14px; color: #1e3a8a; margin-bottom: 4px;">
                    🚌 ${shuttle.vehicle_code} (${shuttle.plate_number || 'B 1000 KMP'})
                </div>
                <div style="font-size: 12px; color: #475569; margin-bottom: 6px;">
                    Pengemudi: <strong>${shuttle.driver_name}</strong>
                </div>
                <div style="font-size: 12px; line-height: 1.65;">
                    <div>Status Kapasitas: <strong style="color: ${statusColor};">${statusText}</strong></div>
                    <div>Keterisian: <strong>${occupied}/${capacity} Penumpang</strong></div>
                    <div>Sisa Kursi: <strong>${available} kursi</strong></div>
                    <div>Kecepatan: <strong>${speedLabel}</strong></div>
                    <div style="color: #64748b; font-size: 11px; margin-top: 4px;">
                        Update: ${shuttle.updated_human || '-'}
                    </div>
                </div>
            </div>
        `;
    }

    function renderStopEtaList(shuttles) {
        const etaContainer = document.getElementById('eta-list-container');
        if (!etaContainer) {
            return;
        }

        const activeShuttles = (shuttles || []).filter(
            s => s.current_lat !== null && s.current_lng !== null
        );

        if (activeShuttles.length === 0) {
            etaContainer.innerHTML = '<p style="color:#64748b; font-size:0.85rem;">Menunggu telemetri armada...</p>';
            return;
        }

        etaContainer.innerHTML = campusStops.map(function (stop) {
            let nearestShuttle = null;
            let minDistanceKm = Infinity;

            activeShuttles.forEach(function (shuttle) {
                const distKm = calculateHaversineKm(
                    Number(shuttle.current_lat),
                    Number(shuttle.current_lng),
                    Number(stop.latitude),
                    Number(stop.longitude)
                );
                if (distKm < minDistanceKm) {
                    minDistanceKm = distKm;
                    nearestShuttle = shuttle;
                }
            });

            const speedKmh = Math.max(25, Number(nearestShuttle.speed || 32));
            const etaMinutes = (minDistanceKm / speedKmh) * 60;
            const distanceMeters = Math.round(minDistanceKm * 1000);
            const distanceLabel = distanceMeters >= 1000
                ? `${(distanceMeters / 1000).toFixed(1)} km`
                : `${distanceMeters} m`;

            let etaText = '';
            if (distanceMeters < 100) {
                etaText = 'Berhenti di Halte';
            } else if (etaMinutes < 1) {
                etaText = `~${Math.max(10, Math.round(etaMinutes * 60))} detik`;
            } else {
                etaText = `~${Math.ceil(etaMinutes)} menit`;
            }

            const stopIconEmoji = isCampusMainStop(stop.name) ? '🏛️' : '🚏';

            return `
                <div class="eta-item" data-stop-name="${stop.name}">
                    <div class="eta-item-left">
                        <span class="eta-stop-name">${stopIconEmoji} ${stop.name}</span>
                        <span class="eta-shuttle-info">${nearestShuttle.vehicle_code} (${distanceLabel})</span>
                    </div>
                    <span class="eta-time-badge">⏱️ ${etaText}</span>
                </div>
            `;
        }).join('');

        etaContainer.querySelectorAll('.eta-item').forEach(function (item) {
            item.addEventListener('click', function () {
                const stopName = item.getAttribute('data-stop-name');
                const marker = stopMarkers[stopName];
                if (marker) {
                    map.panTo(marker.getLatLng(), { animate: true, duration: 0.5 });
                    marker.openPopup();
                }
            });
        });
    }

    function renderShuttleSidebar(shuttles) {
        const listContainer = document.getElementById('shuttle-list-container');
        if (!listContainer) {
            return;
        }

        if (!Array.isArray(shuttles) || shuttles.length === 0) {
            listContainer.innerHTML = '<p style="color:#64748b; font-size:0.9rem;">Belum ada armada aktif.</p>';
            return;
        }

        listContainer.innerHTML = shuttles.map(function (shuttle) {
            const occupied = Number(shuttle.current_passengers ?? shuttle.occupied_seats ?? 0);
            const capacity = maxCapVal(shuttle);
            const available = Math.max(0, Number(shuttle.available_seats ?? (capacity - occupied)));
            const isFull = occupied >= capacity;
            const percentage = Math.min(100, Math.round((occupied / capacity) * 100));
            const speedNum = Number(shuttle.speed || 0);
            const speedDisplay = speedNum <= 0.1
                ? '🛑 Berhenti di Halte'
                : `⚡ ${speedNum.toFixed(1)} km/h`;

            const fillClass = isFull ? 'fill-high' : 'fill-low';
            const badgeClass = isFull ? 'badge-danger' : 'badge-success';
            const statusLabel = isFull ? 'Penuh' : 'Tersedia';

            return `
                <div class="shuttle-card" data-vehicle="${shuttle.vehicle_code}">
                    <div class="shuttle-card-header">
                        <div>
                            <span class="shuttle-code">🚌 ${shuttle.vehicle_code}</span>
                            <span class="shuttle-driver-inline">· ${shuttle.driver_name}</span>
                        </div>
                        <span class="badge ${badgeClass}">${statusLabel} (${occupied}/${capacity} Penumpang)</span>
                    </div>

                    <div class="capacity-bar-track">
                        <div class="capacity-bar-fill ${fillClass}" style="width: ${percentage}%;"></div>
                    </div>

                    <div class="shuttle-coords">
                        <span>Sisa ${available} kursi</span>
                        <span>${speedDisplay}</span>
                        <span>🕒 ${shuttle.updated_human || '-'}</span>
                    </div>
                </div>
            `;
        }).join('');

        const cards = listContainer.querySelectorAll('.shuttle-card');
        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                const code = card.getAttribute('data-vehicle');
                const marker = shuttleMarkers[code];
                if (marker) {
                    map.panTo(marker.getLatLng(), { animate: true, duration: 0.5 });
                    marker.openPopup();
                }
            });
        });
    }

    async function fetchLatestShuttles() {
        const statusTextEl = document.getElementById('polling-status-text');
        const lastSyncEl = document.getElementById('last-sync-time');

        try {
            const response = await fetch(apiEndpoint, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const shuttles = await response.json();

            const activeCodes = new Set();

            shuttles.forEach(function (shuttle, idx) {
                if (shuttle.current_lat === null || shuttle.current_lng === null) {
                    return;
                }

                const newLatLng = [Number(shuttle.current_lat), Number(shuttle.current_lng)];
                const code = shuttle.vehicle_code;
                activeCodes.add(code);

                if (shuttleMarkers[code]) {
                    animateMarkerTo(code, shuttleMarkers[code], newLatLng, 2800);
                    shuttleMarkers[code].setIcon(createShuttleIcon(shuttle, idx));
                    shuttleMarkers[code].setPopupContent(buildPopupHtml(shuttle));
                } else {
                    const marker = L.marker(newLatLng, {
                        icon: createShuttleIcon(shuttle, idx)
                    }).addTo(map);

                    marker.bindPopup(buildPopupHtml(shuttle));
                    shuttleMarkers[code] = marker;
                }
            });

            Object.keys(shuttleMarkers).forEach(function (code) {
                if (!activeCodes.has(code)) {
                    map.removeLayer(shuttleMarkers[code]);
                    delete shuttleMarkers[code];
                }
            });

            renderShuttleSidebar(shuttles);
            renderStopEtaList(shuttles);

            if (statusTextEl) {
                statusTextEl.textContent = 'Live';
            }
            if (lastSyncEl) {
                const now = new Date();
                lastSyncEl.textContent = now.toLocaleTimeString('id-ID');
            }
        } catch (error) {
            if (statusTextEl) {
                statusTextEl.textContent = 'Menunggu koneksi server...';
            }
            console.error('Gagal memuat data armada:', error);
        }
    }

    fetchLatestShuttles();
    setInterval(fetchLatestShuttles, pollIntervalMs);
});
