<?php

namespace App\Mail;

use App\Models\ThongBao;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ThongBaoEmail extends Mailable
{
    use Queueable, SerializesModels;

    public ThongBao $thongBao;

    public function __construct(ThongBao $thongBao)
    {
        $this->thongBao = $thongBao;
    }

    public function build()
    {
        $subject = $this->thongBao->payload['subject'] ?? ('Thông báo: ' . $this->thongBao->ten_template);
        return $this->subject($subject)
            ->view('emails.thong-bao')
            ->with([
                'thongBao' => $this->thongBao,
            ]);
    }
}


