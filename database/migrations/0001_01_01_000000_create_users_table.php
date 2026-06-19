<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         // Drivers,Admin & Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('user_type')->default('user'); // type of user eg admin,driver or rider(user);
            $table->string('profile_image')->nullable();
            $table->string('status')->default('offline'); // offline, online, on_trip
            $table->decimal('curr_lat', 6, 3); //create  longitude
            $table->decimal('curr_long', 6, 3);// Creates latitude
            $table->boolean('is_available')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        //Password Reset Tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        //Sessiond Table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Trips Table
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->string('status')->default('pending'); // pending, accepted, ongoing, completed
            $table->decimal('pickup_latitude', 6, 3);
            $table->decimal('pickup_longitude', 6, 3);
            $table->decimal('dropoff_latitude', 6, 3);
            $table->decimal('dropoff_longitude', 6, 3);
            $table->decimal('fare', 8, 2);
            $table->timestamps();
        });

        // Core Ride Table
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')->constrained('users');
            $table->foreignId('driver_id')->nullable()->constrained('users');

            // Statuses: pending, accepted, arriving, in_progress, completed, canceled
            $table->string('status', 30)->default('pending');

            // Spatial fields for exact endpoints
            // $table->point('pickup_location');
            // $table->point('dropoff_location');
            // $table->string('pickup_address');
            // $table->string('dropoff_address');
            $table->decimal('pickup_lat');
            $table->decimal('pickup_long');
            $table->decimal('dropoff_lat');
            $table->decimal('dropoff_long');
            $table->string('pickup_address');
            $table->string('dropoff_address');


            // Metadata variables
            $table->decimal('surge_multiplier', 3, 2)->default(1.00);
            $table->integer('estimated_duration_seconds');
            $table->integer('estimated_distance_meters');

            // Core Timestamps
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Financial Ledger Transactions Table
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained('rides')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Who is paying/receiving

            // Type: fare_payment, driver_payout, platform_fee, tip
            $table->string('type', 30);

            // Store currency as integer cents/kobo to avoid rounding errors (e.g., $10.50 = 1050)
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('NGN');

            // Statuses: pending, settled, failed, refunded
            $table->string('status', 20)->default('pending');
            $table->string('gateway_reference_id')->nullable(); // Stripe/Paystack reference
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('rides');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
