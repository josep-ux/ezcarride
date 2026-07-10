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
use Carbon\Carbon;

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
    // 1. Validate only the raw inputs sent from your frontend app inputs
    $request->validate([
        'pickup_latitude'  => 'required|numeric',
        'pickup_longitude' => 'required|numeric',
        'dropoff_latitude'  => 'required|numeric',
        'dropoff_longitude' => 'required|numeric',
        'pickup_address'   => 'required|string|max:255',
        'dropoff_address'  => 'required|string|max:255',
    ]);

    try {
        $lat1 = doubleval($request->pickup_latitude);
        $lon1 = doubleval($request->pickup_longitude);
        $lat2 = doubleval($request->dropoff_latitude);
        $lon2 = doubleval($request->dropoff_longitude);

        // 2. Run the secure local Haversine calculations directly inside the backend
        $earthRadius = 6371; 
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $straightLineDistance = $earthRadius * $c;

        $distanceInKm = $straightLineDistance * 1.25; 
        $distanceInMeters = (int) round($distanceInKm * 1000);

        $averageSpeedKmh = 30;
        $durationInSeconds = (int) round(($distanceInKm / $averageSpeedKmh) * 3600);

        $surgeMultiplier = 1.00; // Hardcoded default placeholder
        $calculatedFare = (5.00 + ($distanceInKm * 1.50)) * $surgeMultiplier;

        // 3. Save the calculated metrics straight into the database table rows
        $ride = \App\Models\Ride::create([
            'passenger_id'               => $request->user()->id,
            'driver_id'                  => null,
            'status'                     => 'pending',
            'pickup_lat'                 => $lat1,
            'pickup_long'                => $lon1,
            'dropoff_lat'                => $lat2,
            'dropoff_long'               => $lon2,
            'pickup_address'             => $request->pickup_address,
            'dropoff_address'            => $request->dropoff_address,
            'surge_multiplier'           => round($surgeMultiplier, 2),
            'estimated_duration_seconds' => $durationInSeconds,
            'estimated_distance_meters'  => $distanceInMeters,
            'fare'                       => round($calculatedFare, 2),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Ride booking finalized and requested successfully!',
            'ride'    => $ride
        ], 201);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Internal server error processing booking write.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    /**
 * Scan for unassigned pending rides within a 10km radius of the driver.
 */
public function availableRides(Request $request)
{
    $request->validate([
        'driver_latitude'  => 'required|numeric',
        'driver_longitude' => 'required|numeric',
    ]);

    $driverLat = doubleval($request->driver_latitude);
    $driverLng = doubleval($request->driver_longitude);
    $searchRadiusKm = 10.0; // Distance filter bounds

    try {
        // Fetch only unassigned pending rides created recently
        $pendingRides = Ride::where('status', 'pending')
            ->whereNull('driver_id')
            ->where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->get();

        $availableForDriver = [];

        foreach ($pendingRides as $ride) {
            // Compute straight line distance from driver to pickup spot using local Haversine
            $earthRadius = 6371;
            $dLat = deg2rad(doubleval($ride->pickup_lat) - $driverLat);
            $dLon = deg2rad(doubleval($ride->pickup_long) - $driverLng);

            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($driverLat)) * cos(deg2rad(doubleval($ride->pickup_lat))) *
                 sin($dLon / 2) * sin($dLon / 2);
                 
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distanceToPickupKm = $earthRadius * $c;

            // If the pickup location is within the radius, add it to the driver's list
            if ($distanceToPickupKm <= $searchRadiusKm) {
                $ride->distance_to_driver_km = round($distanceToPickupKm, 2);
                $availableForDriver[] = $ride;
            }
        }

        return response()->json([
            'status' => 'success',
            'count'  => count($availableForDriver),
            'rides'  => $availableForDriver
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to fetch available rides.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

/**
 * Allow a driver to accept a pending ride.
 */
public function acceptRide(Request $request, $id)
{
    try {
        // Find the ride record
        $ride = Ride::findOrFail($id);

        // Fail-safe: Prevent another driver from taking it if it's already assigned
        if ($ride->status !== 'pending' || !is_null($ride->driver_id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This ride has already been accepted by another driver.'
            ], 422);
        }

        // Assign the driver and update the lifecycle milestones
        $ride->update([
            'driver_id'   => $request->user()->id, // Active authenticated driver ID
            'status'      => 'accepted',
            'accepted_at' => Carbon::now()
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'You have successfully accepted this ride request!',
            'ride'    => $ride
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Ride request not found.'
        ], 404);
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'An error occurred while accepting the ride.',
            'error'   => $e->getMessage()
        ], 500);
    }
}
}



