<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = ['driver_wallet_id', 'ride_id', 'type', 'amount', 'description'];
}
