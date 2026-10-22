@extends('layouts.master')

@section('title', 'Peta Live Penumpang & Estimasi ETA Halte - Shuttle Kampus (Kelompok 5)')

@push('styles')
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="" />
    
    <link rel="stylesheet" href="{{ asset('css/tracking.css') }}?v={{ filemtime(public_path('css/tracking.css')) }}">
@endpush

@section('content')
    <div class="tracking-grid">
        
        <section class="card map-wrapper" id="map-card-wrapper">
            <div class="card-header">
                <div>
                    <h1 class="card-title">🗺️ Peta Shuttle Kampus</h1>
                </div>
                <div class="map-header-actions">
                    <span class="badge badge-success">
                        <span class="pulse-dot"></span>
                        <span id="polling-status-text">Live</span>
                    </span>
                    <button type="button" id="btn-fullscreen-map" class="btn-fullscreen" title="Tampilkan Peta Layar Penuh">
                        ⛶ Layar Penuh
                    </button>
                </div>
            </div>

            <div class="map-status-bar">
                <span>Rute: <strong>Citra Raya ⇄ Cikupa ⇄ Kampus UNIPI ⇄ Bitung</strong></span>
                <span>Update: <strong id="last-sync-time">-</strong></span>
            </div>

            <div class="map-legend">
                <span class="legend-item">
                    <span class="legend-dot legend-stop"></span> Halte
                </span>
                <span class="legend-item">
                    <span class="legend-dot legend-shuttle-green"></span> Tersedia (Hijau)
                </span>
                <span class="legend-item">
                    <span class="legend-dot legend-shuttle-red"></span> Penuh (Merah)
                </span>
            </div>

            <div id="shuttle-map"
                 data-endpoint="{{ route('api.v1.shuttles.latest') }}"
                 data-stops='@json($stops)'></div>
        </section>

        <aside class="sidebar-stack">
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">🚏 Estimasi Kedatangan (ETA Halte)</h2>
                </div>
                <div class="card-body">
                    <div id="eta-list-container" class="eta-list">
                        @foreach ($stops as $stop)
                            @php
                                $isCampusStop = str_contains(strtolower($stop->name), 'kampus') || str_contains(strtolower($stop->name), 'unipi');
                            @endphp
                            <div class="eta-item" data-stop-name="{{ $stop->name }}">
                                <div>
                                    <span class="eta-stop-name">{{ $isCampusStop ? '🏛️' : '🚏' }} {{ $stop->name }}</span>
                                    <span class="eta-shuttle-info">Memuat...</span>
                                </div>
                                <span class="eta-time-badge">⏱️ -</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">🚌 Armada Aktif</h2>
                    <span class="badge badge-primary">{{ $shuttles->count() }} Unit</span>
                </div>

                <div class="card-body">
                    <div id="shuttle-list-container" class="shuttle-list">
                        @forelse ($shuttles as $shuttle)
                            @php
                                $occupied = (int) $shuttle->occupied_seats;
                                $capacity = max(1, (int) $shuttle->capacity);
                                $available = max(0, $capacity - $occupied);
                                $isFull = $occupied >= $capacity;
                                $percentage = min(100, (int) round(($occupied / $capacity) * 100));
                            @endphp
                            <div class="shuttle-card" data-vehicle="{{ $shuttle->vehicle_code }}">
                                <div class="shuttle-card-header">
                                    <div>
                                        <span class="shuttle-code">🚌 {{ $shuttle->vehicle_code }}</span>
                                        <span class="shuttle-driver-inline">· {{ $shuttle->driver_name }}</span>
                                    </div>
                                    <span class="badge {{ $isFull ? 'badge-danger' : 'badge-success' }}">
                                        {{ $isFull ? 'Penuh' : 'Tersedia' }} ({{ $occupied }}/{{ $capacity }} Penumpang)
                                    </span>
                                </div>

                                <div class="capacity-bar-track">
                                    <div class="capacity-bar-fill {{ $isFull ? 'fill-high' : 'fill-low' }}" style="width: {{ $percentage }}%;"></div>
                                </div>

                                <div class="shuttle-coords">
                                    <span>Sisa {{ $available }} kursi</span>
                                    <span>{{ (float) $shuttle->speed <= 0.1 ? '🛑 Berhenti di Halte' : '⚡ ' . number_format((float) $shuttle->speed, 1) . ' km/h' }}</span>
                                    <span>🕒 {{ $shuttle->last_telemetry_at ? $shuttle->last_telemetry_at->timezone('Asia/Jakarta')->format('H:i:s') : '-' }}</span>
                                </div>
                            </div>
                        @empty
                            <p>Belum ada armada aktif.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
    
    <script src="{{ asset('js/tracking.js') }}?v={{ filemtime(public_path('js/tracking.js')) }}"></script>
@endpush
