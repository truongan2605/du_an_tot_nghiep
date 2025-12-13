<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn #{{ $hoaDon->so_hoa_don }}</title>
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
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600;">HÓA ĐƠN</h1>
                            <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; opacity: 0.9;">Số hóa đơn: <strong>{{ $hoaDon->so_hoa_don }}</strong></p>
                        </td>
                    </tr>

                    <!-- Hotel Info -->
                    <tr>
                        <td style="padding: 30px; background-color: #ffffff;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 20px; border-bottom: 2px solid #e0e0e0;">
                                        <h2 style="margin: 0; color: #333333; font-size: 22px; font-weight: 600;">Khách sạn của bạn</h2>
                                        <p style="margin: 5px 0 0 0; color: #666666; font-size: 14px;">Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Customer Info -->
                    <tr>
                        <td style="padding: 0 30px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td width="50%" style="padding: 20px 0; vertical-align: top;">
                                        <h3 style="margin: 0 0 10px 0; color: #333333; font-size: 16px; font-weight: 600;">Thông tin khách hàng</h3>
                                        <p style="margin: 5px 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                            <strong>{{ $user->name ?? 'N/A' }}</strong><br>
                                            {{ $user->email ?? 'N/A' }}<br>
                                            @if($user->so_dien_thoai)
                                                {{ $user->so_dien_thoai }}<br>
                                            @endif
                                            @if($datPhong->contact_phone)
                                                ĐT liên hệ: {{ $datPhong->contact_phone }}
                                            @endif
                                        </p>
                                    </td>
                                    <td width="50%" style="padding: 20px 0; vertical-align: top;">
                                        <h3 style="margin: 0 0 10px 0; color: #333333; font-size: 16px; font-weight: 600;">Thông tin đặt phòng</h3>
                                        <p style="margin: 5px 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                            Mã đặt phòng: <strong>{{ $datPhong->ma_tham_chieu }}</strong><br>
                                            Check-in: {{ $datPhong->checked_in_at ? $datPhong->checked_in_at->format('d/m/Y H:i') : 'N/A' }}<br>
                                            Ngày nhận: {{ $datPhong->ngay_nhan_phong->format('d/m/Y') }}<br>
                                            Ngày trả: {{ $datPhong->ngay_tra_phong->format('d/m/Y') }}<br>
                                            Số khách: {{ $datPhong->so_khach }} người
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Invoice Items -->
                    <tr>
                        <td style="padding: 30px;">
                            <h3 style="margin: 0 0 20px 0; color: #333333; font-size: 18px; font-weight: 600;">Chi tiết hóa đơn</h3>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #f8f9fa;">
                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e0e0e0; color: #333333; font-size: 14px; font-weight: 600;">Mô tả</th>
                                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e0e0e0; color: #333333; font-size: 14px; font-weight: 600;">Số lượng</th>
                                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e0e0e0; color: #333333; font-size: 14px; font-weight: 600;">Đơn giá</th>
                                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e0e0e0; color: #333333; font-size: 14px; font-weight: 600;">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $hoaDonItems = $hoaDon->hoaDonItems()->with(['phong', 'loaiPhong', 'vatDung'])->get();
                                    @endphp
                                    @forelse($hoaDonItems as $item)
                                        <tr>
                                            <td style="padding: 15px 12px; border-bottom: 1px solid #e0e0e0; color: #333333; font-size: 14px;">
                                                <strong>{{ $item->name ?? 'N/A' }}</strong>
                                                @if($item->note)
                                                    <br><span style="color: #666666; font-size: 12px;">{{ $item->note }}</span>
                                                @endif
                                                @if($item->phong)
                                                    <br><span style="color: #666666; font-size: 12px;">Phòng: {{ $item->phong->ma_phong ?? $item->phong->name ?? 'N/A' }}</span>
                                                @endif
                                            </td>
                                            <td style="padding: 15px 12px; border-bottom: 1px solid #e0e0e0; text-align: center; color: #333333; font-size: 14px;">
                                                {{ $item->quantity }}
                                            </td>
                                            <td style="padding: 15px 12px; border-bottom: 1px solid #e0e0e0; text-align: right; color: #333333; font-size: 14px;">
                                                {{ number_format($item->unit_price, 0, ',', '.') }} đ
                                            </td>
                                            <td style="padding: 15px 12px; border-bottom: 1px solid #e0e0e0; text-align: right; color: #333333; font-size: 14px; font-weight: 600;">
                                                {{ number_format($item->amount, 0, ',', '.') }} đ
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" style="padding: 20px; text-align: center; color: #666666; font-size: 14px;">
                                                Không có chi tiết
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <!-- Total Section -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="right" style="padding-top: 20px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-left: auto;">
                                            <tr>
                                                <td style="padding: 8px 15px; text-align: right; color: #666666; font-size: 14px;">Tổng cộng:</td>
                                                <td style="padding: 8px 15px; text-align: right; color: #333333; font-size: 18px; font-weight: 700; width: 200px;">
                                                    {{ number_format($hoaDon->tong_thuc_thu, 0, ',', '.') }} đ
                                                </td>
                                            </tr>
                                            @if($datPhong->member_discount_amount && $datPhong->member_discount_amount > 0)
                                                <tr style="background-color: #f8f9fa;">
                                                    <td style="padding: 8px 15px; text-align: right; color: #666666; font-size: 14px;">Giảm giá thành viên:</td>
                                                    <td style="padding: 8px 15px; text-align: right; color: #28a745; font-size: 14px; font-weight: 600;">
                                                        -{{ number_format($datPhong->member_discount_amount, 0, ',', '.') }} đ
                                                    </td>
                                                </tr>
                                            @endif
                                            @if($datPhong->discount_amount && $datPhong->discount_amount > 0)
                                                <tr style="background-color: #f8f9fa;">
                                                    <td style="padding: 8px 15px; text-align: right; color: #666666; font-size: 14px;">Giảm giá voucher:</td>
                                                    <td style="padding: 8px 15px; text-align: right; color: #28a745; font-size: 14px; font-weight: 600;">
                                                        -{{ number_format($datPhong->discount_amount, 0, ',', '.') }} đ
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <td style="padding: 15px; text-align: right; color: #ffffff; font-size: 16px; font-weight: 600;">Tổng thanh toán:</td>
                                                <td style="padding: 15px; text-align: right; color: #ffffff; font-size: 20px; font-weight: 700;">
                                                    {{ number_format($hoaDon->tong_thuc_thu, 0, ',', '.') }} đ
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Payment Status -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 15px; background-color: #f8f9fa; border-radius: 6px; border-left: 4px solid #667eea;">
                                        <p style="margin: 0; color: #333333; font-size: 14px;">
                                            <strong>Trạng thái thanh toán:</strong> 
                                            <span style="color: {{ $hoaDon->trang_thai === 'da_thanh_toan' ? '#28a745' : '#ffc107' }}; font-weight: 600;">
                                                @if($hoaDon->trang_thai === 'da_thanh_toan')
                                                    Đã thanh toán
                                                @elseif($hoaDon->trang_thai === 'da_xuat')
                                                    Đã xuất hóa đơn
                                                @else
                                                    {{ ucfirst(str_replace('_', ' ', $hoaDon->trang_thai)) }}
                                                @endif
                                            </span>
                                        </p>
                                        <p style="margin: 10px 0 0 0; color: #666666; font-size: 13px;">
                                            Ngày xuất: {{ $hoaDon->created_at->format('d/m/Y H:i') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                <strong>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</strong>
                            </p>
                            <p style="margin: 0; color: #999999; font-size: 12px;">
                                Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.<br>
                                Email: support@hotel.com | Hotline: 1900-xxxx
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

