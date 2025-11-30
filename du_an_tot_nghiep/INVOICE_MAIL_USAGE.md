# Hướng dẫn sử dụng InvoiceMail

## Mô tả
`InvoiceMail` là class Mailable được sử dụng để gửi email hóa đơn cho khách hàng khi họ check-in hoàn thành.

## Cách sử dụng

### 1. Gửi email hóa đơn khi check-in

```php
use App\Mail\InvoiceMail;
use App\Models\HoaDon;
use App\Models\DatPhong;
use Illuminate\Support\Facades\Mail;

// Trong controller hoặc service
$hoaDon = HoaDon::with(['hoaDonItems.phong', 'hoaDonItems.loaiPhong', 'hoaDonItems.vatDung'])
    ->find($hoaDonId);

$datPhong = DatPhong::with(['user'])->find($bookingId);

// Gửi email
Mail::to($datPhong->user->email)->send(new InvoiceMail($hoaDon, $datPhong));
```

### 2. Gửi email trong PaymentNotificationService

Thêm vào method `sendCheckinNotification`:

```php
use App\Mail\InvoiceMail;

// Sau khi check-in thành công
if ($hoaDon) {
    try {
        Mail::to($user->email)->send(new InvoiceMail($hoaDon, $booking));
        Log::info('Invoice email sent to customer', [
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'hoa_don_id' => $hoaDon->id,
        ]);
    } catch (\Throwable $e) {
        Log::warning('Failed to send invoice email', [
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

### 3. Gửi email khi checkout (nếu có hóa đơn mới)

```php
// Trong CheckoutController hoặc tương tự
$hoaDon = $booking->hoaDons()->latest()->first();

if ($hoaDon && $hoaDon->trang_thai === 'da_xuat') {
    Mail::to($booking->user->email)->send(new InvoiceMail($hoaDon, $booking));
}
```

## Lưu ý

- Email sử dụng **sync queue** để gửi ngay lập tức
- Template sử dụng **inline styles** để tương thích tốt với các email client
- Các relationships được eager load để tránh N+1 query
- Email hiển thị đầy đủ thông tin: khách hàng, booking, chi tiết hóa đơn, tổng tiền

## Template Email

Template được lưu tại: `resources/views/emails/invoice.blade.php`

Template bao gồm:
- Header với số hóa đơn
- Thông tin khách hàng
- Thông tin đặt phòng
- Chi tiết các items trong hóa đơn
- Tổng tiền và các khoản giảm giá (nếu có)
- Trạng thái thanh toán
- Footer với thông tin liên hệ

