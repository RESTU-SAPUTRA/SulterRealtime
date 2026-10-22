<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('shuttles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_code', 20)->unique();
            $table->string('driver_name', 100);
            $table->string('plate_number', 20)->default('B 1000 KMP');
            $table->integer('capacity')->default(12);
            $table->integer('occupied_seats')->default(0);
            $table->decimal('current_lat', 10, 7)->nullable();
            $table->decimal('current_lng', 10, 7)->nullable();
            $table->float('speed')->default(0);
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->timestamp('last_telemetry_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shuttles');
    }
};
