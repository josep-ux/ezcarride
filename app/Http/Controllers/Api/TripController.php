<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Events\RideRequested;
use App\Jobs\DispatchDriverJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Ride;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $trips = $request->user()->trips()->get();
        return response()->json($trips, 200);
    }

    public function estimateTrip(Request $request) 
{
    // 1. Enforce strict parameter verification matching your schema requirements
    $request->validate([
        'pickup_latitude'  => 'required|numeric',
        'pickup_longitude' => 'required|numeric',
        'dropoff_latitude'  => 'required|numeric',
        'dropoff_longitude' => 'required|numeric',
    ]);

    try {
        $lat1 = doubleval($request->pickup_latitude);
        $lon1 = doubleval($request->pickup_longitude);
        $lat2 = doubleval($request->dropoff_latitude);
        $lon2 = doubleval($request->dropoff_longitude);

        // 2. Compute Haversine straight-line distance
        $earthRadius = 6371; // Earth radius in kilometers
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $straightLineDistance = $earthRadius * $c;

        // 3. Convert metrics to match your "rides" table requirements
        $distanceInKm = $straightLineDistance * 1.25; // Driving routing factor adjustment
        $distanceInMeters = (int) round($distanceInKm * 1000);

        // 4. Calculate estimated duration (assuming average city speed of 30 km/h)
        // Formula: Time = Distance / Speed -> Hours to seconds mapping
        $averageSpeedKmh = 30;
        $durationInHours = $distanceInKm / $averageSpeedKmh;
        $durationInSeconds = (int) round($durationInHours * 3600);

        // 5. Dynamic Metadata Rules (Matches your table fields)
        $surgeMultiplier = 1.00; // You can write custom rush-hour condition overrides here
        
        // Ride Metric Cost Model: Base Fare ($5.00) + ($1.50 per KM) * Surge
        $baseFare = 5.00;
        $perKmRate = 1.50;
        $calculatedFare = ($baseFare + ($distanceInKm * $perKmRate)) * $surgeMultiplier;

        // 6. Return keys formatted exactly like your schema column definitions
        return response()->json([
            'status' => 'success',
            'calculation_engine' => 'Local Server Matrix',
            
            // Map parameters to match input field validations
            'pickup_lat' => $lat1,
            'pickup_long' => $lon1,
            'dropoff_lat' => $lat2,
            'dropoff_long' => $lon2,
            
            // Map parameters to match your metadata column keys
            'estimated_distance_meters' => $distanceInMeters,
            'estimated_duration_seconds' => $durationInSeconds,
            'surge_multiplier' => round($surgeMultiplier, 2),
            
            // Final calculated price matching your currency float layouts
            'fare' => round($calculatedFare, 2),
            'formatted_distance_km' => round($distanceInKm, 2)
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

   public function store(Request $request)
{
    // 1. Validate the final booking submission payload
    $request->validate([
        'pickup_latitude'            => 'required|numeric',
        'pickup_longitude'           => 'required|numeric',
        'dropoff_latitude'           => 'required|numeric',
        'dropoff_longitude'          => 'required|numeric',
        'pickup_address'             => 'required|string|max:255',
        'dropoff_address'            => 'required|string|max:255',
        'estimated_distance_meters'  => 'required|integer',
        'estimated_duration_seconds' => 'required|integer',
        'surge_multiplier'           => 'required|numeric',
        'fare'                       => 'required|numeric',
    ]);

    try {
        // 2. Insert the records mapping exactly to your 'rides' schema layout
        $ride = Ride::create([
            'passenger_id'               => $request->user()->id, // Automatically links the authenticated user
            'driver_id'                  => null,                // Stays empty until a driver accepts the match
            'status'                     => 'pending',           // Initial default lifecycle state
            
            // Map incoming coordinates to matching database columns
            'pickup_lat'                 => $request->pickup_latitude,
            'pickup_long'                => $request->pickup_longitude,
            'dropoff_lat'                => $request->dropoff_latitude,
            'dropoff_long'               => $request->dropoff_longitude,
            
            // Map text addresses
            'pickup_address'             => $request->pickup_address,
            'dropoff_address'            => $request->dropoff_address,
            
            // Log core operational calculation metrics
            'surge_multiplier'           => $request->surge_multiplier,
            'estimated_duration_seconds' => $request->estimated_duration_seconds,
            'estimated_distance_meters'  => $request->estimated_distance_meters,
            'fare'                       => $request->fare,
        ]);

        // 3. Return the created ride record so your frontend app can listen for driver matches
        return response()->json([
            'status'  => 'success',
            'message' => 'Ride booking requested successfully.',
            'ride'    => $ride
        ], 21);

    } catch (\Throwable $e) {
        Log::error('Ride Creation Database Crash', ['error' => $e->getMessage()]);
        
        return response()->json([
            'status'  => 'error',
            'message' => 'Could not save the booking request to the database.'
        ], 500);
    }
}


   
   
}

