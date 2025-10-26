<!DOCTYPE html>
<html>
<head>
    <title>Xác Nhận Thanh Toán</title>
</head>
<body>
    <h1>Hi {{ $user_name }},</h1>
    <p>Payment for booking (Code: {{ $dat_phong->ma_tham_chieu }}) has been recorded.</p>
    <p>Your order is awaiting staff confirmation. Please wait for further notification.</p>
    <p>Status: {{ $dat_phong->trang_thai }}</p>
</body>
</html>