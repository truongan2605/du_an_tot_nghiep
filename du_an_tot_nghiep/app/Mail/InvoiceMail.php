<?php

namespace App\Mail;

use App\Models\HoaDon;
use App\Models\DatPhong;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $hoaDon;
    public $datPhong;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(HoaDon $hoaDon, DatPhong $datPhong)
    {
        // Eager load relationships để tránh N+1 query
        $this->hoaDon = $hoaDon->load(['hoaDonItems.phong', 'hoaDonItems.loaiPhong', 'hoaDonItems.vatDung']);
        $this->datPhong = $datPhong->load(['user']);
        $this->user = $this->datPhong->user;
        
        // Sử dụng sync queue
        $this->onConnection('sync');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $invoiceNumber = $this->hoaDon->so_hoa_don ?? 'N/A';
        return new Envelope(
            subject: "Hóa đơn #{$invoiceNumber} - Khách sạn của bạn",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'hoaDon' => $this->hoaDon,
                'datPhong' => $this->datPhong,
                'user' => $this->user,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
