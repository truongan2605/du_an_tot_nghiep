<!DOCTYPE html>
<html>
<head>
    <title>Test VNPAY Sandbox</title>
</head>
<body>
    <h1>Thanh toán thử qua VNPAY</h1>
    <form action="{{ route('payment.initiate') }}" method="POST">
        @csrf
        <label>dat_phong_id:</label>
        <input type="number" name="dat_phong_id" value="1" required><br>

        <label>Số tiền (VND):</label>
        <input type="number" name="amount" value="10000" required><br>

        <label>Thông tin đơn hàng:</label>
        <input type="text" name="order_info" value="Thanh toán thử" required><br>

        <input type="hidden" name="return_url" value="{{ env('VNPAY_RETURN_URL') }}">
        <button type="submit">Thanh toán qua VNPAY</button>
    </form>
</body>
</html>
