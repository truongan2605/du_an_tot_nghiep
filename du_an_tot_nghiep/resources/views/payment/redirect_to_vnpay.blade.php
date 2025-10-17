<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Redirect to VNPay</title>
</head>
<body>
  <h3>Chuẩn bị chuyển tới VNPay...</h3>
  <p>Mã đơn hàng: {{ $txnRef }}</p>
  <p>Số tiền: {{ number_format($amount) }} VND</p>

  <form id="vnpayForm" action="{{ $vnpUrl }}" method="GET">
    {{-- Nếu bạn muốn gửi method POST thay GET thì build form fields tương ứng --}}
    <button type="submit">Thanh toán ngay</button>
  </form>

  <script>
    // Auto-submit sau 1s — để người dùng thấy thông tin, hoặc đổi thành submit trực tiếp
    setTimeout(function(){ document.getElementById('vnpayForm').submit(); }, 1000);
  </script>
</body>
</html>
