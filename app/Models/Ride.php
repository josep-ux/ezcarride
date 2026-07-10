<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    protected $fillable = [
        'passenger_id',
        'driver_id',
        'status',
        'pickup_lat',
        'pickup_long',
        'dropoff_lat',
        'dropoff_long',
        'pickup_address',
        'dropoff_address',
        'surge_multiplier',
        'estimated_duration_seconds',
        'estimated_distance_meters',
        'fare',
        'accepted_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'pickup_lat' => 'double',
        'pickup_long' => 'double',
        'dropoff_lat' => 'double',
        'dropoff_long' => 'double',
        'surge_multiplier' => 'float',
        'fare' => 'float',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
