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

    public function sendPasswordResetNotification($token){
    // Convert the long alphanumeric Laravel token into a secure 6-digit numeric PIN
    $pinCode = crc32($token) % 1000000;
    $pinCode = str_pad(abs($pinCode), 6, '0', STR_PAD_LEFT);

    $this->notify(new class($pincode) extends \Illuminate\Notification\Notification {
        protected $pin;

        public function __construct($pin) {
            $this->pin = $pin;
        }

        public function via($notifiable) {
            return ['mail'];
        }

        public function toMail($notifiable) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Your Password Reset Code')
                ->line('You are receiving this email because we received a password reset request for your mobile account.')
                ->heading('Your Reset Code:')
                ->line($this->pin)
                ->line('Enter this 6-digit code in your mobile app to securely reset your password.')
                ->line('If you did not request this, please ignore this email.');
        }
    });
 }
}
