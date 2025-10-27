@extends('layouts.staff')

@section('title', 'Quản Lý Phòng')

@section('content')
<div class="p-4">
    <h2 class="text-center mb-4 fw-bold">Quản Lý Tình Trạng Phòng</h2>

    {{-- Form lọc --}}
    <div class="mb-4 p-3 bg-white rounded-3 shadow-sm">
        <form method="GET" action="{{ route('staff.rooms') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Mã Phòng</label>
                <input type="text" name="ma_phong" class="form-control" value="{{ request('ma_phong') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Trạng Thái Phòng</label>
                <select name="trang_thai" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <option value="trong" {{ request('trang_thai') == 'trong' ? 'selected' : '' }}>Trống</option>
                    <option value="dang_o" {{ request('trang_thai') == 'dang_o' ? 'selected' : '' }}>Đang ở</option>
                    <option value="dang_don_dep" {{ request('trang_thai') == 'dang_don_dep' ? 'selected' : '' }}>Đang dọn dẹp</option>
                </select>
            </div>

            <div class="col-md-3 d-grid">
                <button class="btn btn-primary">
                    <i class="bi bi-search"></i> Tìm kiếm
                </button>
            </div>
        </form>
    </div>

    {{-- Bảng danh sách phòng --}}
    <div class="table-responsive shadow-sm rounded-3 bg-white p-3">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã Phòng</th>
                    <th>Tầng</th>
                    <th>Trạng Thái Phòng</th>
                    <th>Khách Hàng</th>
                    <th>Ngày Nhận</th>
                    <th>Ngày Trả</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rooms as $room)
                    @php
                        $statusRoom = [
                            'trong' => ['label' => 'Trống', 'class' => 'bg-success text-white'],
                            'dang_o' => ['label' => 'Đang ở', 'class' => 'bg-primary text-white'],
                            'dang_don_dep' => ['label' => 'Đang dọn dẹp', 'class' => 'bg-warning text-dark'],
                        ];

                        $booking = $room->datPhongItems->first()?->datPhong;
                        $customer = $booking?->nguoiDung?->name ?? $booking?->customer_name ?? 'Chưa có';
                    @endphp

                    <tr>
                        <td>{{ $room->ma_phong }}</td>
                        <td>{{ $room->tang?->ten ?? 'Chưa gán tầng' }}</td>
                        <td>
                            <span class="badge {{ $statusRoom[$room->trang_thai]['class'] ?? 'bg-secondary' }}">
                                {{ $statusRoom[$room->trang_thai]['label'] ?? 'Không xác định' }}
                            </span>
                        </td>
                        <td>{{ $customer }}</td>
                        <td>{{ isset($booking->ngay_nhan_phong) ? \Carbon\Carbon::parse($booking->ngay_nhan_phong)->format('d-m-Y') : '-' }}</td>
                        <td>{{ isset($booking->ngay_tra_phong) ? \Carbon\Carbon::parse($booking->ngay_tra_phong)->format('d-m-Y') : '-' }}</td>
                        <td class="text-center text-nowrap">
                            {{-- Nếu phòng trống --}}
                            @if ($room->trang_thai === 'trong')
                                <span class="text-muted">Sẵn sàng</span>

                            {{-- Nếu phòng đang ở và hôm nay là ngày trả phòng --}}
                            @elseif ($room->trang_thai === 'dang_o' && $booking && \Carbon\Carbon::parse($booking->ngay_tra_phong)->isToday())
                                <form action="{{ route('staff.checkout.process', $booking->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm"
                                            onclick="return confirm('Xác nhận check-out cho phòng {{ $room->ma_phong }}?')">
                                        <i class="bi bi-box-arrow-left"></i> Check-out
                                    </button>
                                </form>
                                <a href="{{ route('staff.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i> Xem
                                </a>

                            {{-- Nếu phòng đang ở nhưng chưa đến ngày trả --}}
                            @elseif ($room->trang_thai === 'dang_o' && $booking)
                                <a href="{{ route('staff.bookings.show', $booking->id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye"></i> Xem
                                </a>

                            {{-- Nếu phòng đang dọn dẹp --}}
                            @elseif ($room->trang_thai === 'dang_don_dep')
                                <form action="{{ route('staff.rooms.update', $room->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="trang_thai" value="trong">
                                    <button type="submit" class="btn btn-primary btn-sm"
                                            onclick="return confirm('Cập nhật phòng {{ $room->ma_phong }} về trạng thái Trống?')">
                                        <i class="bi bi-check-circle"></i> Hoàn tất dọn dẹp
                                    </button>
                                </form>

                            {{-- Nếu không có hành động --}}
                            @else
                                <span class="text-muted">Không có hành động</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Không có phòng nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Phân trang --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $rooms->links('pagination::bootstrap-5') }}
    </div>
</div>

{{-- CSS tuỳ chỉnh --}}
<style>
    .table-responsive {
        background: #fff;
        border-radius: .5rem;
        padding: 1rem;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>
@endsection
