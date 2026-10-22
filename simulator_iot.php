<?php

date_default_timezone_set('Asia/Jakarta');

$onceMode = in_array('--once', $argv, true);
$customUrl = null;
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--') !== 0) {
        $customUrl = $arg;
        break;
    }
}

$apiUrl = $customUrl ?: (getenv('SHUTTLE_API_URL') ?: 'http://127.0.0.1:8000/api/v1/telemetry');
$apiKey = getenv('SHUTTLE_API_KEY') ?: 'kampus-iot-secret-key-2026';
$intervalSeconds = 2;

$loopWaypoints = [
    ['lat' => -6.2365000, 'lng' => 106.5218000, 'label' => 'Halte Citra Raya (Bundaran 1)', 'is_stop' => true, 'is_terminal' => true],
    ['lat' => -6.2330000, 'lng' => 106.5208000, 'label' => 'Jl. Boulevard Citra Raya Utama', 'is_stop' => false],
    ['lat' => -6.2297500, 'lng' => 106.5198500, 'label' => 'Gerbang Citra Raya Cikupa (Jl. Raya Serang)', 'is_stop' => false],
    ['lat' => -6.2283000, 'lng' => 106.5268000, 'label' => 'Jl. Raya Serang Km. 13.5 (Bunder Cikupa)', 'is_stop' => false],
    ['lat' => -6.2268000, 'lng' => 106.5335000, 'label' => 'Halte Cikupa (Jl. Raya Serang Km. 13)', 'is_stop' => true, 'is_terminal' => false],
    ['lat' => -6.2254000, 'lng' => 106.5410000, 'label' => 'Jl. Raya Serang Km. 12 (Sukadamai)', 'is_stop' => false],
    ['lat' => -6.2241000, 'lng' => 106.5482000, 'label' => 'Jl. Raya Serang Km. 11 (Kadu Curug)', 'is_stop' => false],
    ['lat' => -6.2229000, 'lng' => 106.5550000, 'label' => 'Jl. Raya Serang Km. 10.3 (Menjelang Kampus)', 'is_stop' => false],
    ['lat' => -6.2222578, 'lng' => 106.5603413, 'label' => 'Halte Kampus UNIPI (Gerbang Utama)', 'is_stop' => true, 'is_terminal' => true],
    ['lat' => -6.2217000, 'lng' => 106.5638000, 'label' => 'Jl. Raya Serang (Bitung Jaya)', 'is_stop' => false],
    ['lat' => -6.2212000, 'lng' => 106.5668000, 'label' => 'Halte Simpang Bitung', 'is_stop' => true, 'is_terminal' => false],
    ['lat' => -6.2265000, 'lng' => 106.5652000, 'label' => 'Jalur Lingkar Bitung - Curug (Kadu)', 'is_stop' => false],
    ['lat' => -6.2315000, 'lng' => 106.5595000, 'label' => 'Jalur Kadu - Curug Kulon (Arah Balik Citra)', 'is_stop' => false],
    ['lat' => -6.2348000, 'lng' => 106.5495000, 'label' => 'Jalur Penghubung Curug - Cikupa Selatan', 'is_stop' => false],
    ['lat' => -6.2368000, 'lng' => 106.5365000, 'label' => 'Jl. Boulevard Citra Raya Timur (Arah Bundaran 1)', 'is_stop' => false],
];

$routes = [
    'SHT-01' => $loopWaypoints,
    'SHT-02' => $loopWaypoints,
];

$shuttleStates = [
    'SHT-01' => [
        'waypoint_index'     => 0,
        'dwell_ticks'        => 0,
        'current_passengers' => 4,
        'max_capacity'       => 12,
    ],
    'SHT-02' => [
        'waypoint_index'     => 8,
        'dwell_ticks'        => 0,
        'current_passengers' => 6,
        'max_capacity'       => 12,
    ],
];

echo "=================================================================\n";
echo "  SIMULATOR IoT GPS - RUTE MELINGKAR CITRA -> CIKUPA -> BITUNG\n";
echo "=================================================================\n";
echo "Target Endpoint : {$apiUrl}\n";
echo "X-API-KEY       : {$apiKey}\n";
echo "Interval        : {$intervalSeconds} detik\n";
echo "Tekan Ctrl+C untuk menghentikan simulator.\n";
echo "-----------------------------------------------------------------\n\n";

while (true) {
    foreach ($shuttleStates as $shuttleId => &$state) {
        $waypoints = $routes[$shuttleId];
        $currentPoint = $waypoints[$state['waypoint_index']];
        $maxCapacity = (int) $state['max_capacity'];

        $isAtStop = !empty($currentPoint['is_stop']);

        if ($isAtStop && $state['dwell_ticks'] === 0) {

            $state['dwell_ticks'] = 2;
            if (!empty($currentPoint['is_terminal'])) {
                $state['current_passengers'] = mt_rand(0, min(5, $maxCapacity));
            } else {
                $delta = mt_rand(-3, 4);
                $state['current_passengers'] = $state['current_passengers'] + $delta;
            }
        }

        $speed = ($state['dwell_ticks'] > 0)
            ? 0.0
            : round(mt_rand(280, 400) / 10, 1);

        $state['current_passengers'] = max(0, min($maxCapacity, (int) $state['current_passengers']));
        $capacityStatus = $state['current_passengers'] >= $maxCapacity ? 'Penuh' : 'Tersedia';

        $payload = [
            'shuttle_id'         => $shuttleId,
            'vehicle_code'       => $shuttleId,
            'latitude'           => $currentPoint['lat'],
            'longitude'          => $currentPoint['lng'],
            'speed'              => $speed,
            'current_passengers' => $state['current_passengers'],
            'occupied_seats'     => $state['current_passengers'],
            'max_capacity'       => $maxCapacity,
        ];

        $jsonData = json_encode($payload);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-KEY: ' . $apiKey,
            ],
            CURLOPT_TIMEOUT        => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $timeNow = date('H:i:s');
        if ($curlError) {
            echo "[{$timeNow}] {$shuttleId} -> ERROR cURL: {$curlError}\n";
        } else {
            $statusSuffix = $httpCode === 200 ? 'OK' : 'ERROR';
            echo sprintf(
                "[%s] %s -> Lat: %.5f, Lng: %.5f | Muatan: %d/%d Penumpang (%s) | Speed: %.1f km/h | HTTP %d %s (%s)\n",
                $timeNow,
                $shuttleId,
                $currentPoint['lat'],
                $currentPoint['lng'],
                $state['current_passengers'],
                $maxCapacity,
                $capacityStatus,
                $speed,
                $httpCode,
                $statusSuffix,
                $currentPoint['label']
            );
        }

        if ($state['dwell_ticks'] > 0) {
            $state['dwell_ticks']--;
            if ($state['dwell_ticks'] === 0) {
                $state['waypoint_index'] = ($state['waypoint_index'] + 1) % count($waypoints);
            }
        } else {
            $state['waypoint_index'] = ($state['waypoint_index'] + 1) % count($waypoints);
        }
    }
    unset($state);

    if ($onceMode) {
        break;
    }

    sleep($intervalSeconds);
}
