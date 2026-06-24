<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;

class DriverController extends Controller
{
    public function dashboard(){
        $data = [
            'user' => auth()->user(),
            'total_trips' => Trip::where('driver_id', auth()->user()->id)->count(),
            'completed_trips' => Trip::where('driver_id', auth()->user()->id)->where('status', 'completed')->count(),
            'cancelled_trips' => Trip::where('driver_id', auth()->user()->id)->where('status', 'cancelled')->count(),
            'ongoing_trips' => Trip::where('driver_id', auth()->user()->id)->where('status', 'ongoing')->count(),
        ];
       return response()->json([
            'success' => true,
            'data' => $data
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
