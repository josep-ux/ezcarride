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
    // Modify this URL to point directly to your frontend platform route
    $frontendUrl = "https://ezcarride-ng-mainnng-ikqe9v.laravel.cloud/v1/{$token}&email={$this->email}";

    $this->notify(new class($frontendUrl) extends \Illuminate\Notifications\Notification {
        protected $url;

    public function __construct($url) {
        $this->url = $url;
     }

    public function via($notifiable) {
         return ['mail'];
    }

    public function toMail($notifiable) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->line('You are receiving this email because we received a password reset request for your account.')
                ->action('Reset Password', $this->url)
                ->line('If you did not request a password reset, no further action is required.');
        }
    });
}
}
