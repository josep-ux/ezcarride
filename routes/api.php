<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\RiderController;
use GuzzleHttp\Psr7\Rfc7230;
use Symfony\Component\Routing\RouterInterface;

// Public Routes
Route::prefix('v1')->group(function () {
    Route::post('/driver/register', [AuthController::class, 'register']);
    Route::post('/rider/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'sendResetCode']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

//you must login to use any of this end points here
Route::prefix('v1/us/rider')->middleware('auth:sanctum')->group(function () {
    Route::put('/updateProfile', [AuthController::class, 'updateProfileRider']);
   // Step 1: User requests an calculation pricing estimate
    Route::post('/rides/estimate', [TripController::class, 'estimateTrip']);
    // Step 2: User clicks "Confirm Booking" on the app screen
    Route::post('/rides/book', [TripController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [RiderController::class, 'dashboard']);
    Route::put('/updateImage', [AuthController::class, 'changeImage']);
    Route::put('/updatePassword', [AuthController::class, 'changePassword']);
    Route::get('/rides/{id}/status', [TripController::class, 'checkRideStatus']); // <-- Add this tracking line
    Route::get('/rides', [TripController::class, 'index']); // <-- get all rides for the rider
});

Route::prefix('v1/ng/rider')->middleware('auth:sanctum')->group(function () {
    Route::put('/updateProfile', [AuthController::class, 'updateProfileRider']);
    // Step 1: User requests a calculation pricing estimate
    Route::post('/rides/estimate', [TripController::class, 'estimateTrip']);
    // Step 2: User clicks "Confirm Booking" on the app screen
    Route::post('/rides/book', [TripController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [RiderController::class, 'dashboard']);
    Route::put('/updateImage', [AuthController::class, 'changeImage']);
    Route::put('/updatePassword', [AuthController::class, 'changePassword']);
    Route::get('/rides/{id}/status', [TripController::class, 'checkRideStatus']);
    Route::get('/rides', [TripController::class, 'index']); // <-- get all rides for the rider
});
Route::prefix('v1/ng/driver')->middleware('auth:sanctum')->group(function () {
    Route::put('/updateProfile', [AuthController::class, 'updateProfileDriver']);
    Route::get('/dashboard', [DriverController::class, 'dashboard']);
    Route::post('/available-rides', [TripController::class, 'availableRides']);
    Route::put('/rides/{id}/accept', [TripController::class, 'acceptRide']);
    Route::put('/updateImage', [AuthController::class, 'changeImageDriver']);
    Route::put('/updatePassword', [AuthController::class, 'changePasswordDriver']);
    Route::post('/updateLocation', [DriverController::class, 'updateLocation']);
    Route::put('/rides/{id}/arrived', [TripController::class, 'driverArrived']);
    Route::put('/rides/{id}/start', [TripController::class, 'startTrip']);
    Route::put('/rides/{id}/complete', [TripController::class, 'completeTrip']);
    Route::post('/rides/{id}/cancel', [TripController::class, 'cancelRide']); // Handles driver cancellations
    Route::get('/earnings', [TripController::class, 'driverEarnings']); // Fetches financial stats
    Route::get('/wallet', [TripController::class, 'walletBalance']);
});

Route::prefix('v1/us/driver')->middleware('auth:sanctum')->group(function () {
    Route::put('/updateProfile', [AuthController::class, 'updateProfileDriver']);
    Route::get('/dashboard', [DriverController::class, 'dashboard']);
    Route::post('/available-rides', [TripController::class, 'availableRides']);
    Route::put('/rides/{id}/accept', [TripController::class, 'acceptRide']);
    Route::put('/updateImage', [AuthController::class, 'changeImageDriver']);
    Route::put('updatePassword', [AuthController::class, 'changePasswordDriver']);
    Route::put('/updateLocation', [DriverController::class, 'updateLocation']);
    Route::put('/rides/{id}/start', [TripController::class, 'startTrip']);
    Route::put('/rides/{id}/complete', [TripController::class, 'completeTrip']);
    Route::put('/rides/{id}/arrived', [TripController::class, 'driverArrived']);
    Route::post('/rides/{id}/cancel', [TripController::class, 'cancelRide']); // Handles driver cancellations
    Route::get('/earnings', [TripController::class, 'driverEarnings']); // Fetches financial stats
    Route::get('/wallet', [TripController::class, 'walletBalance']);
});

