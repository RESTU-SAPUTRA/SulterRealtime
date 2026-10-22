<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shuttle;
use App\Models\Stop;
use App\Models\TelemetryAuditLog;
use App\Services\ShuttleRouteSimulator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{

    public function dashboard(Request $request): View
    {
        $shuttles = Shuttle::orderBy('vehicle_code')->get();
        $stops = Stop::orderBy('order_index')->get();
        $totalAuditLogs = TelemetryAuditLog::count();

        $editingShuttle = $request->filled('edit_shuttle')
            ? Shuttle::find($request->query('edit_shuttle'))
            : null;

        $editingStop = $request->filled('edit_stop')
            ? Stop::find($request->query('edit_stop'))
            : null;

        return view('admin.dashboard', [
            'shuttles'       => $shuttles,
            'stops'          => $stops,
            'totalAuditLogs' => $totalAuditLogs,
            'editingShuttle' => $editingShuttle,
            'editingStop'    => $editingStop,
        ]);
    }

    public function storeShuttle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_code' => ['required', 'string', 'max:20', 'unique:shuttles,vehicle_code'],
            'plate_number' => ['required', 'string', 'max:20'],
            'driver_name'  => ['required', 'string', 'max:100'],
            'capacity'     => ['required', 'integer', 'min:1', 'max:60'],
            'status'       => ['required', 'in:active,inactive,maintenance'],
        ]);

        $waypoints = ShuttleRouteSimulator::loopWaypoints();
        $totalShuttleCount = Shuttle::count();
        $startWpIndex = count($waypoints) > 0 ? (($totalShuttleCount * 11) % count($waypoints)) : 0;
        $initialPoint = $waypoints[$startWpIndex] ?? ['lat' => -6.2365000, 'lng' => 106.5218000];

        $vehicleCode = strtoupper(trim($validated['vehicle_code']));

        $shuttle = Shuttle::create([
            'vehicle_code'      => $vehicleCode,
            'plate_number'      => strtoupper(trim($validated['plate_number'])),
            'driver_name'       => trim($validated['driver_name']),
            'capacity'          => (int) $validated['capacity'],
            'occupied_seats'    => 1,
            'current_lat'       => $initialPoint['lat'],
            'current_lng'       => $initialPoint['lng'],
            'speed'             => 30.0,
            'status'            => $validated['status'],
            'last_telemetry_at' => now(),
        ]);

        Cache::put('shuttle_sim_wp_' . $shuttle->vehicle_code, $startWpIndex, now()->addHours(12));

        $payload = [
            'vehicle_code'   => $shuttle->vehicle_code,
            'latitude'       => $shuttle->current_lat,
            'longitude'      => $shuttle->current_lng,
            'speed'          => $shuttle->speed,
            'occupied_seats' => $shuttle->occupied_seats,
        ];

        TelemetryAuditLog::create([
            'vehicle_code'    => $shuttle->vehicle_code,
            'latitude'        => $shuttle->current_lat,
            'longitude'       => $shuttle->current_lng,
            'speed'           => $shuttle->speed,
            'occupied_seats'  => $shuttle->occupied_seats,
            'max_capacity'    => $shuttle->capacity,
            'capacity_status' => $shuttle->occupied_seats >= $shuttle->capacity ? 'Penuh' : 'Tersedia',
            'source_system'   => 'OPERATOR_FLEET_REGISTRATION',
            'payload_hash'    => hash('sha256', json_encode($payload)),
            'logged_at'       => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Armada baru {$shuttle->vehicle_code} ({$shuttle->driver_name}) berhasil ditambahkan ke rute Citra Raya ⇄ Kampus!");
    }

    public function updateShuttle(Request $request, Shuttle $shuttle): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('shuttles', 'vehicle_code')->ignore($shuttle->id),
            ],
            'plate_number' => ['required', 'string', 'max:20'],
            'driver_name'  => ['required', 'string', 'max:100'],
            'capacity'     => ['required', 'integer', 'min:1', 'max:60'],
            'status'       => ['required', 'in:active,inactive,maintenance'],
        ]);

        $newCapacity = (int) $validated['capacity'];
        $adjustedOccupied = min((int) $shuttle->occupied_seats, $newCapacity);

        $shuttle->update([
            'vehicle_code'   => strtoupper(trim($validated['vehicle_code'])),
            'plate_number'   => strtoupper(trim($validated['plate_number'])),
            'driver_name'    => trim($validated['driver_name']),
            'capacity'       => $newCapacity,
            'occupied_seats' => $adjustedOccupied,
            'status'         => $validated['status'],
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Data armada {$shuttle->vehicle_code} ({$shuttle->driver_name}) berhasil diperbarui.");
    }

    public function updateStatus(Request $request, Shuttle $shuttle): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive,maintenance'],
        ]);

        $shuttle->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Status armada {$shuttle->vehicle_code} berhasil diubah menjadi {$validated['status']}.");
    }

    public function destroyShuttle(Shuttle $shuttle): RedirectResponse
    {
        $code = $shuttle->vehicle_code;
        Cache::forget('shuttle_sim_wp_' . $code);
        $shuttle->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Armada {$code} berhasil dihapus.");
    }

    public function storeStop(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'order_index' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $stop = Stop::create([
            'name'        => trim($validated['name']),
            'latitude'    => (float) $validated['latitude'],
            'longitude'   => (float) $validated['longitude'],
            'order_index' => (int) $validated['order_index'],
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Halte baru '{$stop->name}' (#{$stop->order_index}) berhasil ditambahkan.");
    }

    public function updateStop(Request $request, Stop $stop): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'order_index' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $stop->update([
            'name'        => trim($validated['name']),
            'latitude'    => (float) $validated['latitude'],
            'longitude'   => (float) $validated['longitude'],
            'order_index' => (int) $validated['order_index'],
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Halte '{$stop->name}' (#{$stop->order_index}) berhasil diperbarui.");
    }

    public function destroyStop(Stop $stop): RedirectResponse
    {
        $name = $stop->name;
        $stop->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Halte '{$name}' berhasil dihapus.");
    }

    public function auditLogs(Request $request): View
    {
        $selectedVehicle = $request->query('vehicle_code');

        $query = TelemetryAuditLog::query()
            ->latest('logged_at')
            ->orderByDesc('id');

        if (!empty($selectedVehicle)) {
            $query->where('vehicle_code', $selectedVehicle);
        }

        $auditLogs = $query->paginate(15)->withQueryString();
        $shuttles = Shuttle::orderBy('vehicle_code')->get();
        $totalLogs = TelemetryAuditLog::count();

        return view('admin.audit', [
            'auditLogs'       => $auditLogs,
            'shuttles'        => $shuttles,
            'selectedVehicle' => $selectedVehicle,
            'totalLogs'       => $totalLogs,
        ]);
    }
}
