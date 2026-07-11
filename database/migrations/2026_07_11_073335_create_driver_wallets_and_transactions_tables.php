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
        // Holds the driver's live withdrawable balance matrix
        Schema::create('driver_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->unique()->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->timestamps();
        });

        // Tracks every transactional credit/debit activity ledger row
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_wallet_id')->constrained('driver_wallets')->onDelete('cascade');
            $table->foreignId('ride_id')->nullable()->constrained('rides')->onDelete('set null');
            $table->string('type'); // credit, debit
            $table->decimal('amount', 8, 2);
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('driver_wallets');
    }
};
