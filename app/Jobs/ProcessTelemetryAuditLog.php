<?php

namespace App\Jobs;

use App\Models\TelemetryAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTelemetryAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        $occupied = (int) ($this->data['occupied_seats'] ?? $this->data['current_passengers'] ?? 0);
        $maxCap = (int) ($this->data['max_capacity'] ?? $this->data['capacity'] ?? 12);
        $capStatus = $this->data['capacity_status'] ?? ($occupied >= $maxCap ? 'Penuh' : 'Tersedia');

        TelemetryAuditLog::create([
            'vehicle_code'    => $this->data['vehicle_code'] ?? $this->data['shuttle_id'],
            'latitude'        => $this->data['latitude'],
            'longitude'       => $this->data['longitude'],
            'speed'           => $this->data['speed'] ?? 0,
            'occupied_seats'  => $occupied,
            'max_capacity'    => $maxCap,
            'capacity_status' => $capStatus,
            'source_system'   => $this->data['source_system'] ?? 'GPS_SIMULATOR_IOT',
            'payload_hash'    => hash('sha256', json_encode($this->data)),
            'logged_at'       => now(),
        ]);
    }
}
