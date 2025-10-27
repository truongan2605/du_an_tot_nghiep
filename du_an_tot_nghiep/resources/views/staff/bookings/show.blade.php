@extends('layouts.staff')

@section('title', 'Chi Tiết Booking #' . $booking->ma_tham_chieu)

@section('content')
<div class="p-4">
    <h2 class="text-center mb-5 fw-bold text-dark">Chi Tiết Booking #{{ $booking->ma_tham_chieu }}</h2>

    <div class="card shadow-sm rounded-3 border-0">
        <div class="card-header bg-gradient-primary text-white fw-bold d-flex align-items-center">
            <i class="bi bi-info-circle me-2"></i> Thông Tin Booking
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Mã Booking:</strong> {{ $booking->ma_tham_chieu }}</p>
                    <p><strong>Khách Hàng:</strong> {{ $booking->nguoiDung?->name ?? $booking->customer_name ?? 'Ẩn danh' }}</p>
                    <p><strong>Email:</strong> {{ $booking->customer_email ?? 'N/A' }}</p>
                    <p><strong>Số Điện Thoại:</strong> {{ $booking->customer_phone ?? 'N/A' }}</p>
                    <p><strong>Trạng Thái:</strong>
                        <span class="badge 
                            @if($booking->trang_thai == 'da_gan_phong') bg-success
                            @elseif($booking->trang_thai == 'dang_cho') bg-warning
                            @elseif($booking->trang_thai == 'dang_cho_xac_nhan') bg-info
                            @elseif($booking->trang_thai == 'da_huy') bg-secondary
                            @elseif($booking->trang_thai == 'hoan_thanh') bg-primary
                            @elseif($booking->trang_thai == 'dang_o') bg-info
                            @else bg-secondary
                            @endif rounded-pill">
                            {{ ucfirst(str_replace('_', ' ', $booking->trang_thai)) }}
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Ngày Nhận Phòng:</strong> {{ $booking->ngay_nhan_phong ? \Carbon\Carbon::parse($booking->ngay_nhan_phong)->format('d/m/Y H:i') : '-' }}</p>
                    <p><strong>Ngày Trả Phòng:</strong> {{ $booking->ngay_tra_phong ? \Carbon\Carbon::parse($booking->ngay_tra_phong)->format('d/m/Y H:i') : '-' }}</p>
                    <p><strong>Số Đêm:</strong> {{ $meta['nights'] ?? ($booking->ngay_nhan_phong && $booking->ngay_tra_phong ? \Carbon\Carbon::parse($booking->ngay_nhan_phong)->diffInDays($booking->ngay_tra_phong) : '-') }}</p>
                    <p><strong>Tổng Tiền:</strong> {{ number_format($booking->tong_tien, 0) }} VND</p>
                    <p><strong>Phương Thức Thanh Toán:</strong> {{ $booking->phuong_thuc_thanh_toan ?? 'N/A' }}</p>
                </div>
            </div>

            <h5 class="mt-4 fw-bold">Phòng Đã Gán</h5>
            <ul class="list-group mb-4">
                @forelse ($booking->datPhongItems as $item)
                    <li class="list-group-item">
                        <strong>Phòng:</strong> {{ $item->phong?->ma_phong ?? 'Chưa gán' }} |
                        <strong>Loại Phòng:</strong> {{ $item->loaiPhong?->ten ?? 'N/A' }} |
                        <strong>Số Lượng:</strong> {{ $item->so_luong ?? 1 }} |
                        <strong>Giá/Đêm:</strong> {{ number_format($item->gia_tren_dem, 0) }} VND
                    </li>
                @empty
                    <li class="list-group-item text-muted">Chưa có phòng nào được gán.</li>
                @endforelse
            </ul>

            @if ($booking->giaoDichs->count() > 0)
                <h5 class="mt-4 fw-bold">Giao Dịch</h5>
                <ul class="list-group mb-4">
                    @foreach ($booking->giaoDichs as $giaoDich)
                        <li class="list-group-item">
                            <strong>Mã Giao Dịch:</strong> {{ $giaoDich->id }} |
                            <strong>Nhà Cung Cấp:</strong> {{ $giaoDich->nha_cung_cap ?? 'N/A' }} |
                            <strong>Số Tiền:</strong> {{ number_format($giaoDich->so_tien, 0) }} VND |
                            <strong>Trạng Thái:</strong>
                            <span class="badge {{ $giaoDich->trang_thai == 'thanh_cong' ? 'bg-success' : 'bg-danger' }} rounded-pill">
                                {{ ucfirst(str_replace('_', ' ', $giaoDich->trang_thai)) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('staff.bookings') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Quay Lại
                </a>
                @if (in_array($booking->trang_thai, ['da_gan_phong', 'dang_o']) && \Carbon\Carbon::parse($booking->ngay_tra_phong)->isToday())
                    <form action="{{ route('staff.checkout.process') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Xác nhận check-out cho booking #{{ $booking->ma_tham_chieu }}?')">
                            <i class="bi bi-box-arrow-left"></i> Check-out
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection