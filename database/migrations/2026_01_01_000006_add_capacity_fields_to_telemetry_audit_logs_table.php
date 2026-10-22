<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('telemetry_audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('telemetry_audit_logs', 'max_capacity')) {
                $table->integer('max_capacity')->default(12)->after('occupied_seats');
            }
            if (! Schema::hasColumn('telemetry_audit_logs', 'capacity_status')) {
                $table->string('capacity_status', 20)->default('Tersedia')->after('max_capacity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('telemetry_audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('telemetry_audit_logs', 'capacity_status')) {
                $table->dropColumn('capacity_status');
            }
            if (Schema::hasColumn('telemetry_audit_logs', 'max_capacity')) {
                $table->dropColumn('max_capacity');
            }
        });
    }
};
