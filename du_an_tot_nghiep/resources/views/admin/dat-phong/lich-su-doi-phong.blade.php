@extends('layouts.admin')

@section('title', 'Lịch sử đổi phòng')

@push('styles')
<style>
    .history-timeline {
        position: relative;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 40px;
        margin-bottom: 2rem;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 50px;
        bottom: -50px;
        width: 2px;
        background: linear-gradient(180deg, #0d6efd 0%, #6c757d 100%);
    }
    
    .timeline-item:last-child::before {
        display: none;
    }
    
    .timeline-icon {
        position: absolute;
        left: 0;
        top: 30px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 2;
    }
    
    .room-card {
        transition: all 0.3s ease;
        border: 2px solid transparent;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    }
    
    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .room-card-old {
        border-color: #dc3545;
        background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
    }
    
    .room-card-new {
        border-color: #198754;
        background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
    }
    
    .price-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .arrow-separator {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 0;
    }
    
    .arrow-separator::before,
    .arrow-separator::after {
        content: '';
        flex: 1;
        height: 2px;
        background: linear-gradient(90deg, transparent 0%, #0d6efd 50%, transparent 100%);
    }
    
    .arrow-icon {
        background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        animation: pulse 2s infinite;
        margin: 0 20px;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .info-row:last-child {
        border-bottom: none;
        padding-top: 15px;
        margin-top: 10px;
        border-top: 2px solid #dee2e6;
    }
    
    .info-label {
        color: #6c757d;
        font-weight: 500;
    }
    
    .info-value {
        font-weight: 600;
        color: #212529;
    }
    
    .summary-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .summary-item {
        text-align: center;
        padding: 15px;
    }
    
    .summary-label {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-bottom: 8px;
    }
    
    .summary-value {
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .badge-custom {
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 0.95rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-upgrade {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    
    .badge-downgrade {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    }
    
    .badge-same {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
    }
    
    .room-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .room-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .icon-old {
        background: linear-gradient(135deg, #fecaca 0%, #ef4444 100%);
        color: #7f1d1d;
    }
    
    .icon-new {
        background: linear-gradient(135deg, #bbf7d0 0%, #22c55e 100%);
        color: #14532d;
    }
    
    .weekend-tag {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: #78350f;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
    }
    
    .empty-icon {
        font-size: 4rem;
        color: #adb5bd;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')

<div class="container-fluid px-4">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">
                <i class="bi bi-clock-history me-2 text-primary"></i>
                Lịch sử đổi phòng
            </h3>
            <p class="text-muted mb-0">
                <i class="bi bi-bookmark-fill me-1"></i>
                Booking #{{ $booking->id }} • 
                <span class="badge bg-primary">{{ $lichSuDoiPhong->count() }} lần đổi</span>
            </p>
        </div>
        <a href="{{ route('staff.bookings.show', $booking->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Quay lại Booking
        </a>
    </div>

    @php
        // Hàm tính số đêm cuối tuần
        function calculateWeekendNights($checkIn, $checkOut) {
            $start = \Carbon\Carbon::parse($checkIn);
            $end = \Carbon\Carbon::parse($checkOut);
            $weekendNights = 0;
            $current = $start->copy();
            
            while ($current->lt($end)) {
                $dayOfWeek = $current->dayOfWeek;
                if ($dayOfWeek == \Carbon\Carbon::FRIDAY || 
                    $dayOfWeek == \Carbon\Carbon::SATURDAY || 
                    $dayOfWeek == \Carbon\Carbon::SUNDAY) {
                    $weekendNights++;
                }
                $current->addDay();
            }
            return $weekendNights;
        }
        
        $totalNights = \Carbon\Carbon::parse($booking->ngay_nhan_phong)->diffInDays(\Carbon\Carbon::parse($booking->ngay_tra_phong));
        $weekendNights = calculateWeekendNights($booking->ngay_nhan_phong, $booking->ngay_tra_phong);
        $weekdayNights = $totalNights - $weekendNights;
        
        // Tính voucher
        $totalRooms = $booking->datPhongItems->count() ?: 1;
        $voucherPerRoom = 0;
        if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
            $voucherPerRoom = (float)$booking->discount_amount / $totalRooms;
        } elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
            $voucherPerRoom = (float)$booking->voucher_discount / $totalRooms;
        }
    @endphp

    @if($lichSuDoiPhong->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <h5 class="text-muted mb-2">Chưa có lịch sử đổi phòng</h5>
            <p class="text-muted">Các thay đổi phòng sẽ được ghi lại tại đây</p>
        </div>
    @else
        <div class="history-timeline">
            @foreach($lichSuDoiPhong as $index => $ls)
                @php
                    $phongCu = \App\Models\Phong::find($ls->phong_cu_id);
                    $phongMoi = \App\Models\Phong::find($ls->phong_moi_id);
                    
                    $item = $booking->datPhongItems()->where('id', $ls->dat_phong_item_id)->first();
                    
                    $extraAdultsCu = 0;
                    $extraChildrenCu = 0;
                    $extraAdultsMoi = 0;
                    $extraChildrenMoi = 0;
                    
                    if ($item) {
                        $extraAdultsMoi = $item->number_adult ?? 0;
                        $extraChildrenMoi = $item->number_child ?? 0;
                    }
                    
                    // TÍNH GIÁ PHÒNG CŨ
// ✅ TÍNH GIÁ PHÒNG CŨ - GIÁ ĐÃ LƯU TRONG LỊCH SỬ LÀ TỔNG CUỐI CÙNG
$totalRoomPriceCu = $ls->gia_cu ?? 0;  // ✅ Đây đã là tổng rồi
$totalRoomPriceCuAfterVoucher = $totalRoomPriceCu - $voucherPerRoom;

// Tính ngược lại các thành phần (để hiển thị)
$giaCuGoc = $phongCu->tong_gia ?? 0;
$extraFeeCu = 0; // Chưa biết chính xác

// Ước tính phụ thu cuối tuần
if ($totalNights > 0) {
    $avgPricePerNightCu = $totalRoomPriceCu / $totalNights;
    $weekendSurchargeCu = ($giaCuGoc * 0.1) * $weekendNights;
} else {
    $avgPricePerNightCu = 0;
    $weekendSurchargeCu = 0;
}
                    
                    // TÍNH GIÁ PHÒNG MỚI
              // ✅ TÍNH GIÁ PHÒNG MỚI - GIÁ ĐÃ LƯU TRONG LỊCH SỬ LÀ TỔNG CUỐI CÙNG
$totalRoomPriceMoi = $ls->gia_moi ?? 0;  // ✅ Đây đã là tổng rồi
$totalRoomPriceMoiAfterVoucher = $totalRoomPriceMoi - $voucherPerRoom;

// Tính ngược lại các thành phần (để hiển thị)
$giaMoiGoc = $phongMoi->tong_gia ?? 0;
$extraFeeMoi = ($extraAdultsMoi * 150000) + ($extraChildrenMoi * 60000);

// Ước tính phụ thu cuối tuần
if ($totalNights > 0) {
    $avgPricePerNightMoi = $totalRoomPriceMoi / $totalNights;
    $weekendSurchargeMoi = ($giaMoiGoc * 0.1) * $weekendNights;
} else {
    $avgPricePerNightMoi = 0;
    $weekendSurchargeMoi = 0;
}

$priceDiff = $totalRoomPriceMoi - $totalRoomPriceCu;  // ✅ So sánh giá gốc (chưa trừ voucher)
                    
                    $badgeClass = 'badge-same';
                    $badgeText = 'Giữ nguyên';
                    $badgeIcon = 'bi-arrow-left-right';
                    $iconBg = 'bg-secondary';
                    
                    if ($ls->loai == 'nang_cap') {
                        $badgeClass = 'badge-upgrade';
                        $badgeText = 'Nâng cấp';
                        $badgeIcon = 'bi-arrow-up-circle-fill';
                        $iconBg = 'bg-success';
                    } elseif ($ls->loai == 'ha_cap') {
                        $badgeClass = 'badge-downgrade';
                        $badgeText = 'Hạ cấp';
                        $badgeIcon = 'bi-arrow-down-circle-fill';
                        $iconBg = 'bg-warning';
                    }
                @endphp

                <div class="timeline-item">
                    <!-- Timeline Icon -->
                    <div class="timeline-icon {{ $iconBg }} text-white">
                        {{ $index + 1 }}
                    </div>

                    <div class="card border-0 shadow-sm">
                        <!-- Card Header -->
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="bi bi-calendar3 me-2 text-primary"></i>
                                        <strong>{{ $ls->created_at->format('d/m/Y') }}</strong>
                                        <span class="text-muted">lúc</span>
                                        <strong>{{ $ls->created_at->format('H:i') }}</strong>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="bi bi-person-badge me-1"></i>
                                        Thực hiện bởi: <strong class="text-dark">{{ $ls->nguoi_thuc_hien }}</strong>
                                    </small>
                                </div>
                                <span class="badge {{ $badgeClass }} badge-custom">
                                    <i class="{{ $badgeIcon }} me-2"></i>
                                    {{ $badgeText }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <!-- PHÒNG CŨ -->
                                <div class="col-lg-5">
                                    <div class="room-card room-card-old h-100 rounded-3 p-4">
                                        <div class="room-header">
                                            <div class="room-icon icon-old">
                                                <i class="bi bi-door-closed-fill"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-danger">Phòng cũ</h6>
                                                <small class="text-muted">Trước khi đổi</small>
                                            </div>
                                        </div>

                                        <h5 class="text-dark mb-1">{{ $phongCu->name ?? 'N/A' }}</h5>
                                        <p class="text-muted small mb-3">
                                            <i class="bi bi-hash"></i> {{ $phongCu->ma_phong ?? '' }}
                                        </p>

                                       <div class="info-row">
    <span class="info-label">
        <i class="bi bi-tag me-1"></i> Giá gốc
    </span>
    <span class="info-value">{{ number_format($giaCuGoc) }}₫</span>
</div>

@if($extraFeeCu > 0)
<div class="info-row">
    <span class="info-label">
        <i class="bi bi-plus-circle me-1"></i> Phụ thu
    </span>
    <span class="info-value text-warning">+{{ number_format($extraFeeCu) }}₫</span>
</div>
@endif

@if($weekendNights > 0 && $weekendSurchargeCu > 0)
<div class="info-row">
    <span class="info-label d-flex align-items-center gap-2">
        <i class="bi bi-calendar-week"></i> Cuối tuần
        <span class="weekend-tag">
            <i class="bi bi-star-fill"></i>
            {{ $weekendNights }} đêm
        </span>
    </span>
    <span class="info-value text-danger">+{{ number_format($weekendSurchargeCu) }}₫</span>
</div>
@endif

@if($voucherPerRoom > 0)
<div class="info-row">
    <span class="info-label">
        <i class="bi bi-ticket-perforated me-1"></i> Voucher
    </span>
    <span class="info-value text-success">-{{ number_format($voucherPerRoom) }}₫</span>
</div>
@endif

<div class="info-row">
    <span class="info-label">
        <i class="bi bi-calculator me-1"></i> <strong>TỔNG PHÒNG</strong>
    </span>
    <span class="price-badge bg-danger text-white">
        {{ number_format($totalRoomPriceCuAfterVoucher) }}₫
    </span>
</div>
                                    </div>
                                </div>

                                <!-- ARROW -->
                                <div class="col-lg-2">
                                    <div class="arrow-separator h-100">
                                        <div class="arrow-icon">
                                            <i class="bi bi-arrow-right fs-4"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- PHÒNG MỚI -->
                                <div class="col-lg-5">
                                    <div class="room-card room-card-new h-100 rounded-3 p-4">
                                        <div class="room-header">
                                            <div class="room-icon icon-new">
                                                <i class="bi bi-door-open-fill"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-success">Phòng mới</h6>
                                                <small class="text-muted">Sau khi đổi</small>
                                            </div>
                                        </div>

                                        <h5 class="text-dark mb-1">{{ $phongMoi->name ?? 'N/A' }}</h5>
                                        <p class="text-muted small mb-3">
                                            <i class="bi bi-hash"></i> {{ $phongMoi->ma_phong ?? '' }}
                                        </p>

                                      <div class="info-row">
    <span class="info-label">
        <i class="bi bi-tag me-1"></i> Giá gốc
    </span>
    <span class="info-value">{{ number_format($giaMoiGoc) }}₫</span>
</div>

@if($extraFeeMoi > 0)
<div class="info-row">
    <span class="info-label">
        <i class="bi bi-plus-circle me-1"></i> Phụ thu
    </span>
    <span class="info-value text-warning">+{{ number_format($extraFeeMoi) }}₫</span>
</div>
@endif

@if($weekendNights > 0 && $weekendSurchargeMoi > 0)
<div class="info-row">
    <span class="info-label d-flex align-items-center gap-2">
        <i class="bi bi-calendar-week"></i> Cuối tuần
        <span class="weekend-tag">
            <i class="bi bi-star-fill"></i>
            {{ $weekendNights }} đêm
        </span>
    </span>
    <span class="info-value text-danger">+{{ number_format($weekendSurchargeMoi) }}₫</span>
</div>
@endif

@if($voucherPerRoom > 0)
<div class="info-row">
    <span class="info-label">
        <i class="bi bi-ticket-perforated me-1"></i> Voucher
    </span>
    <span class="info-value text-success">-{{ number_format($voucherPerRoom) }}₫</span>
</div>
@endif

<div class="info-row">
    <span class="info-label">
        <i class="bi bi-calculator me-1"></i> <strong>TỔNG PHÒNG</strong>
    </span>
    <span class="price-badge bg-success text-white">
        {{ number_format($totalRoomPriceMoiAfterVoucher) }}₫
    </span>
</div>
                                    </div>
                                </div>
                            </div>

                            <!-- SUMMARY BOX -->
                            <div class="summary-box mt-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="summary-item">
                                            <div class="summary-label">Tổng số đêm</div>
                                            <div class="summary-value">
                                                <i class="bi bi-moon-stars me-2"></i>{{ $ls->so_dem }}
                                            </div>
                                            <small style="opacity: 0.8;">
                                                ({{ $weekdayNights }} thường + {{ $weekendNights }} cuối tuần)
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="summary-item border-start border-white border-opacity-25">
                                            <div class="summary-label">Chênh lệch</div>
                                            <div class="summary-value {{ $priceDiff >= 0 ? 'text-warning' : '' }}">
                                                {{ $priceDiff >= 0 ? '+' : '' }}{{ number_format($priceDiff) }}₫
                                            </div>
                                            <small style="opacity: 0.8;">
                                                {{ $priceDiff >= 0 ? 'Tăng' : 'Giảm' }} {{ number_format(abs($priceDiff)) }}₫
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="summary-item border-start border-white border-opacity-25">
                                            <div class="summary-label">Tổng Booking</div>
                                            <div class="summary-value">
                                                {{ number_format($booking->tong_tien) }}₫
                                            </div>
                                            <small style="opacity: 0.8;">Tại thời điểm này</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="summary-item border-start border-white border-opacity-25">
                                            <div class="summary-label">Trạng thái</div>
                                            <div class="summary-value">
                                                <i class="{{ $badgeIcon }} me-2"></i>{{ $badgeText }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection