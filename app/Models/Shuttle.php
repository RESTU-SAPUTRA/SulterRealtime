<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shuttle extends Model
{
    use HasFactory;

    protected $table = 'shuttles';

    protected $fillable = [
        'vehicle_code',
        'driver_name',
        'plate_number',
        'capacity',
        'occupied_seats',
        'current_lat',
        'current_lng',
        'speed',
        'status',
        'last_telemetry_at',
    ];

    protected $casts = [
        'capacity'          => 'integer',
        'occupied_seats'    => 'integer',
        'current_lat'       => 'float',
        'current_lng'       => 'float',
        'speed'             => 'float',
        'last_telemetry_at' => 'datetime',
        'updated_at'        => 'datetime',
        'created_at'        => 'datetime',
    ];

    protected $appends = [
        'available_seats',
        'capacity_status',
        'is_full',
    ];

    public function getAvailableSeatsAttribute(): int
    {
        return max(0, (int) $this->capacity - (int) $this->occupied_seats);
    }

    public function getCapacityStatusAttribute(): string
    {
        return (int) $this->occupied_seats >= (int) $this->capacity ? 'Penuh' : 'Tersedia';
    }

    public function getIsFullAttribute(): bool
    {
        return (int) $this->occupied_seats >= (int) $this->capacity;
    }

    public function telemetryAuditLogs(): HasMany
    {
        return $this->hasMany(TelemetryAuditLog::class, 'vehicle_code', 'vehicle_code');
    }
}
