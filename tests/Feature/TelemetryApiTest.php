<?php

namespace Tests\Feature;

use App\Jobs\ProcessTelemetryAuditLog;
use App\Models\Shuttle;
use App\Models\Stop;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelemetryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_endpoint_rejects_request_without_api_key(): void
    {
        $payload = [
            'vehicle_code'   => 'SHT-01',
            'latitude'       => -6.201200,
            'longitude'      => 106.818500,
            'speed'          => 28.5,
            'occupied_seats' => 7,
        ];

        $responseWithoutKey = $this->postJson('/api/v1/telemetry', $payload);
        $responseWithoutKey->assertStatus(401)
            ->assertJsonPath('status', 'error');

        $responseInvalidKey = $this->withHeaders([
            'X-API-KEY' => 'invalid-secret-key',
        ])->postJson('/api/v1/telemetry', $payload);
        $responseInvalidKey->assertStatus(401);
    }

    public function test_telemetry_endpoint_accepts_valid_payload_and_updates_shuttle_state(): void
    {
        $this->seed(DatabaseSeeder::class);

        $payload = [
            'vehicle_code'   => 'SHT-01',
            'latitude'       => -6.201500,
            'longitude'      => 106.817500,
            'speed'          => 32.5,
            'occupied_seats' => 8,
        ];

        $response = $this->withHeaders([
            'X-API-KEY' => 'kampus-iot-secret-key-2026',
        ])->postJson('/api/v1/telemetry', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Telemetry received and queued for audit');

        $this->assertDatabaseHas('shuttles', [
            'vehicle_code'   => 'SHT-01',
            'occupied_seats' => 8,
        ]);

        $readResponse = $this->getJson('/api/v1/shuttles/latest');
        $readResponse->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.available_seats', 4)
            ->assertJsonPath('0.capacity_status', 'Tersedia')
            ->assertJsonPath('0.occupancy_label', '8/12 Penumpang');

        $fullPayload = [
            'shuttle_id'         => 'SHT-01',
            'latitude'           => -6.224100,
            'longitude'          => 106.548200,
            'current_passengers' => 15,
            'max_capacity'       => 15,
        ];

        $fullResponse = $this->withHeaders([
            'X-API-KEY' => 'kampus-iot-secret-key-2026',
        ])->postJson('/api/v1/telemetry', $fullPayload);

        $fullResponse->assertStatus(200)
            ->assertJsonPath('available_seats', 0)
            ->assertJsonPath('capacity_status', 'Penuh');

        $exceedPayload = [
            'shuttle_id'         => 'SHT-01',
            'latitude'           => -6.224100,
            'longitude'          => 106.548200,
            'current_passengers' => 18,
            'max_capacity'       => 15,
        ];

        $this->withHeaders([
            'X-API-KEY' => 'kampus-iot-secret-key-2026',
        ])->postJson('/api/v1/telemetry', $exceedPayload)->assertStatus(422);
    }

    public function test_audit_log_job_dispatched_successfully(): void
    {
        Queue::fake();

        $payload = [
            'vehicle_code'   => 'SHT-01',
            'latitude'       => -6.203000,
            'longitude'      => 106.819000,
            'speed'          => 27.0,
            'occupied_seats' => 6,
        ];

        $response = $this->withHeaders([
            'X-API-KEY' => 'kampus-iot-secret-key-2026',
        ])->postJson('/api/v1/telemetry', $payload);

        $response->assertStatus(200);

        Queue::assertPushed(ProcessTelemetryAuditLog::class, function (ProcessTelemetryAuditLog $job) {
            return $job->data['vehicle_code'] === 'SHT-01'
                && (int) $job->data['occupied_seats'] === 6;
        });
    }

    public function test_web_dashboards_and_audit_trail_render_properly(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Estimasi Kedatangan (ETA Halte)');

        $this->get('/audit-trail')->assertRedirect('/login');
        $this->get('/admin/audit-trail')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');

        $loginResponse = $this->post('/login', [
            'email'    => 'operator@campus.ac.id',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect('/admin');
        $this->assertAuthenticated();

        $this->get('/admin')
            ->assertStatus(200)
            ->assertSee('Monitoring Operasional Armada')
            ->assertSee('Operator Shuttle Kampus');

        $this->get('/admin/audit-trail')
            ->assertStatus(200)
            ->assertSee('Tabel Audit Trail Lintas Sistem')
            ->assertSee('Operator Shuttle Kampus');

        $addShuttleResponse = $this->post('/admin/shuttles', [
            'vehicle_code' => 'SHT-03',
            'plate_number' => 'B 1053 KMP',
            'driver_name'  => 'Pak Hendra Wijaya',
            'capacity'     => 14,
            'status'       => 'active',
        ]);
        $addShuttleResponse->assertRedirect('/admin');
        $this->assertDatabaseHas('shuttles', [
            'vehicle_code' => 'SHT-03',
            'driver_name'  => 'Pak Hendra Wijaya',
            'status'       => 'active',
        ]);

        $newShuttle = Shuttle::where('vehicle_code', 'SHT-03')->firstOrFail();
        $this->put("/admin/shuttles/{$newShuttle->id}", [
            'vehicle_code' => 'SHT-03',
            'plate_number' => 'B 1099 KMP',
            'driver_name'  => 'Pak Hendra Wijaya (Updated)',
            'capacity'     => 10,
            'status'       => 'maintenance',
        ])->assertRedirect('/admin');
        $this->assertDatabaseHas('shuttles', [
            'id'           => $newShuttle->id,
            'plate_number' => 'B 1099 KMP',
            'driver_name'  => 'Pak Hendra Wijaya (Updated)',
            'capacity'     => 10,
            'status'       => 'maintenance',
        ]);

        $this->delete("/admin/shuttles/{$newShuttle->id}")->assertRedirect('/admin');
        $this->assertDatabaseMissing('shuttles', [
            'vehicle_code' => 'SHT-03',
        ]);

        $addStopResponse = $this->post('/admin/stops', [
            'name'        => 'Halte Pasar Curug',
            'latitude'    => -6.2250000,
            'longitude'   => 106.5450000,
            'order_index' => 5,
        ]);
        $addStopResponse->assertRedirect('/admin');
        $this->assertDatabaseHas('stops', [
            'name'        => 'Halte Pasar Curug',
            'order_index' => 5,
        ]);

        $newStop = Stop::where('name', 'Halte Pasar Curug')->firstOrFail();
        $this->put("/admin/stops/{$newStop->id}", [
            'name'        => 'Halte Kadu Jaya',
            'latitude'    => -6.2335570,
            'longitude'   => 106.5535650,
            'order_index' => 5,
        ])->assertRedirect('/admin');
        $this->assertDatabaseHas('stops', [
            'id'          => $newStop->id,
            'name'        => 'Halte Kadu Jaya',
            'order_index' => 5,
        ]);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
