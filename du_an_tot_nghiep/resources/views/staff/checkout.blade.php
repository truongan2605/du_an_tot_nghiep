@extends('layouts.admin')

@section('title', 'Check-out')

@section('content')
<div class="p-4">
    <h2 class="text-center mb-5 fw-bold text-dark">Danh Sách Check-out Hôm Nay</h2>
    <div class="card shadow-sm rounded-3 border-0">
        <div class="card-header bg-gradient-warning text-white fw-bold d-flex align-items-center">
            <i class="bi bi-box-arrow-left me-2"></i> Booking Sẵn Sàng Check-out
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Mã Booking</th>
                        <th>Khách Hàng</th>
                        <th>Phòng</th>
                        <th>Ngày Nhận</th>
                        <th>Ngày Trả</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>
                                <a href="{{ route('staff.bookings.show', $booking->id) }}" class="text-primary text-decoration-none">
                                    {{ $booking->ma_tham_chieu }}
                                </a>
                            </td>
                            <td>{{ $booking->nguoiDung?->name ?? $booking->customer_name ?? 'Ẩn danh' }}</td>
                            <td>
                                @foreach ($booking->datPhongItems as $item)
                                    @if ($item->phong)
                                        <span class="badge bg-success me-1">{{ $item->phong->ma_phong }}</span>
                                    @endif
                                @endforeach
                            </td>
                            <td>{{ \Carbon\Carbon::parse($booking->ngay_nhan_phong)->format('d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($booking->ngay_tra_phong)->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge 
                                    @if($booking->trang_thai == 'da_gan_phong') bg-success
                                    @elseif($booking->trang_thai == 'dang_o') bg-info
                                    @else bg-secondary
                                    @endif rounded-pill">
                                    {{ ucfirst(str_replace('_', ' ', $booking->trang_thai)) }}
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('staff.checkout.process') }}" method="POST" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Xác nhận check-out cho booking #{{ $booking->ma_tham_chieu }}?')">
                                        <i class="bi bi-box-arrow-left"></i> Check-out
                                    </button>
                                </form>
                                <a href="{{ route('staff.bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Xem
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center">Không có booking nào sẵn sàng check-out hôm nay.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection