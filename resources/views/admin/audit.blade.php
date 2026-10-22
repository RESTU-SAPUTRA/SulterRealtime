@extends('layouts.master')

@section('title', 'Audit Trail Lintas Sistem - Shuttle Kampus (Kelompok 5)')

@push('styles')
    
    <link rel="stylesheet" href="{{ asset('css/audit.css') }}?v={{ filemtime(public_path('css/audit.css')) }}">
@endpush

@section('content')
    
    <section class="audit-summary-grid">
        <div class="summary-card">
            <div class="summary-label">Total Audit Log</div>
            <div class="summary-value" id="total-audit-records">{{ number_format($totalLogs, 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Armada Terdaftar</div>
            <div class="summary-value">{{ $shuttles->count() }} Unit</div>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div class="audit-header-left">
                <h1 class="card-title">📋 Tabel Audit Trail Lintas Sistem</h1>
                <span class="badge badge-success">
                    <span class="pulse-dot"></span>
                    <span>Live</span>
                </span>
            </div>

            <div class="audit-header-actions">
                <form method="GET" action="{{ route('admin.audit') }}" class="filter-form">
                    <label for="vehicle-filter-select" class="filter-label">Armada:</label>
                    <select name="vehicle_code" id="vehicle-filter-select" class="form-select">
                        <option value="">Semua Armada</option>
                        @foreach ($shuttles as $shuttle)
                            <option value="{{ $shuttle->vehicle_code }}"
                                {{ $selectedVehicle === $shuttle->vehicle_code ? 'selected' : '' }}>
                                {{ $shuttle->vehicle_code }} — {{ $shuttle->driver_name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    @if (!empty($selectedVehicle))
                        <a href="{{ route('admin.audit') }}" class="btn btn-outline btn-sm">Reset</a>
                    @endif
                </form>

                <button type="button" id="toggle-live-audit" class="btn btn-outline btn-sm">
                    ⏸ Jeda Live Update
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Armada</th>
                        <th>Koordinat (Lat, Lng)</th>
                        <th>Kecepatan</th>
                        <th>Muatan & Status</th>
                        <th>Sumber</th>
                        <th>Hash SHA-256</th>
                        <th>Waktu (WIB)</th>
                    </tr>
                </thead>
                <tbody id="audit-table-body" data-endpoint="{{ route('api.v1.audit-logs.latest') }}">
                    @forelse ($auditLogs as $log)
                        @php
                            $logMaxCap = max(1, (int) ($log->max_capacity ?: 12));
                            $logIsFull = (int) $log->occupied_seats >= $logMaxCap || $log->capacity_status === 'Penuh';
                            $shortSource = str_contains((string) $log->source_system, 'SIMULATOR')
                                ? 'IoT Simulator'
                                : (str_contains((string) $log->source_system, 'OPERATOR') ? 'Input Operator' : $log->source_system);
                        @endphp
                        <tr>
                            <td class="mono-text text-muted-cell">#{{ $log->id }}</td>
                            <td>
                                <span class="badge badge-primary">🚌 {{ $log->vehicle_code }}</span>
                            </td>
                            <td class="mono-text">
                                {{ number_format((float) $log->latitude, 6, '.', '') }},
                                {{ number_format((float) $log->longitude, 6, '.', '') }}
                            </td>
                            <td><strong>{{ number_format((float) $log->speed, 1, '.', '') }} km/h</strong></td>
                            <td>
                                <div class="audit-capacity-cell">
                                    <strong>{{ $log->occupied_seats }}/{{ $logMaxCap }} Penumpang</strong>
                                    <span class="badge {{ $logIsFull ? 'badge-danger' : 'badge-success' }}">
                                        {{ $logIsFull ? 'Penuh' : 'Tersedia' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="source-pill" title="{{ $log->source_system }}">{{ $shortSource }}</span>
                            </td>
                            <td class="mono-text hash-cell" title="{{ $log->payload_hash }}">
                                {{ $log->payload_hash ? substr($log->payload_hash, 0, 12) . '...' : '-' }}
                            </td>
                            <td class="mono-text time-cell">
                                {{ $log->logged_at ? $log->logged_at->timezone('Asia/Jakarta')->format('H:i:s · d/m/Y') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="audit-empty-cell">
                                Belum ada data log telemetri yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($auditLogs->hasPages())
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Menampilkan <strong>{{ $auditLogs->firstItem() }}–{{ $auditLogs->lastItem() }}</strong> dari <strong>{{ number_format($auditLogs->total(), 0, ',', '.') }}</strong> log
                </div>
                <div class="pagination-controls">
                    @if ($auditLogs->onFirstPage())
                        <span class="page-btn disabled">« Sebelumnya</span>
                    @else
                        <a href="{{ $auditLogs->previousPageUrl() }}" class="page-btn">« Sebelumnya</a>
                    @endif

                    <span class="page-indicator">
                        Hal. <strong>{{ $auditLogs->currentPage() }}</strong> / {{ $auditLogs->lastPage() }}
                    </span>

                    @if ($auditLogs->hasMorePages())
                        <a href="{{ $auditLogs->nextPageUrl() }}" class="page-btn">Berikutnya »</a>
                    @else
                        <span class="page-btn disabled">Berikutnya »</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    
    <script src="{{ asset('js/audit.js') }}?v={{ filemtime(public_path('js/audit.js')) }}"></script>
@endpush
