@php
    $displayName = $userName ?: 'bạn';
@endphp

<p>Xin chào {{ $displayName }},</p>

<p>Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản trên hệ thống đặt phòng khách sạn.</p>

<p>Mã xác thực của bạn là:</p>

<h2 style="font-size: 24px; letter-spacing: 4px; text-align:center; margin: 16px 0;">
    <strong>{{ $code }}</strong>
</h2>

<p>Mã này có hiệu lực trong <strong>{{ $expiresInMinutes }} phút</strong>. Vui lòng không chia sẻ mã này cho bất kỳ ai.</p>

<p>Nếu bạn không thực hiện yêu cầu này, bạn có thể bỏ qua email này.</p>

<p>Trân trọng,<br>
Hệ thống đặt phòng khách sạn</p>






