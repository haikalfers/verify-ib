<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public array $context;

    public function __construct(string $code, array $context = [])
    {
        $this->code = $code;
        $this->context = $context;
    }

    public function build()
    {
        return $this
            ->subject('Kode Verifikasi Login Admin')
            ->view('emails.admin-otp');
    }
}
