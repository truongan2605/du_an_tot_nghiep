<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $thongBao->payload['title'] ?? $thongBao->ten_template }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!-- Main Container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">THÔNG BÁO</h1>
                            <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; opacity: 0.9;">
                                {{ $thongBao->payload['title'] ?? $thongBao->ten_template }}
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <div style="color: #333333; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                                {!! nl2br(e($thongBao->payload['message'] ?? 'Bạn có thông báo mới từ hệ thống.')) !!}
                            </div>

                            @if($booking)
                            <!-- Chi tiết đặt phòng -->
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                                <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: 600;">
                                    <i style="color: #667eea;">📋</i> Thông tin đặt phòng
                                </h3>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px; width: 140px;">
                                            <strong>Mã đặt phòng:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 600;">
                                            {{ $booking->ma_tham_chieu ?? 'N/A' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Ngày nhận phòng:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px;">
                                            {{ $booking->ngay_nhan_phong ? $booking->ngay_nhan_phong->format('d/m/Y') : 'N/A' }}
                                            @if($booking->ngay_nhan_phong)
                                                <span style="color: #999999; font-size: 12px;">(Check-in: 14:00)</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Ngày trả phòng:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px;">
                                            {{ $booking->ngay_tra_phong ? $booking->ngay_tra_phong->format('d/m/Y') : 'N/A' }}
                                            @if($booking->ngay_tra_phong)
                                                <span style="color: #999999; font-size: 12px;">(Check-out: 12:00)</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($booking->ngay_nhan_phong && $booking->ngay_tra_phong)
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Số đêm:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px;">
                                            {{ $booking->ngay_nhan_phong->diffInDays($booking->ngay_tra_phong) }} đêm
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Số khách:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px;">
                                            {{ $booking->so_khach ?? 'N/A' }} người
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Trạng thái:</strong>
                                        </td>
                                        <td style="padding: 8px 0;">
                                            @php
                                                $statusColors = [
                                                    'dang_cho' => '#ffc107',
                                                    'da_xac_nhan' => '#17a2b8',
                                                    'da_gan_phong' => '#6c757d',
                                                    'da_nhan_phong' => '#28a745',
                                                    'dang_su_dung' => '#28a745',
                                                    'hoan_thanh' => '#28a745',
                                                    'da_huy' => '#dc3545',
                                                ];
                                                $statusLabels = [
                                                    'dang_cho' => 'Đang chờ',
                                                    'da_xac_nhan' => 'Đã xác nhận',
                                                    'da_gan_phong' => 'Đã gán phòng',
                                                    'da_nhan_phong' => 'Đã nhận phòng',
                                                    'dang_su_dung' => 'Đang sử dụng',
                                                    'hoan_thanh' => 'Hoàn thành',
                                                    'da_huy' => 'Đã hủy',
                                                ];
                                                $status = $booking->trang_thai ?? 'dang_cho';
                                                $color = $statusColors[$status] ?? '#6c757d';
                                                $label = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
                                            @endphp
                                            <span style="display: inline-block; padding: 4px 12px; background-color: {{ $color }}; color: #ffffff; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                {{ $label }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Tổng tiền:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #28a745; font-size: 16px; font-weight: 600;">
                                            {{ number_format($booking->tong_tien ?? 0, 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                    @if($booking->discount_amount && $booking->discount_amount > 0)
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Giảm giá:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #dc3545; font-size: 14px;">
                                            -{{ number_format($booking->discount_amount, 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                    @endif
                                    @if($booking->deposit_amount && $booking->deposit_amount > 0)
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Tiền cọc:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px;">
                                            {{ number_format($booking->deposit_amount, 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>

                            <!-- Thông tin phòng -->
                            @if($booking->datPhongItems && $booking->datPhongItems->count() > 0)
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                                <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: 600;">
                                    <i style="color: #28a745;">🏨</i> Phòng đã đặt
                                </h3>
                                @foreach($booking->datPhongItems as $item)
                                <div style="background-color: #ffffff; border-radius: 6px; padding: 15px; margin-bottom: 10px;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        @if($item->phong)
                                        <tr>
                                            <td style="padding: 5px 0; color: #666666; font-size: 14px; width: 120px;">
                                                <strong>Mã phòng:</strong>
                                            </td>
                                            <td style="padding: 5px 0; color: #333333; font-size: 14px; font-weight: 600;">
                                                {{ $item->phong->ma_phong ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        @endif
                                        @if($item->phong && $item->phong->loaiPhong)
                                        <tr>
                                            <td style="padding: 5px 0; color: #666666; font-size: 14px;">
                                                <strong>Loại phòng:</strong>
                                            </td>
                                            <td style="padding: 5px 0; color: #333333; font-size: 14px;">
                                                {{ $item->phong->loaiPhong->ten ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        @endif
                                        @if($item->so_luong)
                                        <tr>
                                            <td style="padding: 5px 0; color: #666666; font-size: 14px;">
                                                <strong>Số lượng:</strong>
                                            </td>
                                            <td style="padding: 5px 0; color: #333333; font-size: 14px;">
                                                {{ $item->so_luong }} phòng
                                            </td>
                                        </tr>
                                        @endif
                                        @if($item->gia_tren_dem)
                                        <tr>
                                            <td style="padding: 5px 0; color: #666666; font-size: 14px;">
                                                <strong>Giá/đêm:</strong>
                                            </td>
                                            <td style="padding: 5px 0; color: #333333; font-size: 14px;">
                                                {{ number_format($item->gia_tren_dem, 0, ',', '.') }} VNĐ
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            <!-- Thông tin thanh toán -->
                            @php
                                $totalPaid = $booking->giaoDichs ? $booking->giaoDichs->where('trang_thai', 'thanh_cong')->sum('so_tien') : 0;
                                $remaining = ($booking->tong_tien ?? 0) - $totalPaid;
                            @endphp
                            @if($totalPaid > 0 || $remaining > 0)
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #17a2b8;">
                                <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: 600;">
                                    <i style="color: #17a2b8;">💳</i> Thông tin thanh toán
                                </h3>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px; width: 140px;">
                                            <strong>Đã thanh toán:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #28a745; font-size: 14px; font-weight: 600;">
                                            {{ number_format($totalPaid, 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                    @if($remaining > 0)
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Còn lại:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #dc3545; font-size: 14px; font-weight: 600;">
                                            {{ number_format($remaining, 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="2" style="padding: 8px 0;">
                                            <span style="display: inline-block; padding: 4px 12px; background-color: #28a745; color: #ffffff; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                ✓ Đã thanh toán đầy đủ
                                            </span>
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            @endif
                            @endif

                            <!-- Thông tin giao dịch -->
                            @if($transaction)
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                                <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: 600;">
                                    <i style="color: #ffc107;">💰</i> Chi tiết giao dịch
                                </h3>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px; width: 140px;">
                                            <strong>Mã giao dịch:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 600;">
                                            #{{ $transaction->id }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Số tiền:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #28a745; font-size: 16px; font-weight: 600;">
                                            {{ number_format($transaction->so_tien ?? 0, 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                    @if($transaction->nha_cung_cap)
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Phương thức:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px;">
                                            {{ ucfirst(str_replace('_', ' ', $transaction->nha_cung_cap)) }}
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Trạng thái:</strong>
                                        </td>
                                        <td style="padding: 8px 0;">
                                            @php
                                                $txnStatus = $transaction->trang_thai ?? 'dang_cho';
                                                $txnStatusColor = $txnStatus === 'thanh_cong' ? '#28a745' : ($txnStatus === 'that_bai' ? '#dc3545' : '#ffc107');
                                                $txnStatusLabel = $txnStatus === 'thanh_cong' ? 'Thành công' : ($txnStatus === 'that_bai' ? 'Thất bại' : 'Đang chờ');
                                            @endphp
                                            <span style="display: inline-block; padding: 4px 12px; background-color: {{ $txnStatusColor }}; color: #ffffff; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                {{ $txnStatusLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($transaction->created_at)
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Thời gian:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px;">
                                            {{ $transaction->created_at->format('d/m/Y H:i:s') }}
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            @endif

                            <!-- Thông tin bổ sung từ payload -->
                            @if(!empty($thongBao->payload['amount']) && !$transaction)
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px; width: 140px;">
                                            <strong>Số tiền:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #28a745; font-size: 16px; font-weight: 600;">
                                            {{ number_format($thongBao->payload['amount'], 0, ',', '.') }} VNĐ
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            @endif

                            @if(!$booking && (!empty($thongBao->payload['booking_id']) || !empty($thongBao->payload['transaction_id'])))
                            <!-- Thông tin cơ bản từ payload khi không load được booking -->
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    @if(!empty($thongBao->payload['booking_id']))
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px; width: 140px;">
                                            <strong>Mã đặt phòng:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 600;">
                                            #{{ $thongBao->payload['booking_id'] }}
                                        </td>
                                    </tr>
                                    @endif
                                    @if(!empty($thongBao->payload['transaction_id']))
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                            <strong>Mã giao dịch:</strong>
                                        </td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 600;">
                                            #{{ $thongBao->payload['transaction_id'] }}
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            @endif

                            @if(!empty($thongBao->payload['link']) || !empty($thongBao->payload['booking_id']))
                            <div style="margin-top: 30px; text-align: center;">
                                @php
                                    $linkUrl = null;
                                    
                                    // Ưu tiên 1: Sử dụng link trong payload (đã được set đúng cho từng loại người nhận)
                                    if (!empty($thongBao->payload['link'])) {
                                        $link = $thongBao->payload['link'];
                                        
                                        // Nếu link bắt đầu bằng /, dùng url() để tạo absolute URL
                                        if (str_starts_with($link, '/')) {
                                            $linkUrl = url($link);
                                        } 
                                        // Nếu link là route name (có dấu chấm), thử dùng route()
                                        elseif (str_contains($link, '.')) {
                                            try {
                                                $linkUrl = route($link);
                                            } catch (\Exception $e) {
                                                $linkUrl = url($link);
                                            }
                                        } 
                                        // Nếu là absolute URL, dùng trực tiếp
                                        elseif (filter_var($link, FILTER_VALIDATE_URL)) {
                                            $linkUrl = $link;
                                        }
                                        // Ngược lại, dùng url()
                                        else {
                                            $linkUrl = url($link);
                                        }
                                    } 
                                    // Ưu tiên 2: Nếu không có link nhưng có booking_id, tạo link dựa trên vai trò người nhận
                                    elseif (!empty($thongBao->payload['booking_id'])) {
                                        $bookingId = $thongBao->payload['booking_id'];
                                        
                                        // Kiểm tra vai trò người nhận
                                        $recipientRole = $recipient->vai_tro ?? null;
                                        
                                        if (in_array($recipientRole, ['admin', 'nhan_vien'])) {
                                            // Staff/Admin: dùng route staff
                                            try {
                                                $linkUrl = route('staff.bookings.show', $bookingId);
                                            } catch (\Exception $e) {
                                                // Fallback nếu route không tồn tại
                                                $linkUrl = url('/staff/bookings/' . $bookingId);
                                            }
                                        } else {
                                            // Customer hoặc không có vai trò: dùng route account
                                            try {
                                                $linkUrl = route('account.booking.show', $bookingId);
                                            } catch (\Exception $e) {
                                                // Fallback nếu route không tồn tại
                                                $linkUrl = url('/account/bookings/' . $bookingId);
                                            }
                                        }
                                    }
                                @endphp
                                
                                @if($linkUrl)
                                <a href="{{ $linkUrl }}" 
                                   style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                    {{ $booking ? 'Xem chi tiết đặt phòng' : 'Xem chi tiết' }}
                                </a>
                                @endif
                            </div>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                <strong>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</strong>
                            </p>
                            <p style="margin: 0; color: #999999; font-size: 12px;">
                                Thời gian: {{ $thongBao->created_at->format('d/m/Y H:i') }}
                            </p>
                            <p style="margin: 15px 0 0 0; color: #999999; font-size: 11px;">
                                Đây là email tự động, vui lòng không trả lời email này.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>


