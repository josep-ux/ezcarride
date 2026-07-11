<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverWallet extends Model
{
    protected $fillable = ['driver_id', 'balance'];

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'driver_wallet_id');
    }
}
