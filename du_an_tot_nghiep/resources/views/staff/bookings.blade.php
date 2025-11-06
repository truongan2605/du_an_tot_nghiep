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
                    <th>Mã Phòng Đã Gán</th>
                    <th>Khách Hàng</th>
                    <th>Trạng Thái</th>
                    <th>Mã Tham Chiếu</th>
                    <th>Ngày Nhận</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                <tr>
                    <td>{{ $booking->id }}</td>

                    {{-- Mã phòng đã gán --}}
                    <td>
                        @php
                            $roomCodes = [];
                            if ($booking->datPhongItems) {
                                foreach ($booking->datPhongItems as $item) {
                                    if ($item->phongDaDats) {
                                        foreach ($item->phongDaDats as $phongDaDat) {
                                            if ($phongDaDat->phong) {
                                                $roomCodes[] = $phongDaDat->phong->ma_phong;
                                            }
                                        }
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
                        @elseif ($booking->trang_thai === 'da_xac_nhan')
                            <span class="text-warning fw-bold">Chờ Gán</span>
                        @else
                            <span class="text-muted">Chưa gán</span>
                        @endif
                    </td>

                    {{-- Khách hàng --}}
                    <td>{{ $booking->nguoiDung->name ?? 'Ẩn danh' }}</td>

                    {{-- Trạng thái --}}
                    <td>
                        @php
                            $statusColors = [
                                'dang_cho' => 'bg-warning text-dark',
                                'da_xac_nhan' => 'bg-primary text-white',
                                'da_gan_phong' => 'bg-success text-white',
                            ];
                        @endphp
                        <span class="badge {{ $statusColors[$booking->trang_thai] ?? 'bg-secondary' }}">
                            {{ ucfirst(str_replace('_',' ',$booking->trang_thai)) }}
                        </span>
                    </td>

                    {{-- Mã tham chiếu --}}
                    <td>{{ $booking->ma_tham_chieu ?? 'Chưa có' }}</td>

                    {{-- Ngày nhận --}}
                    <td>{{ $booking->ngay_nhan_phong?->format('d-m-Y H:i') ?? '-' }}</td>

                    {{-- Thao tác --}}
                    <td class="text-center text-nowrap">
                        @if ($booking->trang_thai === 'dang_cho')
                            <form action="{{ route('staff.confirm', $booking->id) }}" method="POST" class="d-inline-flex gap-1 align-items-center">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Xác nhận booking?')">Xác Nhận</button>
                            </form>
                            <form action="{{ route('staff.cancel', $booking->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hủy booking này?')">Hủy</button>
                            </form>
                        @elseif ($booking->trang_thai === 'da_xac_nhan')
                            <a href="{{ route('staff.assign-rooms', $booking->id) }}" class="btn btn-warning btn-sm">Gán Phòng</a>
                            <form action="{{ route('staff.cancel', $booking->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm ms-1" onclick="return confirm('Hủy booking này?')">Hủy</button>
                            </form>
                        @elseif ($booking->trang_thai === 'da_gan_phong')
                            <a href="{{ route('staff.rooms') }}" class="btn btn-success btn-sm">Xem Phòng</a>
                        @else
                            <span class="text-muted">Không có hành động</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Không có booking nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
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
