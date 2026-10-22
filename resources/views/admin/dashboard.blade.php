@extends('layouts.master')

@section('title', 'Dashboard Admin Operasional - Shuttle Kampus (Kelompok 5)')

@push('styles')
    
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
@endpush

@section('content')
    @if (session('success'))
        <div class="alert-success">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            ⚠️ {{ $errors->first() }}
        </div>
    @endif

    <section class="admin-metrics-grid">
        <div class="admin-metric-card">
            <div class="admin-metric-label">Total Armada</div>
            <div class="admin-metric-value">{{ $shuttles->count() }} Unit</div>
        </div>
        <div class="admin-metric-card">
            <div class="admin-metric-label">Armada Aktif</div>
            <div class="admin-metric-value">{{ $shuttles->where('status', 'active')->count() }} Unit</div>
        </div>
        <div class="admin-metric-card">
            <div class="admin-metric-label">Total Halte</div>
            <div class="admin-metric-value">{{ $stops->count() }} Halte</div>
        </div>
        <div class="admin-metric-card">
            <div class="admin-metric-label">Total Audit Log</div>
            <div class="admin-metric-value">{{ number_format($totalAuditLogs, 0, ',', '.') }}</div>
        </div>
    </section>

    @php
        $nextNumber = $shuttles->count() + 1;
        $suggestedCode = sprintf('SHT-%02d', $nextNumber);
        $suggestedPlate = sprintf('B %d KMP', 1050 + $nextNumber);
        $isEditingShuttle = isset($editingShuttle) && $editingShuttle !== null;
    @endphp
    <section class="card admin-section-card" id="armada-section">
        <div class="card-header">
            <h1 class="card-title">🛠️ Monitoring Operasional Armada</h1>
            <a href="{{ route('admin.audit') }}" class="btn btn-primary btn-sm">
                📋 Audit Trail
            </a>
        </div>

        <div class="inline-form-bar {{ $isEditingShuttle ? 'edit-mode-bar' : '' }}">
            @if ($isEditingShuttle)
                <div class="edit-banner-label">
                    ✏️ Mode Edit Armada: <strong>{{ $editingShuttle->vehicle_code }} ({{ $editingShuttle->plate_number }})</strong>
                </div>
            @endif
            <form method="POST" action="{{ $isEditingShuttle ? route('admin.shuttles.update', $editingShuttle) : route('admin.shuttles.store') }}">
                @csrf
                @if ($isEditingShuttle)
                    @method('PUT')
                @endif
                <div class="shuttle-form-grid">
                    <div class="form-group">
                        <label for="vehicle_code" class="form-label-xs">Kode Armada</label>
                        <input
                            type="text"
                            id="vehicle_code"
                            name="vehicle_code"
                            value="{{ old('vehicle_code', $isEditingShuttle ? $editingShuttle->vehicle_code : $suggestedCode) }}"
                            class="form-input-sm"
                            placeholder="SHT-04"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="plate_number" class="form-label-xs">No. Polisi</label>
                        <input
                            type="text"
                            id="plate_number"
                            name="plate_number"
                            value="{{ old('plate_number', $isEditingShuttle ? $editingShuttle->plate_number : $suggestedPlate) }}"
                            class="form-input-sm"
                            placeholder="B 1054 KMP"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="driver_name" class="form-label-xs">Nama Pengemudi</label>
                        <input
                            type="text"
                            id="driver_name"
                            name="driver_name"
                            value="{{ old('driver_name', $isEditingShuttle ? $editingShuttle->driver_name : '') }}"
                            class="form-input-sm"
                            placeholder="Nama Sopir"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="capacity" class="form-label-xs">Kursi</label>
                        <input
                            type="number"
                            id="capacity"
                            name="capacity"
                            value="{{ old('capacity', $isEditingShuttle ? $editingShuttle->capacity : 12) }}"
                            min="1"
                            max="60"
                            class="form-input-sm"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label-xs">{{ $isEditingShuttle ? 'Status' : 'Status Awal' }}</label>
                        @php
                            $currentShuttleStatus = old('status', $isEditingShuttle ? $editingShuttle->status : 'active');
                        @endphp
                        <select id="status" name="status" class="form-input-sm" required>
                            <option value="active" {{ $currentShuttleStatus === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="maintenance" {{ $currentShuttleStatus === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="inactive" {{ $currentShuttleStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group form-actions-inline">
                        <button type="submit" class="btn btn-primary btn-submit-sm">
                            {{ $isEditingShuttle ? '💾 Simpan' : '➕ Tambah Armada' }}
                        </button>
                        @if ($isEditingShuttle)
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline btn-cancel-sm">
                                ✖ Batal
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Armada</th>
                        <th>Pengemudi</th>
                        <th>Muatan & Kursi</th>
                        <th>Kecepatan & Update</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shuttles as $shuttle)
                        @php
                            $isFull = (int) $shuttle->occupied_seats >= (int) $shuttle->capacity;
                            $isRowEditing = $isEditingShuttle && $editingShuttle->id === $shuttle->id;
                        @endphp
                        <tr class="{{ $isRowEditing ? 'row-editing' : '' }}">
                            <td>
                                <div class="vehicle-cell">
                                    <strong>🚌 {{ $shuttle->vehicle_code }}</strong>
                                    <span class="plate-pill">{{ $shuttle->plate_number }}</span>
                                </div>
                            </td>
                            <td>{{ $shuttle->driver_name }}</td>
                            <td>
                                <div class="occupancy-inline">
                                    <strong>{{ $shuttle->occupied_seats }}/{{ $shuttle->capacity }} Penumpang</strong>
                                    <span class="badge {{ $isFull ? 'badge-danger' : 'badge-success' }}">
                                        {{ $isFull ? 'Penuh' : 'Tersedia' }}
                                    </span>
                                    <span class="text-muted-xs">(Sisa {{ $shuttle->available_seats }})</span>
                                </div>
                            </td>
                            <td>
                                <div class="telemetry-meta">
                                    <span>{{ (float) $shuttle->speed <= 0.1 ? '🛑 Berhenti (0.0 km/h)' : '⚡ ' . number_format((float) $shuttle->speed, 1) . ' km/h' }}</span>
                                    <span class="text-muted-xs">🕒 {{ $shuttle->last_telemetry_at ? $shuttle->last_telemetry_at->timezone('Asia/Jakarta')->format('H:i:s') : '-' }}</span>
                                </div>
                            </td>
                            <td>
                                @if ($shuttle->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif ($shuttle->status === 'maintenance')
                                    <span class="badge badge-warning">Maintenance</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="status-actions-form">
                                    <form method="POST" action="{{ route('admin.shuttles.status', $shuttle) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="btn btn-xs {{ $shuttle->status === 'active' ? 'btn-success' : 'btn-outline' }}">
                                            Active
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.shuttles.status', $shuttle) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="maintenance">
                                        <button type="submit" class="btn btn-xs {{ $shuttle->status === 'maintenance' ? 'btn-warning' : 'btn-outline' }}">
                                            Maint.
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.shuttles.status', $shuttle) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="inactive">
                                        <button type="submit" class="btn btn-xs {{ $shuttle->status === 'inactive' ? 'btn-danger' : 'btn-outline' }}">
                                            Off
                                        </button>
                                    </form>

                                    <a href="{{ route('admin.dashboard', ['edit_shuttle' => $shuttle->id]) }}#armada-section" class="btn btn-xs btn-edit" title="Edit Armada">
                                        ✏️ Edit
                                    </a>

                                    <form method="POST" action="{{ route('admin.shuttles.destroy', $shuttle) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-delete" title="Hapus Armada">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @php
        $nextStopOrder = ($stops->max('order_index') ?? $stops->count()) + 1;
        $isEditingStop = isset($editingStop) && $editingStop !== null;
    @endphp
    <section class="card" id="halte-section">
        <div class="card-header">
            <h2 class="card-title">🚏 Daftar & Tambah Halte</h2>
        </div>

        <div class="inline-form-bar {{ $isEditingStop ? 'edit-mode-bar' : '' }}">
            @if ($isEditingStop)
                <div class="edit-banner-label">
                    ✏️ Mode Edit Halte: <strong>#{{ $editingStop->order_index }} — {{ $editingStop->name }}</strong>
                </div>
            @endif
            <form method="POST" action="{{ $isEditingStop ? route('admin.stops.update', $editingStop) : route('admin.stops.store') }}">
                @csrf
                @if ($isEditingStop)
                    @method('PUT')
                @endif
                <div class="stop-form-grid">
                    <div class="form-group">
                        <label for="order_index" class="form-label-xs">Urutan (#)</label>
                        <input
                            type="number"
                            id="order_index"
                            name="order_index"
                            value="{{ old('order_index', $isEditingStop ? $editingStop->order_index : $nextStopOrder) }}"
                            min="1"
                            max="100"
                            class="form-input-sm"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="stop_name" class="form-label-xs">Nama Halte</label>
                        <input
                            type="text"
                            id="stop_name"
                            name="name"
                            value="{{ old('name', $isEditingStop ? $editingStop->name : '') }}"
                            class="form-input-sm"
                            placeholder="Contoh: Halte Pasar Curug"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="latitude" class="form-label-xs">Latitude</label>
                        <input
                            type="number"
                            step="any"
                            id="latitude"
                            name="latitude"
                            value="{{ old('latitude', $isEditingStop ? number_format((float) $editingStop->latitude, 7, '.', '') : '-6.2250000') }}"
                            class="form-input-sm"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="longitude" class="form-label-xs">Longitude</label>
                        <input
                            type="number"
                            step="any"
                            id="longitude"
                            name="longitude"
                            value="{{ old('longitude', $isEditingStop ? number_format((float) $editingStop->longitude, 7, '.', '') : '106.5450000') }}"
                            class="form-input-sm"
                            required
                        >
                    </div>

                    <div class="form-group form-actions-inline">
                        <button type="submit" class="btn btn-primary btn-submit-sm">
                            {{ $isEditingStop ? '💾 Simpan' : '➕ Tambah Halte' }}
                        </button>
                        @if ($isEditingStop)
                            <a href="{{ route('admin.dashboard') }}#halte-section" class="btn btn-outline btn-cancel-sm">
                                ✖ Batal
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Urutan</th>
                        <th>Nama Halte</th>
                        <th>Koordinat (Lat, Lng)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stops as $stop)
                        @php
                            $isStopRowEditing = $isEditingStop && $editingStop->id === $stop->id;
                            $isCampusStop = str_contains(strtolower($stop->name), 'kampus') || str_contains(strtolower($stop->name), 'unipi');
                        @endphp
                        <tr class="{{ $isStopRowEditing ? 'row-editing' : '' }}">
                            <td><strong>#{{ $stop->order_index }}</strong></td>
                            <td>{{ $isCampusStop ? '🏛️' : '🚏' }} {{ $stop->name }}</td>
                            <td><code>{{ number_format((float) $stop->latitude, 6) }}, {{ number_format((float) $stop->longitude, 6) }}</code></td>
                            <td>
                                <div class="status-actions-form">
                                    <a href="{{ route('admin.dashboard', ['edit_stop' => $stop->id]) }}#halte-section" class="btn btn-xs btn-edit" title="Edit Halte">
                                        ✏️ Edit
                                    </a>

                                    <form method="POST" action="{{ route('admin.stops.destroy', $stop) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-delete" title="Hapus Halte">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
