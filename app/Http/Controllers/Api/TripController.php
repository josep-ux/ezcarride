<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Events\RideRequested;
use App\Jobs\DispatchDriverJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class TripController extends Controller
{
    public function index(Request $request)
    {
        $trips = $request->user()->trips()->get();
        return response()->json($trips, 200);
    }

    public function estimateTrip(Request $request) 
    {

     // 1. Enforce validation on coordinates
    $request->validate([
        'pickup_latitude' => 'required|numeric',
        'pickup_longitude' => 'required|numeric',
        'dropoff_latitude' => 'required|numeric',
        'dropoff_longitude' => 'required|numeric',
    ]);

    try {
        $lat1 = doubleval($request->pickup_latitude);
        $lon1 = doubleval($request->pickup_longitude);
        $lat2 = doubleval($request->dropoff_latitude);
        $lon2 = doubleval($request->dropoff_longitude);

        // 2. The Haversine Formula (Calculates straight-line distance over Earth's surface)
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $straightLineDistance = $earthRadius * $c;

        // 3. Add a driving route factor (roads are roughly 20-30% longer than straight lines)
        $distanceInKm = $straightLineDistance * 1.25;

        // 4. Calculate Ride Price: Base Fare ($5.00) + ($1.50 per KM)
        $estimatedPrice = 5.00 + ($distanceInKm * 1.50); 

        return response()->json([
            'status' => 'success',
            'calculation_engine' => 'Local Server Matrix',
            'distance' => round($distanceInKm, 2),
            'price' => round($estimatedPrice, 2)
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
    }


    public function show(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip, 200);
    }
    
    public function getTripStatus(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json(['status' => $trip->status], 200);
    }
    public function updateTripStatus(Request $request, $id)
    {
        $trip = $request->user()->trips()->findOrFail($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'status' => 'required|string|in:pending,accepted,in_progress,completed,cancelled',
        ]);
        $trip->status = $request->status;
        $trip->save();
        return response()->json(['message' => 'Trip status updated', 'status' => $trip->status], 200);
    }
    public function getTripLocation(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json([
            'pickup_latitude' => $trip->pickup_latitude,
            'pickup_longitude' => $trip->pickup_longitude,
            'dropoff_latitude' => $trip->dropoff_latitude,
            'dropoff_longitude' => $trip->dropoff_longitude,
        ], 200);
    }
    public function updateTripLocation(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'pickup_latitude' => 'required|numeric',
            'pickup_longitude' => 'required|numeric',
            'dropoff_latitude' => 'required|numeric',
            'dropoff_longitude' => 'required|numeric',
        ]);
        $trip->update($request->only(['pickup_latitude', 'pickup_longitude', 'dropoff_latitude', 'dropoff_longitude']));
        return response()->json(['message' => 'Trip location updated'], 200);
    }

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
    public function getTripDriver(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->driver, 200);
    }
    public function getTripRider(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->rider, 200);
    }
    public function updateTripDriver(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);
        $trip->driver_id = $request->driver_id;
        $trip->save();
        return response()->json(['message' => 'Trip driver updated'], 200);
    }
    public function updateTripRider(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'rider_id' => 'required|exists:users,id',
        ]);
        $trip->rider_id = $request->rider_id;
        $trip->save();
        return response()->json(['message' => 'Trip rider updated'], 200);
    }
    public function updateTripFare(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'fare' => 'required|numeric|min:0',
        ]);
        $trip->fare = $request->fare;
        $trip->save();
        return response()->json(['message' => 'Trip fare updated'], 200);
    }
    public function getTripPayment(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->payment, 200);
    }
    public function updateTripPayment(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'payment' => 'required|numeric|min:0',
        ]);
        $trip->payment = $request->payment;
        $trip->save();
        return response()->json(['message' => 'Trip payment updated'], 200);
    }
    public function getTripRating(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->rating, 200);
    }
    public function updateTripRating(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);

        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
        ]);
        $trip->rating = $request->rating;
        $trip->save();
        return response()->json(['message' => 'Trip rating updated'], 200);
    }
    public function getTripFeedback(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->feedback, 200);
    }
    public function updateTripFeedback(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'feedback' => 'required|string|max:255',
        ]);
        $trip->feedback = $request->feedback;
        $trip->save();
        return response()->json(['message' => 'Trip feedback updated'], 200);
    }
    public function getTripReceipt(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->receipt, 200);
    }
    public function updateTripReceipt(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'receipt' => 'required|string|max:255',
        ]);
        $trip->receipt = $request->receipt;
        $trip->save();
        return response()->json(['message' => 'Trip receipt updated'], 200);
    }
    public function getTripInvoice(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->invoice, 200);
    }
    public function updateTripInvoice(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'invoice' => 'required|string|max:255',
        ]);
        $trip->invoice = $request->invoice;
        $trip->save();
        return response()->json(['message' => 'Trip invoice updated'], 200);
    }
    public function getTripRoute(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->route, 200);
    }
    public function updateTripRoute(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'route' => 'required|string|max:255',
        ]);
        $trip->route = $request->route;
        $trip->save();
        return response()->json(['message' => 'Trip route updated'], 200);
    }
    public function getTripEta(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->eta, 200);
    }
    public function updateTripEta(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'eta' => 'required|date',
        ]);
        $trip->eta = $request->eta;
        $trip->save();
        return response()->json(['message' => 'Trip ETA updated'], 200);

        }
        public function getTripDistance(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->distance, 200);
    }
    public function updateTripDistance(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'distance' => 'required|numeric|min:0',
        ]);
        $trip->distance = $request->distance;
        $trip->save();
        return response()->json(['message' => 'Trip distance updated'], 200);
    }
    public function getTripDuration(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->duration, 200);
    }
    public function updateTripDuration(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'duration' => 'required|numeric|min:0',
        ]);
        $trip->duration = $request->duration;
        $trip->save();
        return response()->json(['message' => 'Trip duration updated'], 200);
    }
    public function getTripStatusHistory(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->status_history, 200);
    }
    public function updateTripStatusHistory(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'status_history' => 'required|array',
            'status_history.*' => 'required|string|in:requested,accepted,in_progress,completed,cancelled',
        ]);
        $trip->status_history = $request->status_history;
        $trip->save();
        return response()->json(['message' => 'Trip status history updated'], 200);
        }
    public function getTripLocationHistory(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->location_history, 200);
   }
   public function updateTripLocationHistory(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'location_history' => 'required|array',
            'location_history.*.latitude' => 'required|numeric',
            'location_history.*.longitude' => 'required|numeric',
        ]);
        $trip->location_history = $request->location_history;
        $trip->save();
        return response()->json(['message' => 'Trip location history updated'], 200);
    }
    public function getTripDriverHistory(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->driver_history, 200);
   }
   public function updateTripDriverHistory(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
}
        $request->validate([
            'driver_history' => 'required|array',
            'driver_history.*.driver_id' => 'required|exists:users,id',
            'driver_history.*.timestamp' => 'required|date',
        ]);
        $trip->driver_history = $request->driver_history;
        $trip->save();
        return response()->json(['message' => 'Trip driver history updated'], 200);
    }
    public function updateTripRiderHistory(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        $request->validate([
            'rider_history' => 'required|array',
            'rider_history.*.rider_id' => 'required|exists:users,id',
            'rider_history.*.timestamp' => 'required|date',
        ]);
        $trip->rider_history = $request->rider_history;
        $trip->save();
        return response()->json(['message' => 'Trip rider history updated'], 200);
    }
    public function getTripRiderHistory(Request $request, $id)
    {
        $trip = $request->user()->trips()->find($id);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }
        return response()->json($trip->rider_history, 200);
    }

}

