@extends('layouts.staff')

@section('content')
    <div class="container mt-5">
        <h1 class="mb-4">Danh Sách Chờ Xác Nhận Thanh Toán</h1>

        @if ($pendingPayments->isEmpty())
            <div class="alert alert-info">Không có đơn hàng nào chờ xác nhận.</div>
        @else
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Mã Đặt Phòng</th>
                        <th>Khách Hàng</th>
                        <th>Ngày Nhận</th>
                        <th>Ngày Trả</th>
                        <th>Tổng Tiền</th>
                        <th>Trạng Thái Thanh Toán</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingPayments as $payment)
                        <tr>
                            <td>{{ $payment->ma_tham_chieu }}</td>
                            <td>{{ $payment->nguoiDung->name ?? 'Chưa có thông tin' }}</td>
                            <td>{{ $payment->ngay_nhan_phong->format('d/m/Y H:i') }}</td>
                            <td>{{ $payment->ngay_tra_phong->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format($payment->tong_tien, 0, ',', '.') }} {{ $payment->don_vi_tien }}</td>
                            <td>
                                @if ($payment->giaoDichs->where('trang_thai', 'thanh_cong')->first())
                                    Thanh toán thành công
                                @else
                                    Chờ xác nhận
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('api.confirm-payment', $payment->id) }}" method="POST" style="display:inline;" id="confirm-form-{{ $payment->id }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Xác nhận thanh toán cho {{ $payment->ma_tham_chieu }}?')">Xác Nhận</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <script>
        document.querySelectorAll('[id^="confirm-form-"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.confirmed) {
                        alert('Xác nhận thành công!');
                        location.reload();
                    } else {
                        alert('Xác nhận thất bại: ' + (data.error || 'Lỗi không xác định'));
                    }
                })
                .catch(error => alert('Lỗi kết nối: ' + error));
            });
        });
    </script>
@endsection