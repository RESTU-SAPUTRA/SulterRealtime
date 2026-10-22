document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('audit-table-body');
    const toggleBtn = document.getElementById('toggle-live-audit');
    const totalCountEl = document.getElementById('total-audit-records');
    const filterSelect = document.getElementById('vehicle-filter-select');

    if (!tableBody || !toggleBtn) {
        return;
    }

    const endpoint = tableBody.getAttribute('data-endpoint') || '/api/v1/audit-logs/latest';
    let isLive = true;

    function formatShortSource(src) {
        const raw = String(src || '');
        if (raw.includes('SIMULATOR')) {
            return 'IoT Simulator';
        }
        if (raw.includes('OPERATOR')) {
            return 'Input Operator';
        }
        return raw || 'IoT GPS';
    }

    function renderRows(logs) {
        if (!Array.isArray(logs) || logs.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="audit-empty-cell">
                        Belum ada data log telemetri yang tercatat.
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = logs.map(function (log) {
            const lat = Number(log.latitude).toFixed(6);
            const lng = Number(log.longitude).toFixed(6);
            const speed = Number(log.speed).toFixed(1);
            const occupied = Number(log.current_passengers ?? log.occupied_seats ?? 0);
            const maxCap = Math.max(1, Number(log.max_capacity || 12));
            const isFull = occupied >= maxCap || log.capacity_status === 'Penuh';
            const statusClass = isFull ? 'badge-danger' : 'badge-success';
            const statusLabel = isFull ? 'Penuh' : 'Tersedia';
            const hashStr = log.payload_hash ? String(log.payload_hash).substring(0, 12) + '...' : '-';
            const shortSource = formatShortSource(log.source_system);

            return `
                <tr>
                    <td class="mono-text text-muted-cell">#${log.id}</td>
                    <td><span class="badge badge-primary">🚌 ${log.vehicle_code}</span></td>
                    <td class="mono-text">${lat}, ${lng}</td>
                    <td><strong>${speed} km/h</strong></td>
                    <td>
                        <div class="audit-capacity-cell">
                            <strong>${occupied}/${maxCap} Penumpang</strong>
                            <span class="badge ${statusClass}">${statusLabel}</span>
                        </div>
                    </td>
                    <td><span class="source-pill" title="${log.source_system || ''}">${shortSource}</span></td>
                    <td class="mono-text hash-cell" title="${log.payload_hash || ''}">${hashStr}</td>
                    <td class="mono-text time-cell">${log.logged_at}</td>
                </tr>
            `;
        }).join('');
    }

    async function fetchAuditLogs() {
        if (!isLive) {
            return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page') || '1';
        if (currentPage !== '1') {
            return;
        }

        try {
            const selectedVehicle = filterSelect ? filterSelect.value : '';
            const queryUrl = selectedVehicle
                ? `${endpoint}?vehicle_code=${encodeURIComponent(selectedVehicle)}&limit=15`
                : `${endpoint}?limit=15`;

            const response = await fetch(queryUrl, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            if (typeof data.total_records === 'number' && totalCountEl) {
                totalCountEl.textContent = data.total_records.toLocaleString('id-ID');
            }

            if (Array.isArray(data.logs)) {
                renderRows(data.logs);
            }
        } catch (error) {
            console.error('Gagal memperbarui audit log:', error);
        }
    }

    toggleBtn.addEventListener('click', function () {
        isLive = !isLive;
        if (isLive) {
            toggleBtn.textContent = '⏸ Jeda Live Update';
            toggleBtn.classList.remove('btn-primary');
            toggleBtn.classList.add('btn-outline');
            fetchAuditLogs();
        } else {
            toggleBtn.textContent = '▶ Aktifkan Live Update';
            toggleBtn.classList.remove('btn-outline');
            toggleBtn.classList.add('btn-primary');
        }
    });

    setInterval(fetchAuditLogs, 3000);
});
