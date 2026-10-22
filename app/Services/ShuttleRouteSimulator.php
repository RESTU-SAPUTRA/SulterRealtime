<?php

namespace App\Services;

use App\Models\Shuttle;
use App\Models\Stop;
use App\Models\TelemetryAuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ShuttleRouteSimulator
{

    public static function corridorKeypoints(): array
    {
        return [

            ['lat' => -6.2365000, 'lng' => 106.5218000, 'label' => 'Halte Citra Raya (Bundaran 1)', 'is_stop' => true, 'is_terminal' => true],
            ['lat' => -6.2330000, 'lng' => 106.5208000, 'label' => 'Jl. Boulevard Citra Raya Utama'],
            ['lat' => -6.2297500, 'lng' => 106.5198500, 'label' => 'Gerbang Citra Raya Cikupa (Jl. Raya Serang)'],
            ['lat' => -6.2283000, 'lng' => 106.5268000, 'label' => 'Jl. Raya Serang Km. 13.5 (Bunder Cikupa)'],

            ['lat' => -6.2268000, 'lng' => 106.5335000, 'label' => 'Halte Cikupa (Jl. Raya Serang Km. 13)', 'is_stop' => true],
            ['lat' => -6.2254000, 'lng' => 106.5410000, 'label' => 'Jl. Raya Serang Km. 12 (Sukadamai)'],
            ['lat' => -6.2241000, 'lng' => 106.5482000, 'label' => 'Jl. Raya Serang Km. 11 (Kadu Curug)'],
            ['lat' => -6.2229000, 'lng' => 106.5550000, 'label' => 'Jl. Raya Serang Km. 10.3 (Menjelang Kampus)'],

            ['lat' => -6.2222578, 'lng' => 106.5603413, 'label' => 'Halte Kampus UNIPI (Gerbang Utama)', 'is_stop' => true, 'is_terminal' => true],
            ['lat' => -6.2217000, 'lng' => 106.5638000, 'label' => 'Jl. Raya Serang (Bitung Jaya)'],
            ['lat' => -6.2212000, 'lng' => 106.5668000, 'label' => 'Halte Simpang Bitung', 'is_stop' => true],

            ['lat' => -6.2265000, 'lng' => 106.5652000, 'label' => 'Jalur Lingkar Bitung - Curug (Kadu)'],
            ['lat' => -6.2315000, 'lng' => 106.5595000, 'label' => 'Jalur Kadu - Curug Kulon (Arah Balik Citra)'],
            ['lat' => -6.2348000, 'lng' => 106.5495000, 'label' => 'Jalur Penghubung Curug - Cikupa Selatan'],
            ['lat' => -6.2368000, 'lng' => 106.5365000, 'label' => 'Jl. Boulevard Citra Raya Timur (Arah Bundaran 1)'],
        ];
    }

    public static function loopWaypoints(): array
    {
        $keypoints = self::corridorKeypoints();
        $finePoints = [];
        $subSteps = 3;

        $count = count($keypoints);
        for ($i = 0; $i < $count; $i++) {
            $start = $keypoints[$i];
            $end = $keypoints[($i + 1) % $count];

            for ($s = 0; $s < $subSteps; $s++) {
                $ratio = $s / $subSteps;
                $finePoints[] = [
                    'lat'         => round($start['lat'] + ($end['lat'] - $start['lat']) * $ratio, 7),
                    'lng'         => round($start['lng'] + ($end['lng'] - $start['lng']) * $ratio, 7),
                    'label'       => $start['label'],
                    'is_stop'     => $s === 0 && !empty($start['is_stop']),
                    'is_terminal' => $s === 0 && !empty($start['is_terminal']),
                ];
            }
        }

        try {
            if (Schema::hasTable('stops')) {
                $dbStops = Stop::orderBy('order_index')->get();
                foreach ($dbStops as $stop) {
                    $stopLat = (float) $stop->latitude;
                    $stopLng = (float) $stop->longitude;
                    $bestIndex = null;
                    $bestDistSq = INF;

                    foreach ($finePoints as $idx => $fp) {
                        $dLat = $fp['lat'] - $stopLat;
                        $dLng = $fp['lng'] - $stopLng;
                        $distSq = ($dLat * $dLat) + ($dLng * $dLng);
                        if ($distSq < $bestDistSq) {
                            $bestDistSq = $distSq;
                            $bestIndex = $idx;
                        }
                    }

                    if ($bestIndex !== null && $bestDistSq <= 0.000225) {
                        $finePoints[$bestIndex]['lat'] = round($stopLat, 7);
                        $finePoints[$bestIndex]['lng'] = round($stopLng, 7);
                        $finePoints[$bestIndex]['label'] = $stop->name;
                        $finePoints[$bestIndex]['is_stop'] = true;
                    }
                }
            }
        } catch (\Throwable $e) {

        }

        return $finePoints;
    }

    public static function corridorWaypoints(): array
    {
        return self::loopWaypoints();
    }

    public static function calculateNextOccupancy(int $currentOccupied, int $capacity, array $point): int
    {
        $capacity = max(1, $capacity);

        if ($currentOccupied >= $capacity - 1 && empty($point['is_stop'])) {
            return mt_rand(3, max(4, (int) floor($capacity * 0.65)));
        }

        if (empty($point['is_stop'])) {
            return max(0, min($capacity, $currentOccupied));
        }

        if (!empty($point['is_terminal'])) {
            return mt_rand(0, min(4, $capacity));
        }

        $delta = mt_rand(-3, 3);
        $next = $currentOccupied + $delta;

        if ($next > $capacity - 2 && mt_rand(1, 100) <= 75) {
            $next = mt_rand(2, max(3, $capacity - 4));
        }

        return max(0, min($capacity, $next));
    }

    public static function tickIfDue(int $minIntervalSeconds = 2): void
    {
        $waypoints = self::loopWaypoints();
        $totalPoints = count($waypoints);
        if ($totalPoints === 0) {
            return;
        }

        $shuttles = Shuttle::where('status', 'active')
            ->orderBy('vehicle_code')
            ->get();

        $activeCount = max(1, $shuttles->count());
        $idealSpacing = max(6, (int) floor($totalPoints / $activeCount));
        $minSafeGap = max(4, (int) floor($idealSpacing / 2));
        $occupiedIndices = [];

        foreach ($shuttles as $idx => $shuttle) {
            $cacheKey = 'shuttle_sim_wp_' . $shuttle->vehicle_code;
            $dwellKey = 'shuttle_sim_dwell_' . $shuttle->vehicle_code;
            $defaultStartIndex = ($idx * $idealSpacing) % $totalPoints;
            $currentWpIndex = ((int) Cache::get($cacheKey, $defaultStartIndex)) % $totalPoints;
            $dwellRemaining = (int) Cache::get($dwellKey, 0);

            $lastUpdate = $shuttle->last_telemetry_at;
            $shouldAdvance = ($lastUpdate === null || $lastUpdate->diffInSeconds(now()) >= $minIntervalSeconds);

            $isDwellingAtStop = false;
            $justArrivedAtStop = false;

            if ($shouldAdvance && $dwellRemaining > 0) {

                $nextWpIndex = $currentWpIndex;
                $isDwellingAtStop = true;
                Cache::put($dwellKey, $dwellRemaining - 1, now()->addHours(12));
            } elseif ($shouldAdvance) {
                $nextWpIndex = ($currentWpIndex + 1) % $totalPoints;
            } else {
                $nextWpIndex = $currentWpIndex;
            }

            $isTooClose = false;
            foreach ($occupiedIndices as $usedIdx) {
                $diff = abs($nextWpIndex - $usedIdx);
                $circularDistance = min($diff, $totalPoints - $diff);
                if ($circularDistance < $minSafeGap) {
                    $isTooClose = true;
                    break;
                }
            }

            if ($isTooClose) {
                $nextWpIndex = ($occupiedIndices[count($occupiedIndices) - 1] + $idealSpacing) % $totalPoints;
                $shouldAdvance = true;
                $isDwellingAtStop = false;
                Cache::put($dwellKey, 0, now()->addHours(12));
            }

            $occupiedIndices[] = $nextWpIndex;

            if (! $shouldAdvance) {
                continue;
            }

            Cache::put($cacheKey, $nextWpIndex, now()->addHours(12));

            $point = $waypoints[$nextWpIndex];

            if (! $isDwellingAtStop && !empty($point['is_stop'])) {
                $isDwellingAtStop = true;
                $justArrivedAtStop = true;
                Cache::put($dwellKey, 2, now()->addHours(12));
            }

            $speed = $isDwellingAtStop
                ? 0.0
                : round(mt_rand(280, 380) / 10, 1);

            $capacity = max(1, (int) $shuttle->capacity);
            $occupied = $justArrivedAtStop
                ? self::calculateNextOccupancy((int) $shuttle->occupied_seats, $capacity, $point)
                : max(0, min($capacity, (int) $shuttle->occupied_seats));
            $capacityStatus = $occupied >= $capacity ? 'Penuh' : 'Tersedia';

            $shuttle->update([
                'current_lat'       => $point['lat'],
                'current_lng'       => $point['lng'],
                'speed'             => $speed,
                'occupied_seats'    => $occupied,
                'last_telemetry_at' => now(),
            ]);

            $payload = [
                'shuttle_id'         => $shuttle->vehicle_code,
                'vehicle_code'       => $shuttle->vehicle_code,
                'latitude'           => $point['lat'],
                'longitude'          => $point['lng'],
                'speed'              => $speed,
                'current_passengers' => $occupied,
                'occupied_seats'     => $occupied,
                'max_capacity'       => $capacity,
            ];

            TelemetryAuditLog::create([
                'vehicle_code'    => $shuttle->vehicle_code,
                'latitude'        => $point['lat'],
                'longitude'       => $point['lng'],
                'speed'           => $speed,
                'occupied_seats'  => $occupied,
                'max_capacity'    => $capacity,
                'capacity_status' => $capacityStatus,
                'source_system'   => 'REALTIME_SIMULATOR_CITRA_KAMPUS',
                'payload_hash'    => hash('sha256', json_encode($payload)),
                'logged_at'       => now(),
            ]);
        }
    }
}
