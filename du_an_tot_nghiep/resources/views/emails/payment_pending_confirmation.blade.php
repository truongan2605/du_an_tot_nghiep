<!DOCTYPE html>
<html>
<head>
    <title>Xác Nhận Thanh Toán</title>
</head>
<body>
    <h1>Chào {{ $user_name }},</h1>
    <p>Thanh toán cho đặt phòng (Mã: {{ $dat_phong->ma_tham_chieu }}) đã được ghi nhận.</p>
    <p>Đơn hàng của bạn đang chờ nhân viên xác nhận. Vui lòng đợi thông báo tiếp theo.</p>
    <p>Trạng thái: {{ $dat_phong->trang_thai }}</p>
</body>
</html>