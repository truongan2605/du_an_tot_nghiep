@extends('layouts.staff')

@section('title', 'Danh Sách Booking Chờ Xác Nhận')

@section('content')
<div class="container-fluid px-3 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="h5 mb-0 fw-bold text-dark">Booking Chờ Xác Nhận</h3>
            <small class="text-muted">Quản lý và xác nhận các booking mới</small>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-info rounded-pill px-3 py-2">{{ $bookings->total() }} Booking</span>
        </div>
    </div>

    <div class="card shadow-sm rounded-3 border-0 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 py-3 small fw-semibold" style="width: 60px;">ID</th>
                            <th class="py-3 small fw-semibold" style="width: 140px;">Khách Hàng</th>
                            <th class="py-3 small fw-semibold" style="width: 120px;">Phòng</th>
                            <th class="py-3 small fw-semibold" style="width: 100px;">Loại Phòng</th>
                            <th class="py-3 small fw-semibold text-center" style="width: 100px;">Nhận</th>
                            <th class="py-3 small fw-semibold text-center" style="width: 100px;">Trả</th>
                            <th class="py-3 small fw-semibold text-center" style="width: 60px;">SL</th>
                            <th class="py-3 small fw-semibold text-center" style="width: 100px;">Trạng Thái</th>
                            <th class="py-3 small fw-semibold text-end" style="width: 100px;">Tổng Tiền</th>
                            <th class="py-3 small fw-semibold text-center" style="width: 120px;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                        <tr>
                            <td class="ps-3 small fw-semibold">{{ $booking->id }}</td>
                            <td class="small">{{ Str::limit($booking->nguoiDung?->name ?? $booking->customer_name ?? 'Ẩn danh', 20) }}</td>
                            <td class="small">
                                @php
                                    $meta = is_array($booking->snapshot_meta) ? $booking->snapshot_meta : json_decode($booking->snapshot_meta, true);
                                    $selectedPhongMa = $meta['selected_phong_ma'] ?? 'N/A';
                                @endphp
                                @if ($selectedPhongMa !== 'N/A')
                                    <span class="badge bg-outline-success bg-opacity-75 me-1 small rounded-pill" data-bs-toggle="tooltip" title="Khách chọn">
                                        {{ $selectedPhongMa }}
                                    </span>
                                @endif
                                @foreach ($booking->datPhongItems as $item)
                                    @if ($item->phong)
                                        <span class="badge bg-success bg-opacity-75 me-1 small rounded-pill">
                                            {{ $item->phong->ma_phong }}
                                        </span>
                                    @else
                                        <span class="text-muted small">Chưa gán</span>
                                    @endif
                                @endforeach
                            </td>
                            <td class="small">{{ Str::limit($booking->datPhongItems->first()?->loaiPhong?->ten ?? 'N/A', 15) }}</td>
                            <td class="small text-center">{{ $booking->ngay_nhan_phong?->format('d/m H:i') ?? '-' }}</td>
                            <td class="small text-center">{{ $booking->ngay_tra_phong?->format('d/m H:i') ?? '-' }}</td>
                            <td class="small text-center">{{ $booking->datPhongItems->sum('so_luong') ?? 1 }}</td>
                            <td class="text-center">
                                @php
                                    $statusColors = [
                                        'dang_cho' => 'bg-warning text-dark',
                                        'da_xac_nhan' => 'bg-primary text-white',
                                        'da_gan_phong' => 'bg-success text-white',
                                    ];
                                    $statusLabels = [
                                        'dang_cho' => 'Chờ Xác Nhận',
                                        'da_xac_nhan' => 'Đã Xác Nhận',
                                        'da_gan_phong' => 'Đã Gán Phòng',
                                    ];
                                @endphp
                                <span class="badge {{ $statusColors[$booking->trang_thai] ?? 'bg-secondary' }} rounded-pill px-2 py-1 small">
                                    {{ $statusLabels[$booking->trang_thai] ?? ucfirst(str_replace('_', ' ', $booking->trang_thai)) }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold small">{{ number_format($booking->tong_tien, 0, ',', '.') }}đ</td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    @if ($booking->trang_thai === 'dang_cho')
                                        <form action="{{ route('staff.confirm', $booking->id) }}" method="POST" class="d-inline" id="confirm-form-{{ $booking->id }}">
                                            @csrf
                                            <button type="button" class="btn btn-primary rounded-start-pill px-3 py-1" onclick="handleAction('Xác nhận và gán phòng tự động?', {{ $booking->id }}, 'confirm')">
                                                <i class="bi bi-check-lg me-1"></i>Xác Nhận
                                            </button>
                                        </form>
                                        <form action="{{ route('staff.cancel', $booking->id) }}" method="POST" class="d-inline" id="cancel-form-{{ $booking->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger rounded-end-pill px-3 py-1" onclick="handleAction('Hủy booking này?', {{ $booking->id }}, 'cancel')">
                                                <i class="bi bi-x-lg me-1"></i>Hủy
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('staff.rooms') }}" class="btn btn-outline-info rounded-pill px-3 py-1">
                                            <i class="bi bi-house me-1"></i>Xem Phòng
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x display-6 mb-3 opacity-50"></i>
                                <p class="mb-0">Không có booking nào chờ xác nhận.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($bookings->hasPages())
        <div class="card-footer bg-light border-0 py-2">
            <div class="d-flex justify-content-between align-items-center small text-muted">
                <span>Hiển thị {{ $bookings->firstItem() }} - {{ $bookings->lastItem() }} của {{ $bookings->total() }} booking</span>
                <div>
                    {{ $bookings->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function handleAction(message, id, action) {
    if (confirm(message)) {
        const form = document.getElementById(`${action}-form-${id}`);
        if (action === 'confirm') {
            form.submit();
        } else {
            form.submit();
        }
    }
}


document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
});
</script>

<style>
.table {
    font-size: 0.875rem;
}
.table th {
    font-weight: 600;
    color: #6c757d;
    border-top: none;
}
.table td {
    border-color: rgba(0,0,0,0.04);
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
.btn-group-sm .btn {
    font-size: 0.75rem;
    transition: all 0.2s ease;
}
.btn-group-sm .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.pagination .page-link {
    color: #0d6efd;
    border: 1px solid #dee2e6;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>
@endsection