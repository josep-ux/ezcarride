<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Events\RideRequested;
use App\Jobs\DispatchDriverJob;

class TripController extends Controller
{
    public function requestRide(Request $request)
    {
       $validated = $request->validate([
            'pickup_latitude'  => 'required|numeric',
            'pickup_longitude'  => 'required|numeric',
            'dropoff_latitude' => 'required',
            'dropoff_longitude' => 'required|numeric',
            'fare' => 'required|numeric|min:0',
        ]);

        // 1. Persist the booking state
        $trip = $request->user()->trips()->create($validated);

        // 2. Offload matching logic to a background queue worker
        DispatchDriverJob::dispatch($trip);

        return response()->json([
            'message' => 'Searching for nearby drivers...',
            'trip' => $trip
        ], 201);
    }
}
