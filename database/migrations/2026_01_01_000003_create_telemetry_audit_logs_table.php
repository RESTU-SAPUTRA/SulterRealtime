<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('telemetry_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_code', 20);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('speed')->default(0);
            $table->integer('occupied_seats')->default(0);
            $table->integer('max_capacity')->default(12);
            $table->string('capacity_status', 20)->default('Tersedia');
            $table->string('source_system', 50)->default('GPS_SIMULATOR_IOT');
            $table->string('payload_hash', 64)->nullable();
            $table->timestamp('logged_at')->useCurrent();

            $table->index(['vehicle_code', 'logged_at'], 'idx_vehicle_logged');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_audit_logs');
    }
};
