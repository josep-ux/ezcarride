<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = ['status','pickup_latitude','pickup_longitude',
                            'dropoff_latitude','dropoff_longitude','fare'];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
}
