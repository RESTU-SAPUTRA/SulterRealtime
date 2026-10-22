<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTelemetryAuditLog;
use App\Models\Shuttle;
use App\Models\TelemetryAuditLog;
use App\Services\ShuttleRouteSimulator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shuttle_id'         => ['required_without:vehicle_code', 'nullable', 'string', 'max:20'],
            'vehicle_code'       => ['required_without:shuttle_id', 'nullable', 'string', 'max:20'],
            'latitude'           => ['required', 'numeric', 'between:-90,90'],
            'longitude'          => ['required', 'numeric', 'between:-180,180'],
            'speed'              => ['nullable', 'numeric', 'min:0'],
            'current_passengers' => ['required_without:occupied_seats', 'nullable', 'integer', 'min:0'],
            'occupied_seats'     => ['required_without:current_passengers', 'nullable', 'integer', 'min:0'],
            'max_capacity'       => ['nullable', 'integer', 'min:1', 'max:100'],
            'capacity'           => ['nullable', 'integer', 'min:1', 'max:100'],
            'source_system'      => ['nullable', 'string', 'max:50'],
        ]);

        $vehicleCode = (string) ($validated['vehicle_code'] ?? $validated['shuttle_id']);
        $existingShuttle = Shuttle::where('vehicle_code', $vehicleCode)->first();

        $maxCapacity = (int) ($validated['max_capacity']
            ?? $validated['capacity']
            ?? ($existingShuttle ? $existingShuttle->capacity : 12));

        $rawPassengers = (int) ($validated['current_passengers'] ?? $validated['occupied_seats'] ?? 0);

        if ($rawPassengers < 0 || $rawPassengers > $maxCapacity) {
            return response()->json([
                'status'  => 'error',
                'message' => "Jumlah penumpang (current_passengers: {$rawPassengers}) harus berada pada rentang 0 hingga kapasitas maksimal ({$maxCapacity}).",
            ], 422);
        }

        $currentPassengers = max(0, min($maxCapacity, $rawPassengers));
        $availableSeats = max(0, $maxCapacity - $currentPassengers);
        $capacityStatus = $currentPassengers >= $maxCapacity ? 'Penuh' : 'Tersedia';
        $speed = isset($validated['speed']) ? (float) $validated['speed'] : ($existingShuttle ? (float) $existingShuttle->speed : 30.0);

        $shuttle = Shuttle::updateOrCreate(
            ['vehicle_code' => $vehicleCode],
            [
                'driver_name'       => $existingShuttle->driver_name ?? ('Driver ' . $vehicleCode),
                'plate_number'      => $existingShuttle->plate_number ?? 'B 1000 KMP',
                'capacity'          => $maxCapacity,
                'occupied_seats'    => $currentPassengers,
                'current_lat'       => $validated['latitude'],
                'current_lng'       => $validated['longitude'],
                'speed'             => $speed,
                'status'            => $existingShuttle->status ?? 'active',
                'last_telemetry_at' => now(),
            ]
        );

        $auditPayload = [
            'shuttle_id'         => $vehicleCode,
            'vehicle_code'       => $vehicleCode,
            'latitude'           => (float) $validated['latitude'],
            'longitude'          => (float) $validated['longitude'],
            'speed'              => $speed,
            'current_passengers' => $currentPassengers,
            'occupied_seats'     => $currentPassengers,
            'max_capacity'       => $maxCapacity,
            'available_seats'    => $availableSeats,
            'capacity_status'    => $capacityStatus,
            'source_system'      => $validated['source_system'] ?? 'GPS_SIMULATOR_IOT',
        ];

        ProcessTelemetryAuditLog::dispatch($auditPayload);

        return response()->json([
            'status'          => 'success',
            'message'         => 'Telemetry received and queued for audit',
            'timestamp'       => now()->toIso8601String(),
            'available_seats' => $availableSeats,
            'capacity_status' => $capacityStatus,
            'data'            => $shuttle,
        ], 200);
    }

    public function recordTelemetry(Request $request): JsonResponse
    {
        return $this->store($request);
    }

    public function getLatestShuttles(): JsonResponse
    {

        ShuttleRouteSimulator::tickIfDue(2);

        $shuttles = Shuttle::where('status', 'active')
            ->orderBy('vehicle_code')
            ->get()
            ->map(function (Shuttle $shuttle) {
                $timestamp = $shuttle->last_telemetry_at ?? $shuttle->updated_at;
                $maxCapacity = max(1, (int) $shuttle->capacity);
                $currentPassengers = max(0, min($maxCapacity, (int) $shuttle->occupied_seats));
                $availableSeats = max(0, $maxCapacity - $currentPassengers);
                $isFull = $currentPassengers >= $maxCapacity;
                $capacityStatus = $isFull ? 'Penuh' : 'Tersedia';

                return [
                    'id'                 => $shuttle->id,
                    'shuttle_id'         => $shuttle->vehicle_code,
                    'vehicle_code'       => $shuttle->vehicle_code,
                    'driver_name'        => $shuttle->driver_name,
                    'plate_number'       => $shuttle->plate_number,
                    'capacity'           => $maxCapacity,
                    'max_capacity'       => $maxCapacity,
                    'occupied_seats'     => $currentPassengers,
                    'current_passengers' => $currentPassengers,
                    'available_seats'    => $availableSeats,
                    'capacity_status'    => $capacityStatus,
                    'is_full'            => $isFull,
                    'occupancy_label'    => "{$currentPassengers}/{$maxCapacity} Penumpang",
                    'current_lat'        => $shuttle->current_lat !== null ? (float) $shuttle->current_lat : null,
                    'current_lng'        => $shuttle->current_lng !== null ? (float) $shuttle->current_lng : null,
                    'speed'              => (float) $shuttle->speed,
                    'status'             => $shuttle->status,
                    'last_telemetry_at'  => $timestamp ? $timestamp->timezone('Asia/Jakarta')->toIso8601String() : null,
                    'updated_human'      => $timestamp ? $timestamp->timezone('Asia/Jakarta')->format('H:i:s') : '-',
                ];
            });

        return response()->json($shuttles, 200);
    }

    public function getLatestAuditLogs(Request $request): JsonResponse
    {
        $vehicleCode = $request->query('vehicle_code');
        $limit = min(100, max(10, (int) $request->query('limit', 25)));

        $query = TelemetryAuditLog::query()
            ->orderByDesc('logged_at')
            ->orderByDesc('id');

        if (!empty($vehicleCode)) {
            $query->where('vehicle_code', $vehicleCode);
        }

        $logs = $query->limit($limit)->get()->map(function (TelemetryAuditLog $log) {
            $occupied = (int) $log->occupied_seats;
            $maxCap = max(1, (int) ($log->max_capacity ?: 12));
            $capStatus = $log->capacity_status ?: ($occupied >= $maxCap ? 'Penuh' : 'Tersedia');

            return [
                'id'                 => $log->id,
                'shuttle_id'         => $log->vehicle_code,
                'vehicle_code'       => $log->vehicle_code,
                'latitude'           => (float) $log->latitude,
                'longitude'          => (float) $log->longitude,
                'speed'              => (float) $log->speed,
                'occupied_seats'     => $occupied,
                'current_passengers' => $occupied,
                'max_capacity'       => $maxCap,
                'available_seats'    => max(0, $maxCap - $occupied),
                'capacity_status'    => $capStatus,
                'occupancy_label'    => "{$occupied}/{$maxCap} Penumpang",
                'source_system'      => $log->source_system,
                'payload_hash'       => $log->payload_hash ?? '-',
                'logged_at'          => $log->logged_at ? $log->logged_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '-',
            ];
        });

        return response()->json([
            'total_records' => TelemetryAuditLog::count(),
            'logs'          => $logs,
        ], 200);
    }
}
