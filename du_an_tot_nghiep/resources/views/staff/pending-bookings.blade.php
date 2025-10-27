@extends('layouts.staff')

@section('title', 'Danh Sách Booking Chờ Xác Nhận')

@section('content')
<div class="p-4">
    <h2 class="mb-4 fw-bold">Danh Sách Booking Chờ Xác Nhận</h2>

    <div class="table-responsive shadow-sm rounded-3 bg-white p-2">
        <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center">ID</th>
                    <th>Tên Khách Hàng</th>
                    <th>Phòng Đặt</th>
                    <th>Loại Phòng</th>
                    <th>Ngày Nhận</th>
                    <th>Ngày Trả</th>
                    <th class="text-center">SL</th>
                    <th>Trạng Thái</th>
                    <th class="text-end">Tổng Tiền</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                <tr>
                    <td class="text-center">{{ $booking->id }}</td>
                    <td>{{ $booking->nguoiDung?->name ?? $booking->customer_name ?? 'Ẩn danh' }}</td>
                    <td>
                        @php
                            $meta = is_array($booking->snapshot_meta) ? $booking->snapshot_meta : json_decode($booking->snapshot_meta, true);
                            $selectedPhongMa = $meta['selected_phong_ma'] ?? 'N/A';
                        @endphp
                        <span class="badge bg-success me-1">
                            {{ $selectedPhongMa }} (Khách chọn)
                        </span>
                        @foreach ($booking->datPhongItems as $item)
                            @if ($item->phong)
                                <span class="badge bg-success me-1">
                                    {{ $item->phong->ma_phong }}
                                </span>
                            @else
                                <span class="text-muted">Chưa gán</span>
                            @endif
                        @endforeach
                    </td>
                    <td>{{ $booking->datPhongItems->first()?->loaiPhong?->ten ?? 'N/A' }}</td>
                    <td>{{ $booking->ngay_nhan_phong?->format('d-m-Y H:i') ?? '-' }}</td>
                    <td>{{ $booking->ngay_tra_phong?->format('d-m-Y H:i') ?? '-' }}</td>
                    <td class="text-center">{{ $booking->datPhongItems->sum('so_luong') ?? 1 }}</td>
                    <td class="text-center">
                        @php
                            $statusColors = [
                                'dang_cho' => 'bg-warning text-dark',
                                'da_xac_nhan' => 'bg-primary text-white',
                                'da_gan_phong' => 'bg-success text-white',
                            ];
                        @endphp
                        <span class="badge {{ $statusColors[$booking->trang_thai] ?? 'bg-secondary' }}">
                            {{ ucfirst(str_replace('_', ' ', $booking->trang_thai)) }}
                        </span>
                    </td>
                    <td class="text-end fw-bold">{{ number_format($booking->tong_tien, 0, ',', '.') }} VNĐ</td>
                    <td class="text-center text-nowrap">
                        @if ($booking->trang_thai === 'dang_cho')
                            <form action="{{ route('staff.confirm', $booking->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Xác nhận và gán phòng tự động cho booking này?');">
                                    XÁC NHẬN
                                </button>
                            </form>
                            <form action="{{ route('staff.cancel', $booking->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn hủy booking này?');">
                                    HỦY
                                </button>
                            </form>
                        @else
                            <a href="{{ route('staff.rooms') }}" class="btn btn-info btn-sm">Xem Phòng</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">Không có booking nào chờ xác nhận.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $bookings->links('pagination::bootstrap-5') }}
    </div>
</div>

<style>
.table-responsive { background: #fff; border-radius: .5rem; padding: 1rem; }
.table th, .table td { vertical-align: middle; }
</style>
@endsection