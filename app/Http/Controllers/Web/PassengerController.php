<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shuttle;
use App\Models\Stop;
use Illuminate\View\View;

class PassengerController extends Controller
{

    public function index(): View
    {
        $shuttles = Shuttle::where('status', 'active')
            ->orderBy('vehicle_code')
            ->get();

        $stops = Stop::orderBy('order_index')->get();

        return view('passenger.map', [
            'shuttles' => $shuttles,
            'stops'    => $stops,
        ]);
    }
}
