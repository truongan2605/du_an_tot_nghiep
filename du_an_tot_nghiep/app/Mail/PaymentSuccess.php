<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccess extends Mailable
{
    use Queueable, SerializesModels;

    public $dat_phong;
    public $userName;

    
    public function __construct($dat_phong, $userName)
    {
        $this->dat_phong = $dat_phong;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->subject('Xác nhận thanh toán thành công')
            ->view('emails.payment_success')
            ->with([
                'dat_phong' => $this->dat_phong,
                'userName' => $this->userName,
            ]);
    }
}
