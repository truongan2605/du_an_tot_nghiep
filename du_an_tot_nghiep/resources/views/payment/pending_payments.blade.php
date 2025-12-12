@extends('layouts.admin')

@section('content')
<div class="container-fluid px-3 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="h5 mb-0 fw-bold text-dark">Danh Sách Chờ Xác Nhận Thanh Toán</h3>
            <small class="text-muted">Xác nhận các khoản thanh toán đặt cọc đang chờ</small>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-info rounded-pill px-3 py-2">{{ $pendingPayments->count() }} Đơn Chờ</span>
        </div>
    </div>

    @if ($pendingPayments->isEmpty())
        <div class="card shadow-sm rounded-3 border-0">
            <div class="card-body text-center py-5">
                <i class="bi bi-receipt display-4 text-muted mb-3"></i>
                <h5 class="text-muted">Không có đơn hàng nào chờ xác nhận</h5>
                <p class="text-muted mb-0">Tất cả thanh toán đã được xử lý hoặc không có đơn mới.</p>
            </div>
        </div>
    @else
        <div class="card shadow-sm rounded-3 border-0 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3 py-3 small fw-semibold" style="width: 120px;">Mã Đặt Phòng</th>
                                <th class="py-3 small fw-semibold" style="width: 150px;">Khách Hàng</th>
                                <th class="py-3 small fw-semibold text-center" style="width: 120px;">Ngày Nhận</th>
                                <th class="py-3 small fw-semibold text-center" style="width: 120px;">Ngày Trả</th>
                                <th class="py-3 small fw-semibold text-end" style="width: 100px;">Tổng Tiền</th>
                                <th class="py-3 small fw-semibold text-end" style="width: 100px;">Số Tiền Cọc</th>
                                <th class="py-3 small fw-semibold text-center" style="width: 120px;">Trạng Thái TT</th>
                                <th class="py-3 small fw-semibold text-center" style="width: 100px;">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingPayments as $payment)
                                <tr>
                                    <td class="ps-3 small fw-semibold">{{ $payment->ma_tham_chieu }}</td>
                                    <td class="small">{{ Str::limit($payment->nguoiDung->name ?? 'Chưa có thông tin', 25) }}</td>
                                    <td class="small text-center">{{ $payment->ngay_nhan_phong->format('d/m H:i') }}</td>
                                    <td class="small text-center">{{ $payment->ngay_tra_phong->format('d/m H:i') }}</td>
                                    <td class="text-end fw-semibold small">{{ number_format($payment->tong_tien, 0, ',', '.') }}đ</td>
                                    <td class="text-end fw-semibold small">{{ number_format($payment->deposit_amount, 0, ',', '.') }}đ</td>
                                    <td class="text-center">
                                        <span class="badge 
                                            @if ($payment->giaoDichs->where('trang_thai', 'thanh_cong')->first())
                                                bg-success text-white rounded-pill px-2 py-1 small
                                            @else
                                                bg-warning text-dark rounded-pill px-2 py-1 small
                                            @endif
                                        ">
                                            {{ $payment->giaoDichs->where('trang_thai', 'thanh_cong')->first() ? 'Thành Công' : 'Chờ Xác Nhận' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('api.confirm-payment', $payment->id) }}" method="POST" class="d-inline" id="confirm-form-{{ $payment->id }}">
                                            @csrf
                                            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 py-1" onclick="handleConfirm('Xác nhận thanh toán cho {{ $payment->ma_tham_chieu }}?', {{ $payment->id }})">
                                                <i class="bi bi-check-lg me-1"></i>Xác Nhận
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function handleConfirm(message, id) {
    if (confirm(message)) {
        const form = document.getElementById(`confirm-form-${id}`);
        const formData = new FormData(form);
        fetch(form.action, {
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
    }
}
</script>

<style>
.table {
    font-size: 0.875rem;
}
.table th {
    font-weight: 600;
    color: #fff;
    border-top: none;
}
.table td {
    border-color: rgba(0,0,0,0.05);
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
}
.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
    transition: background-color 0.15s ease;
}
.card {
    transition: box-shadow 0.2s ease;
}
.card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.btn-sm {
    font-size: 0.75rem;
    transition: all 0.2s ease;
}
.btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
</style>
@endsection