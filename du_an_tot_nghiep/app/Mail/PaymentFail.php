<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentFail extends Mailable
{
    use Queueable, SerializesModels;

    public $dat_phong;
    public $errorCode;

    public function __construct($dat_phong, $errorCode)
    {
        $this->dat_phong = $dat_phong;
        $this->errorCode = $errorCode;
    }

    public function build()
    {
        return $this->subject('Thanh toán thất bại')
            ->view('emails.payment_fail')
            ->with([
                'dat_phong' => $this->dat_phong,
                'errorCode' => $this->errorCode,
            ]);
    }
}
