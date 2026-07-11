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
    Route::get('/trip/{id}/rating', [TripController::class, 'getTripRating']);
    Route::post('/trip/{id}/rating', [TripController::class, 'updateTripRating']);
    Route::get('/trip/{id}/feedback', [TripController::class, 'getTripFeedback']);
    Route::post('/trip/{id}/feedback', [TripController::class, 'updateTripFeedback']);
    Route::get('/trip/{id}/receipt', [TripController::class, 'getTripReceipt']);
    Route::post('/trip/{id}/receipt', [TripController::class, 'updateTripReceipt']);
    Route::get('/trip/{id}/invoice', [TripController::class, 'getTripInvoice']);
    Route::post('/trip/{id}/invoice', [TripController::class, 'updateTripInvoice']);
    Route::get('/trip/{id}/route', [TripController::class, 'getTripRoute']);
    Route::post('/trip/{id}/route', [TripController::class, 'updateTripRoute']);
    Route::get('/trip/{id}/eta', [TripController::class, 'getTripEta']);
    Route::post('/trip/{id}/eta', [TripController::class, 'updateTripEta']);
    Route::get('/trip/{id}/distance', [TripController::class, 'getTripDistance']);
    Route::post('/trip/{id}/distance', [TripController::class, 'updateTripDistance']);
    Route::get('/trip/{id}/duration', [TripController::class, 'getTripDuration']);
    Route::post('/trip/{id}/duration', [TripController::class, 'updateTripDuration'] );
    Route::get('/trip/{id}/statusHistory', [TripController::class, 'getTripStatusHistory']);
    Route::post('/trip/{id}/statusHistory', [TripController::class, 'updateTripStatusHistory']);
    Route::get('/trip/{id}/locationHistory', [TripController::class, 'getTripLocationHistory']);
    Route::post('/trip/{id}/locationHistory', [TripController::class, 'updateTripLocationHistory']);
    Route::get('/trip/{id}/driverHistory', [TripController::class, 'getTripDriverHistory']);
    Route::post('/trip/{id}/driverHistory', [TripController::class, 'updateTripDriverHistory']);
    Route::get('/trip/{id}/riderHistory', [TripController::class, 'getTripRiderHistory']);
    Route::post('/trip/{id}/riderHistory', [TripController::class, 'updateTripRiderHistory']);
    Route::get('/trip/{id}/fareHistory', [TripController::class, 'getTripFareHistory']);
    Route::post('/trip/{id}/fareHistory', [TripController::class, 'updateTripFareHistory']);
    Route::get('/trip/{id}/paymentHistory', [TripController::class, 'getTripPaymentHistory']);
    Route::post('/trip/{id}/paymentHistory', [TripController::class, 'updateTripPaymentHistory']);
    Route::get('/trip/{id}/ratingHistory', [TripController::class, 'getTripRatingHistory']);
    Route::post('/trip/{id}/ratingHistory', [TripController::class, 'updateTripRatingHistory']);
   
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
    Route::get('/trip/{id}/rating', [TripController::class, 'getTripRating']);
    Route::post('/trip/{id}/rating', [TripController::class, 'updateTripRating']);
    Route::get('/trip/{id}/feedback', [TripController::class, 'getTripFeedback']);
    Route::post('/trip/{id}/feedback', [TripController::class, 'updateTripFeedback']);
    Route::get('/trip/{id}/receipt', [TripController::class, 'getTripReceipt']);
    Route::post('/trip/{id}/receipt', [TripController::class, 'updateTripReceipt']);
    Route::get('/trip/{id}/invoice', [TripController::class, 'getTripInvoice']);
    Route::post('/trip/{id}/invoice', [TripController::class, 'updateTripInvoice']);
    Route::get('/trip/{id}/route', [TripController::class, 'getTripRoute']);
    Route::post('/trip/{id}/route', [TripController::class, 'updateTripRoute']);
    Route::get('/trip/{id}/eta', [TripController::class, 'getTripEta']);
    Route::post('/trip/{id}/eta', [TripController::class, 'updateTripEta']);
    Route::get('/trip/{id}/distance', [TripController::class, 'getTripDistance']);
    Route::post('/trip/{id}/distance', [TripController::class, 'updateTripDistance']);
    Route::get('/trip/{id}/duration', [TripController::class, 'getTripDuration']);
    Route::post('/trip/{id}/duration', [TripController::class, 'updateTripDuration'] );
    Route::get('/trip/{id}/statusHistory', [TripController::class, 'getTripStatusHistory']);
    Route::post('/trip/{id}/statusHistory', [TripController::class, 'updateTripStatusHistory']);
    Route::get('/trip/{id}/locationHistory', [TripController::class, 'getTripLocationHistory']);
    Route::post('/trip/{id}/locationHistory', [TripController::class, 'updateTripLocationHistory']);
    Route::get('/trip/{id}/driverHistory', [TripController::class, 'getTripDriverHistory']);
    Route::post('/trip/{id}/driverHistory', [TripController::class, 'updateTripDriverHistory']);
    Route::get('/trip/{id}/riderHistory', [TripController::class, 'getTripRiderHistory']);
    Route::post('/trip/{id}/riderHistory', [TripController::class, 'updateTripRiderHistory']);
    Route::get('/trip/{id}/fareHistory', [TripController::class, 'getTripFareHistory']);
    Route::post('/trip/{id}/fareHistory', [TripController::class, 'updateTripFareHistory']);
    Route::get('/trip/{id}/paymentHistory', [TripController::class, 'getTripPaymentHistory']);
    Route::post('/trip/{id}/paymentHistory', [TripController::class, 'updateTripPaymentHistory']);
    Route::get('/trip/{id}/ratingHistory', [TripController::class, 'getTripRatingHistory']);
    Route::post('/trip/{id}/ratingHistory', [TripController::class, 'updateTripRatingHistory']);
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
});