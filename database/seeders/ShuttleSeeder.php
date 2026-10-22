<?php

namespace Database\Seeders;

use App\Models\Shuttle;
use App\Models\TelemetryAuditLog;
use Illuminate\Database\Seeder;

class ShuttleSeeder extends Seeder
{

    public function run(): void
    {
        $shuttles = [
            [
                'vehicle_code'      => 'SHT-01',
                'driver_name'       => 'Pak Budi Santoso',
                'plate_number'      => 'B 1051 KMP',
                'capacity'          => 12,
                'occupied_seats'    => 3,
                'current_lat'       => -6.2365000,
                'current_lng'       => 106.5218000,
                'speed'             => 30.0,
                'status'            => 'active',
                'last_telemetry_at' => now(),
            ],
            [
                'vehicle_code'      => 'SHT-02',
                'driver_name'       => 'Pak Agus Pratama',
                'plate_number'      => 'B 1052 KMP',
                'capacity'          => 12,
                'occupied_seats'    => 5,
                'current_lat'       => -6.2222578,
                'current_lng'       => 106.5603413,
                'speed'             => 32.5,
                'status'            => 'active',
                'last_telemetry_at' => now(),
            ],
        ];

        foreach ($shuttles as $shuttleData) {
            Shuttle::updateOrCreate(
                ['vehicle_code' => $shuttleData['vehicle_code']],
                $shuttleData
            );

            $payload = [
                'shuttle_id'         => $shuttleData['vehicle_code'],
                'vehicle_code'       => $shuttleData['vehicle_code'],
                'latitude'           => $shuttleData['current_lat'],
                'longitude'          => $shuttleData['current_lng'],
                'speed'              => $shuttleData['speed'],
                'current_passengers' => $shuttleData['occupied_seats'],
                'occupied_seats'     => $shuttleData['occupied_seats'],
                'max_capacity'       => $shuttleData['capacity'],
            ];

            TelemetryAuditLog::create([
                'vehicle_code'    => $payload['vehicle_code'],
                'latitude'        => $payload['latitude'],
                'longitude'       => $payload['longitude'],
                'speed'           => $payload['speed'],
                'occupied_seats'  => $payload['occupied_seats'],
                'max_capacity'    => $shuttleData['capacity'],
                'capacity_status' => $payload['occupied_seats'] >= $shuttleData['capacity'] ? 'Penuh' : 'Tersedia',
                'source_system'   => 'GPS_SIMULATOR_IOT',
                'payload_hash'    => hash('sha256', json_encode($payload)),
                'logged_at'       => now(),
            ]);
        }
    }
}
