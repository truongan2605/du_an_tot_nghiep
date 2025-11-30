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
                            <div style="color: #333333; font-size: 16px; line-height: 1.6;">
                                {!! nl2br(e($thongBao->payload['message'] ?? 'Bạn có thông báo mới từ hệ thống.')) !!}
                            </div>

                            @if(!empty($thongBao->payload['link']))
                            <div style="margin-top: 30px; text-align: center;">
                                <a href="{{ url($thongBao->payload['link']) }}" 
                                   style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                    Xem chi tiết
                                </a>
                            </div>
                            @endif
                        </td>
                    </tr>

                    <!-- Additional Info -->
                    @if(!empty($thongBao->payload['booking_id']) || !empty($thongBao->payload['amount']))
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8f9fa; border-radius: 6px; padding: 20px;">
                                @if(!empty($thongBao->payload['booking_id']))
                                <tr>
                                    <td style="padding: 5px 0; color: #666666; font-size: 14px;">
                                        <strong>Mã đặt phòng:</strong> #{{ $thongBao->payload['booking_id'] }}
                                    </td>
                                </tr>
                                @endif
                                @if(!empty($thongBao->payload['amount']))
                                <tr>
                                    <td style="padding: 5px 0; color: #666666; font-size: 14px;">
                                        <strong>Số tiền:</strong> {{ number_format($thongBao->payload['amount'], 0, ',', '.') }} VNĐ
                                    </td>
                                </tr>
                                @endif
                                @if(!empty($thongBao->payload['transaction_id']))
                                <tr>
                                    <td style="padding: 5px 0; color: #666666; font-size: 14px;">
                                        <strong>Mã giao dịch:</strong> #{{ $thongBao->payload['transaction_id'] }}
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>
                    @endif

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

