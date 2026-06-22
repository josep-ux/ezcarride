<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;

class RiderController extends Controller
{
    public function dashboard(){
       return response()->json([
            'success' => true,
        ], 200);
    }
    public function requestRide(Request $request){
        return response()->json([
            'success' => true,
        ], 200);
    }
    public function updateRideStatus(Request $request){
        return response()->json([
            'success' => true,
        ], 200);
    }
    public function updateLocation(Request $request){
        return response()->json([
            'success' => true,
        ], 200);
    }
    public function acceptRide(Request $request){
        return response()->json([
            'success' => true,
        ], 200);
    }
    public function completeRide(Request $request){
        return response()->json([
            'success' => true,
        ], 200);
    }
    public function cancelRide(Request $request){
        return response()->json([
            'success' => true,
        ], 200);
    }
    public function getRiderHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->rider_history,
        ], 200);
    }
    public function getRiderCurrentTrip($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->current_trip,
        ], 200);
    }
    public function getRiderUpcomingTrips($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->upcoming_trips,
        ], 200);
    }
    public function getRiderPastTrips($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->past_trips,
        ], 200);
    }
    public function getRiderCompletedTrips($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->completed_trips,
        ], 200);

    }
    public function getRiderOngoingTrips($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->ongoing_trips,
        ], 200);
    }
    public function getRiderScheduledTrips($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->scheduled_trips,
        ], 200);
    }
    public function getRiderNoShowTrips($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->no_show_trips,
        ], 200);
    }
    public function getRiderRatedTrips($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->rated_trips,
        ], 200);
    }
    public function getRiderUnratedTrips($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
        ], 200);
    }
    public function getRiderCancelledTrips($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->cancelled_trips,
        ], 200);
    }
    public function getRiderDriverHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->driver_history,
        ], 200);
    }
    public function getRiderFareHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->fare_history,
        ], 200);
    }
    public function getRiderPaymentHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->payment_history,
        ], 200);
    }
    public function getRiderRatingHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->rating_history,
        ], 200);
    }
    public function getRiderFeedbackHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->feedback_history,
        ], 200);
    }
    public function getRiderReceiptHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->receipt_history,
        ], 200);
    }
    public function getRiderInvoiceHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->invoice_history,
        ], 200);
    }
    public function getRiderRouteHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->route_history,
        ], 200);
    }
    public function getRiderEtaHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->eta_history,
        ], 200);
    }
    public function getRiderDistanceHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->distance_history,
        ], 200);
    }
    public function getRiderDurationHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->duration_history,
        ], 200);
    }
    public function getRiderStatusHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->status_history,
        ], 200);
    }
    public function getRiderLocationHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->location_history,
        ], 200);
    }
}
