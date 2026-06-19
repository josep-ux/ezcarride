<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\RiderController;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/updateProfile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/rideRequest', [TripController::class, 'requestRide']);
    Route::get('/rider', [RiderController::class, 'dashboard']);
    Route::get('/driver', [DriverController::class, 'dashboard']);
    Route::post('/requestRide', [DriverController::class, 'requestRide']);
});
