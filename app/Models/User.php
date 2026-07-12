<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use App\Notifications\SendNumericResetCodeNotification;

#[Fillable(['name', 'email', 'password','user_type','country','dob',
'phone_number','address','city','state','zip_code','license_number','vehicle_type','vehicle_model','vehicle_color','vehicle_plate_number','bank_name','bank_account_number','bank_account_name','payment_method','stripe_account_id','paypal_email','profile_image','status','curr_lat','curr_long'])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'curr_lat' => 'double',
            'curr_long' => 'double',
            'is_available' => 'boolean',
        ];
    }

    public function trips(){
        return $this->hasMany(Trip::class,'user_id');
    }
    public function rides(){
        return $this->hasMany(Ride::class,'passenger_id');
    }

    public function sendPasswordResetNotification($token): void
    {
        // Pass the raw token directly to our custom notification
        $this->notify(new SendNumericResetCodeNotification($token));
    }
}
