@extends('layouts.staff')

@section('title', 'Tổng Quan Booking')

@section('content')
<div class="p-4">
    <h2 class="text-center mb-4 fw-bold">Tổng Quan Booking</h2>

    <div class="table-responsive shadow-sm rounded-3 bg-white p-3">
        <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Mã Phòng</th>
                    <th>Khách Hàng</th>
                    <th>Trạng Thái</th>
                    <th>Mã Tham Chiếu</th>
                    <th>Ngày Nhận</th>
                    <th>Ngày Trả</th>
                    <th class="text-end">Tổng Tiền</th>
                    <th class="text-end">Đặt Cọc (20%)</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                <tr>
                    <td>{{ $booking->id }}</td>
                    <td>
                        @php
                            $roomCodes = [];
                            if ($booking->datPhongItems) {
                                foreach ($booking->datPhongItems as $item) {
                                    if ($item->phong) {
                                        $roomCodes[] = $item->phong->ma_phong;
                                    }
                                }
                            }
                        @endphp
                        @if (!empty($roomCodes))
                            @foreach (array_unique($roomCodes) as $code)
                                <span class="badge bg-success me-1" data-bs-toggle="tooltip" title="Phòng {{ $code }}">
                                    {{ $code }}
                                </span>
                            @endforeach
                        @else
                            <span class="text-muted">Không có phòng</span>
                        @endif
                    </td>
                    <td>{{ $booking->nguoiDung?->name ?? 'Ẩn danh' }}</td>
                    <td>
                        @php
                            $statusColors = [
                                'dang_cho' => 'bg-warning text-dark',
                                'da_gan_phong' => 'bg-success text-white',
                                'dang_o' => 'bg-info text-white',
                                'da_huy' => 'bg-secondary text-white',
                                'hoan_thanh' => 'bg-primary text-white',
                            ];
                        @endphp
                        <span class="badge {{ $statusColors[$booking->trang_thai] ?? 'bg-secondary' }}">
                            {{ ucfirst(str_replace('_', ' ', $booking->trang_thai)) }}
                        </span>
                    </td>
                    <td>{{ $booking->ma_tham_chieu ?? 'Chưa có' }}</td>
                    <td>{{ $booking->ngay_nhan_phong?->format('d-m-Y H:i') ?? '-' }}</td>
                    <td>{{ $booking->ngay_tra_phong?->format('d-m-Y H:i') ?? '-' }}</td>
                    <td class="text-end fw-bold">{{ number_format($booking->tong_tien, 0, ',', '.') }} VNĐ</td>
                    <td class="text-end fw-bold">{{ number_format($booking->deposit_amount, 0, ',', '.') }} VNĐ</td>
                    <td class="text-center text-nowrap">
                        <div class="d-flex gap-1 justify-content-center">
                            @if ($booking->trang_thai === 'dang_cho')
                                <form action="{{ route('staff.confirm', $booking->id) }}" method="POST" class="d-inline-flex align-items-center">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Xác nhận và gán phòng tự động cho booking này?')">
                                        <i class="bi bi-check-circle"></i> Xác Nhận
                                    </button>
                                </form>
                                <form action="{{ route('staff.cancel', $booking->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hủy booking này?')">
                                        <i class="bi bi-x-circle"></i> Hủy
                                    </button>
                                </form>
                            @elseif (in_array($booking->trang_thai, ['da_gan_phong', 'dang_o']) && \Carbon\Carbon::parse($booking->ngay_tra_phong)->isToday())
                                <form action="{{ route('staff.checkout.process', $booking->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Xác nhận check-out cho booking #{{ $booking->ma_tham_chieu }}?')">
                                        <i class="bi bi-box-arrow-left"></i> Check-out
                                    </button>
                                </form>
                                <a href="{{ route('staff.rooms') }}" class="btn btn-success btn-sm">
                                    <i class="bi bi-house"></i> Xem Phòng
                                </a>
                            @elseif ($booking->trang_thai === 'da_huy')
                                <span class="text-muted">Đã hủy</span>
                            @elseif ($booking->trang_thai === 'hoan_thanh')
                                <span class="text-muted">Đã hoàn thành</span>
                            @else
                                <span class="text-muted">Không có hành động</span>
                            @endif
                            <a href="" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i> Xem
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">Không có booking nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $bookings->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
</div>

<style>
.table-responsive {
    background: #fff;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 0 10px rgba(0,0,0,0.08);
}
.table th, .table td {
    vertical-align: middle;
}
.table-hover tbody tr:hover {
    background-color: #f1f5f9;
    transition: background-color 0.2s;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el)
    })
});
</script>
@endsection