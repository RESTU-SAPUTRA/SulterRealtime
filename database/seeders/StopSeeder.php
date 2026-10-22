<?php

namespace Database\Seeders;

use App\Models\Stop;
use Illuminate\Database\Seeder;

class StopSeeder extends Seeder
{

    public function run(): void
    {
        $stops = [
            [
                'name'        => 'Halte Citra Raya (Bundaran 1)',
                'latitude'    => -6.2365000,
                'longitude'   => 106.5218000,
                'order_index' => 1,
            ],
            [
                'name'        => 'Halte Cikupa (Jl. Raya Serang Km. 13)',
                'latitude'    => -6.2268000,
                'longitude'   => 106.5335000,
                'order_index' => 2,
            ],
            [
                'name'        => 'Halte Kampus UNIPI (Gerbang Utama)',
                'latitude'    => -6.2222578,
                'longitude'   => 106.5603413,
                'order_index' => 3,
            ],
            [
                'name'        => 'Halte Simpang Bitung',
                'latitude'    => -6.2212000,
                'longitude'   => 106.5668000,
                'order_index' => 4,
            ],
        ];

        Stop::query()->delete();

        foreach ($stops as $stopData) {
            Stop::updateOrCreate(
                ['name' => $stopData['name']],
                $stopData
            );
        }
    }
}
