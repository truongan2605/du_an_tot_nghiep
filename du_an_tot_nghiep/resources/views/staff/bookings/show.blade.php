@extends('layouts.staff')

@section('title', 'Chi Tiết Booking:' . $booking->ma_tham_chieu)

@section('content')
    <div class="container-fluid py-5">
        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="display-6 fw-bold text-gradient-primary">
                <i class="bi bi-journal-bookmark-fill me-2"></i>
                Booking :{{ $booking->ma_tham_chieu }}
            </h1>
            <p class="text-muted">Thông tin chi tiết đặt phòng</p>
        </div>

        <!-- Main Card -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header bg-gradient-primary text-white py-4 position-relative overflow-hidden">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                    <h5 class="mb-0 fw-bold">Thông Tin Booking</h5>
                </div>
                <div class="position-absolute end-0 top-50 translate-middle-y pe-5 opacity-10">
                    <i class="bi bi-calendar-check fs-1"></i>
                </div>
            </div>

            <div class="card-body p-5">
                <div class="row g-5">

                    <div class="col-lg-6">
                        <h6 class="text-primary fw-bold mb-4"><i class="bi bi-person-circle me-2"></i>Khách Hàng</h6>
                        <div class="ps-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-tag-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Mã Booking</small>
                                    <p class="mb-0 fw-bold text-dark">#{{ $booking->ma_tham_chieu }}</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-person-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Họ Tên</small>
                                    <p class="mb-0">
                                        {{ $booking->nguoiDung?->name ?? ($booking->customer_name ?? 'Ẩn danh') }}</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-envelope-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Email</small>
                                    <p class="mb-0">{{ $booking->nguoiDung?->email ?? ($booking->email ?? 'N/A') }}</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-telephone-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Số Điện Thoại</small>
                                    <p class="mb-0">{{ $booking->contact_phone ?? ($booking->phone ?? 'N/A') }}</p>
                                </div>
                            </div>
                              <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-telephone-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Địa chỉ</small>
                                    <p class="mb-0">{{ $booking->contact_address ?? ($booking->address ?? 'N/A') }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-telephone-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Ghi chú</small>
                                    <p class="mb-0">{{ $booking->ghi_chu ?? ($booking->ghi_chu ?? '...') }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-patch-check-fill text-muted me-3 fs-5"></i>
                                <div>
                                    <small class="text-muted">Trạng Thái</small>
                                    <div class="mt-1">
                                        @php
                                            $status = $booking->trang_thai;
                                            $statusClasses = [
                                                'da_gan_phong' => 'bg-success',
                                                'dang_cho' => 'bg-warning text-dark',
                                                'dang_cho_xac_nhan' => 'bg-info',
                                                'da_huy' => 'bg-secondary',
                                                'hoan_thanh' => 'bg-primary',
                                                'dang_o' => 'bg-indigo text-white',
                                            ];
                                            $statusIcons = [
                                                'da_gan_phong' => 'bi-check-circle',
                                                'dang_cho' => 'bi-hourglass-split',
                                                'dang_cho_xac_nhan' => 'bi-clock-history',
                                                'da_huy' => 'bi-x-circle',
                                                'hoan_thanh' => 'bi-check2-all',
                                                'dang_o' => 'bi-house-door',
                                            ];
                                        @endphp

                                        <span
                                            class="badge rounded-pill px-3 py-2 fs-7 {{ $statusClasses[$status] ?? 'bg-dark' }}">
                                            <i class="bi {{ $statusIcons[$status] ?? 'bi-question-circle' }} me-1"></i>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-check text-muted me-3 fs-5"></i>
                                <div>
                                    <small class="text-muted">Trạng Thái Check-in</small>
                                    <div class="mt-1">
                                        @if ($booking->checked_in_at)
                                            <span
                                                class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-2 fs-7">
                                                <i class="bi bi-clock-history me-1"></i>
                                                Đã check-in lúc {{ $booking->checked_in_at->format('d/m/Y H:i:s') }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary text-white rounded-pill px-3 py-2 fs-7">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Chưa check-in
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="col-lg-6">
                        <h6 class="text-primary fw-bold mb-4"><i class="bi bi-calendar3 me-2"></i>Chi Tiết Đặt Phòng</h6>
                        <div class="ps-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-calendar-check text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Nhận Phòng</small>
                                    <p class="mb-0 fw-bold">
                                        {{ $booking->ngay_nhan_phong ? \Carbon\Carbon::parse($booking->ngay_nhan_phong)->format('d/m/Y H:i') : '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-calendar-x text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Trả Phòng</small>
                                    <p class="mb-0 fw-bold">
                                        {{ $booking->ngay_tra_phong ? \Carbon\Carbon::parse($booking->ngay_tra_phong)->format('d/m/Y H:i') : '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-moon-stars-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Số Đêm</small>
                                    <p class="mb-0 fw-bold">
                                        {{ $meta['nights'] ?? ($booking->ngay_nhan_phong && $booking->ngay_tra_phong ? \Carbon\Carbon::parse($booking->ngay_nhan_phong)->diffInDays($booking->ngay_tra_phong) : '-') }}
                                        đêm</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-currency-exchange text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Tổng Tiền</small>
                                    <p class="mb-0 fs-5 fw-bold text-success">{{ number_format($booking->tong_tien, 0) }} ₫
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <i class="bi bi-credit-card-2-front-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Phương Thức</small>
                                    <p class="mb-0">{{ $booking->phuong_thuc_thanh_toan ?? 'VN PAY' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <hr class="my-5">

                <h6 class="text-primary fw-bold mb-4"><i class="bi bi-door-open-fill me-2"></i>Phòng Đã Gán</h6>
                @forelse ($booking->datPhongItems as $item)
                    <div class="card border-0 shadow-sm mb-3 rounded-3 overflow-hidden">
                        <div class="card-body py-3 px-4">
                            <div class="row align-items-center text-sm">
                                <div class="col-md-3">
                                    <strong class="text-primary">#{{ $item->phong?->ma_phong ?? 'Chưa gán' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <i class="bi bi-building me-1"></i> {{ $item->loaiPhong?->ten ?? 'N/A' }}
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-light text-dark border">{{ $item->so_luong ?? 1 }} phòng</span>
                                </div>
                                <div class="col-md-4 text-end">
                                    <strong class="text-success">{{ number_format($item->gia_tren_dem, 0) }} ₫</strong>/đêm
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 mb-3 d-block"></i>
                        <p>Chưa có phòng nào được gán.</p>
                    </div>
                @endforelse


                @if ($booking->giaoDichs->count() > 0)
                    <hr class="my-5">
                    <h6 class="text-primary fw-bold mb-4"><i class="bi bi-receipt me-2"></i>Lịch Sử Giao Dịch</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã GD</th>
                                    <th>Nhà Cung Cấp</th>
                                    <th class="text-end">Số Tiền Cọc</th>
                                    <th class="text-center">Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($booking->giaoDichs as $giaoDich)
                                    <tr>
                                        <td><code>#{{ $giaoDich->id }}</code></td>
                                        <td>{{ $giaoDich->nha_cung_cap ?? 'N/A' }}</td>
                                        <td class="text-end fw-bold text-success">
                                            {{ number_format($giaoDich->so_tien, 0) }} ₫</td>
                                        <td class="text-center">
                                            <span
                                                class="badge rounded-pill px-3 py-2
                                            {{ $giaoDich->trang_thai == 'thanh_cong' ? 'bg-success' : 'bg-danger' }}">
                                                <i
                                                    class="bi {{ $giaoDich->trang_thai == 'thanh_cong' ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>
                                                {{ ucfirst(str_replace('_', ' ', $giaoDich->trang_thai)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif


                <hr class="my-5">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                    <a href="{{ route('staff.rooms') }}" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="bi bi-arrow-left me-2"></i>Quay Lại
                    </a>

                    @if (in_array($booking->trang_thai, ['da_gan_phong', 'dang_o']) &&
                            \Carbon\Carbon::parse($booking->ngay_tra_phong)->isToday())
                        <form action="{{ route('staff.checkout.process') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <button type="submit" class="btn btn-warning btn-lg px-5 shadow-sm"
                                onclick="return confirm('⚠️ Xác nhận check-out cho booking #{{ $booking->ma_tham_chieu }}?\n\nKhách sẽ được trả phòng ngay lập tức.')">
                                <i class="bi bi-box-arrow-right me-2"></i>Check-out Ngay
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .text-gradient-primary {
            background: linear-gradient(90deg, #0d6efd, #0a58ca);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .bg-indigo {
            background-color: #5f3dc4 !important;
        }

        .card {
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .table code {
            font-size: 0.85em;
        }
    </style>
@endsection
