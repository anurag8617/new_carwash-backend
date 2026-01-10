<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderOtpMail extends Mailable
{
    public $otp;
    public $serviceName;
    public $userName;

    public function __construct($otp, $serviceName, $userName)
    {
        $this->otp = $otp;
        $this->serviceName = $serviceName;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->subject('Your Service Completion OTP')
                    ->view('emails.order_otp');
    }
}
