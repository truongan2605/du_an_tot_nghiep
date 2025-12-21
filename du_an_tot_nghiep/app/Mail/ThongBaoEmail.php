<?php

namespace App\Mail;

use App\Models\ThongBao;
use App\Models\DatPhong;
use App\Models\GiaoDich;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ThongBaoEmail extends Mailable
{
    use Queueable, SerializesModels;

    public ThongBao $thongBao;
    public $booking = null;
    public $transaction = null;
    public $recipient = null;

    public function __construct(ThongBao $thongBao)
    {
        $this->thongBao = $thongBao;
        
        // Load người nhận với relationship để xác định vai trò
        $this->recipient = $thongBao->load('nguoiNhan')->nguoiNhan;
        
        // Load booking nếu có booking_id trong payload
        if (!empty($thongBao->payload['booking_id'])) {
            $this->booking = DatPhong::with([
                'datPhongItems.phong.loaiPhong',
                'nguoiDung',
                'giaoDichs' => function($query) {
                    $query->where('trang_thai', 'thanh_cong')->orderBy('created_at', 'desc');
                }
            ])->find($thongBao->payload['booking_id']);
        }
        
        // Load transaction nếu có transaction_id trong payload
        if (!empty($thongBao->payload['transaction_id'])) {
            $this->transaction = GiaoDich::find($thongBao->payload['transaction_id']);
        }
    }

    public function build()
    {
        // Lấy subject từ payload hoặc tạo từ title/template
        $subject = $this->thongBao->payload['subject'] 
            ?? $this->thongBao->payload['title'] 
            ?? ('Thông báo: ' . $this->thongBao->ten_template);
        
        return $this->subject($subject)
            ->view('emails.thong-bao')
            ->with([
                'thongBao' => $this->thongBao,
                'booking' => $this->booking,
                'transaction' => $this->transaction,
                'recipient' => $this->recipient,
            ]);
    }
}




