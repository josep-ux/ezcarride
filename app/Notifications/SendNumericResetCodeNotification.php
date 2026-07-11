<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendNumericResetCodeNotification extends Notification
{
    use Queueable;

    protected $code;
    //protected $name

    public function __construct($code)
    {
        $this->code = (string) $code;
        //$this->name = (string) $code;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Convert Laravel's underlying token into your exact 6-digit numeric PIN
        $pin = crc32($this->code) % 1000000;
        $pin = str_pad((string)abs($pin), 6, '0', STR_PAD_LEFT);

        return (new MailMessage)
            ->subject('Your Password Reset Code')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->line('Your 6-digit password reset verification code is:')
            ->line($pin) // Displays the 6-digit number boldly in the email
            ->line('This verification code will expire shortly.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
