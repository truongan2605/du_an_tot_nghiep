<?php

namespace App\Services;

use App\Models\RefundRequest;
use Illuminate\Support\Facades\Storage;

class RefundReceiptGenerator
{
    /**
     * Generate a professional refund receipt image
     *
     * @param RefundRequest $refund
     * @return string|false Path to generated image or false on failure
     */
    public function generate(RefundRequest $refund)
    {
        // Image dimensions
        $width = 800;
        $height = 1000;

        // Create image
        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            return false;
        }

        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 128, 128, 128);
        $lightGray = imagecolorallocate($image, 240, 240, 240);
        $green = imagecolorallocate($image, 34, 197, 94);
        $blue = imagecolorallocate($image, 59, 130, 246);

        // Fill background
        imagefilledrectangle($image, 0, 0, $width, $height, $white);

        // Header background
        imagefilledrectangle($image, 0, 0, $width, 150, $blue);

        // Get booking info
        $booking = $refund->datPhong;
        $recipientName = $booking->contact_name ?? 'N/A';
        $bookingRef = $booking->ma_tham_chieu ?? 'N/A';

        // Prepare text data
        $hotelName = 'KHÁCH SẠN';
        $title = 'BIÊN NHẬN HOÀN TIỀN';
        $transactionId = 'REF-' . str_pad($refund->id, 6, '0', STR_PAD_LEFT);
        $date = now()->format('d/m/Y H:i');
        $amount = number_format($refund->amount, 0, ',', '.');
        
        // Use built-in font (size 1-5)
        $y = 30;
        
        // Hotel name (header)
        $this->drawCenteredText($image, $hotelName, $width, $y, $white, 5);
        $y += 40;
        
        // Title
        $this->drawCenteredText($image, $title, $width, $y, $white, 4);
        $y += 80;

        // Transaction info section
        imagefilledrectangle($image, 30, $y, $width - 30, $y + 180, $lightGray);
        $y += 25;
        
        $this->drawText($image, "Ma giao dich: $transactionId", 50, $y, $black, 3);
        $y += 35;
        
        $this->drawText($image, "Ngay: $date", 50, $y, $gray, 3);
        $y += 50;
        
        $this->drawText($image, "Nguoi nhan: $recipientName", 50, $y, $black, 3);
        $y += 35;
        
        $this->drawText($image, "Ma dat phong: $bookingRef", 50, $y, $black, 3);
        $y += 80;

        // Amount section
        $this->drawCenteredText($image, 'SO TIEN HOAN', $width, $y, $black, 3);
        $y += 50;
        
        $this->drawCenteredText($image, "$amount VND", $width, $y, $green, 5);
        $y += 80;

        // Status section
        imagefilledrectangle($image, 30, $y, $width - 30, $y + 120, $lightGray);
        $y += 30;
        
        $this->drawText($image, "Trang thai: Da hoan tien", 50, $y, $green, 3);
        $y += 40;
        
        $this->drawText($image, "Phuong thuc: Chuyen khoan", 50, $y, $black, 3);
        $y += 80;

        // Footer
        $this->drawCenteredText($image, '--- HET ---', $width, $y, $gray, 2);

        // Generate filename
        $filename = 'refund_receipt_' . $refund->id . '_' . time() . '.png';
        $path = 'refund_proofs/' . $filename;
        $fullPath = storage_path('app/public/' . $path);

        // Ensure directory exists
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Save image
        $success = imagepng($image, $fullPath);
        imagedestroy($image);

        return $success ? $path : false;
    }

    /**
     * Draw centered text
     */
    private function drawCenteredText($image, $text, $imageWidth, $y, $color, $font)
    {
        // Calculate approximate text width for built-in font
        $charWidth = imagefontwidth($font);
        $textWidth = $charWidth * strlen($text);
        $x = ($imageWidth - $textWidth) / 2;
        
        imagestring($image, $font, $x, $y, $text, $color);
    }

    /**
     * Draw left-aligned text
     */
    private function drawText($image, $text, $x, $y, $color, $font)
    {
        imagestring($image, $font, $x, $y, $text, $color);
    }
}
