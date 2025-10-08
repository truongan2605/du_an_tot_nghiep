<?php 
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public $dat_phong;
    public $user_name;

    public function __construct($dat_phong, $user_name)
    {
        $this->dat_phong = $dat_phong;
        $this->user_name = $user_name;
    }

    public function build()
    {
        return $this->view('emails.payment_pending_confirmation')
            ->subject('Xác Nhận Thanh Toán Đang Chờ Xử Lý');
    }
}