<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelemetryAuditLog extends Model
{
    use HasFactory;

    protected $table = 'telemetry_audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'vehicle_code',
        'latitude',
        'longitude',
        'speed',
        'occupied_seats',
        'max_capacity',
        'capacity_status',
        'source_system',
        'payload_hash',
        'logged_at',
    ];

    protected $casts = [
        'latitude'       => 'float',
        'longitude'      => 'float',
        'speed'          => 'float',
        'occupied_seats' => 'integer',
        'max_capacity'   => 'integer',
        'logged_at'      => 'datetime',
    ];

    public function shuttle(): BelongsTo
    {
        return $this->belongsTo(Shuttle::class, 'vehicle_code', 'vehicle_code');
    }
}
