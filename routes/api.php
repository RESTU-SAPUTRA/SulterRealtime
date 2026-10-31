<?php

use App\Http\Controllers\Api\TelemetryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/telemetry', [TelemetryController::class, 'store'])
        ->middleware('verify.api.key')
        ->name('api.v1.telemetry.store');

    Route::get('/shuttles/latest', [TelemetryController::class, 'getLatestShuttles'])
        ->name('api.v1.shuttles.latest');

    Route::get('/audit-logs/latest', [TelemetryController::class, 'getLatestAuditLogs'])
        ->name('api.v1.audit-logs.latest');
});

Route::post('/telemetry', [TelemetryController::class, 'store'])
    ->middleware('verify.api.key')
    ->name('api.telemetry.record');

Route::get('/shuttles/latest', [TelemetryController::class, 'getLatestShuttles'])
    ->name('api.shuttles.latest');

Route::get('/audit-logs/latest', [TelemetryController::class, 'getLatestAuditLogs'])
    ->name('api.audit-logs.latest');
