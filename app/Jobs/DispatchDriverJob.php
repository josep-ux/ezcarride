<?php

namespace App\Jobs;

use App\Models\Trip;
use App\Models\User;
use App\Events\TripOfferedEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchDriverJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Trip $trip)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Simple bounding box approach to query nearby available drivers
        $radius = 0.05; // ~5km radius adjustment factor

        $drivers = User::where('user_type', 'driver')
            ->where('is_available', true)
            ->whereBetween('curr_lat', [$this->trip->pickup_lat - $radius, $this->trip->pickup_lat + $radius])
            ->whereBetween('curr_lng', [$this->trip->pickup_lng - $radius, $this->trip->pickup_lng + $radius])
            ->limit(5)
            ->get();

        foreach ($drivers as $driver) {
            // Push real-time offer prompts directly to individual driver dashboard apps
            broadcast(new TripOfferedEvent($driver, $this->trip));
        }
    }
}
