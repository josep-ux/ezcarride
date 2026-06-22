<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;

class DriverController extends Controller
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

    public function getDriverHistory($id){
        $trip = Trip::find($id);
        return response()->json([
            'success' => true,
            'data' => $trip->driver_history,
        ], 200);
    }
}
