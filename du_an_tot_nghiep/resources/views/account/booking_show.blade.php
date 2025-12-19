@extends('layouts.app')

@section('title', 'Chi tiết đặt phòng')

@section('content')
    @php
        // Format ngày tháng tiếng Việt
        $formatDateVi = function ($date, $format = 'D, d M Y') {
            if (!$date) return '';
            
            $carbon = \Carbon\Carbon::parse($date);
            
            $days = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
            $months = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                       'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
            
            if ($format === 'D, d M Y') {
                $dayName = $days[$carbon->dayOfWeek];
                $monthName = $months[$carbon->month];
                return $dayName . ', ' . $carbon->format('d') . ' ' . $monthName . ' ' . $carbon->format('Y');
            } elseif ($format === 'd M Y H:i') {
                $monthName = $months[$carbon->month];
                return $carbon->format('d') . ' ' . $monthName . ' ' . $carbon->format('Y H:i');
            }
            
            return $carbon->format($format);
        };

        // Calculate countdown using actual check-in time (14:00)
        $checkInDateTime = \Carbon\Carbon::parse($booking->ngay_nhan_phong)->setTime(14, 0, 0);
        $now = \Carbon\Carbon::now();
        $daysUntilCheckIn = (int) $now->diffInDays($checkInDateTime, false);
        $hoursUntilCheckIn = (int) $now->diffInHours($checkInDateTime, false);
        
        // Calculate refund if cancelled now
        $totalAmount = $booking->tong_tien ?? ($booking->snapshot_total ?? 0);
        $paidAmount = $booking->deposit_amount ?? 0;
        
        // CRITICAL: Subtract unused voucher values (same logic as backend)
        $unusedVoucherValue = 0;
        $unusedVouchers = \App\Models\Voucher::where('code', 'LIKE', 'DOWNGRADE%')
            ->whereHas('users', function($q) use ($booking) {
                $q->where('user_id', $booking->nguoi_dung_id);
            })
            ->where('active', true)
            ->where('end_date', '>=', now())
            ->get();
            
        foreach ($unusedVouchers as $voucher) {
            $isUsed = \App\Models\VoucherUsage::where('voucher_id', $voucher->id)
                ->where('nguoi_dung_id', $booking->nguoi_dung_id)
                ->exists();
                
            if (!$isUsed) {
                $unusedVoucherValue += $voucher->value;
            }
        }
        
        // Calculate EFFECTIVE payment (same as backend)
        $effectivePaidAmount = $paidAmount - $unusedVoucherValue;
        
        $depositType = 50;
        if ($effectivePaidAmount > 0 && $totalAmount > 0) {
            $percentage = ($effectivePaidAmount / $totalAmount) * 100;
            $depositType = ($percentage >= 95) ? 100 : 50;
        }
        
        $refundPercentage = 0;
        if ($depositType == 100) {
            if ($daysUntilCheckIn >= 7) $refundPercentage = 90;
            elseif ($daysUntilCheckIn >= 3) $refundPercentage = 60;
            elseif ($daysUntilCheckIn >= 1) $refundPercentage = 40;
            else $refundPercentage = 20;
        } else {
            if ($daysUntilCheckIn >= 7) $refundPercentage = 100;
            elseif ($daysUntilCheckIn >= 3) $refundPercentage = 70;
            elseif ($daysUntilCheckIn >= 1) $refundPercentage = 30;
            else $refundPercentage = 0;
        }
        $refundAmount = ($paidAmount * $refundPercentage) / 100;
    @endphp
    
    <section class="py-3">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    {{-- Booking Header --}}
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-0 p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            {{-- Left: Booking Info --}}
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-receipt fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-dark">{{ $booking->ma_tham_chieu }}</h5>
                                    <div class="text-muted small">Tạo lúc: {{ $formatDateVi($booking->created_at, 'd M Y H:i') }}</div>
                                </div>
                            </div>

                            {{-- Right: Status + Actions --}}
                            <div class="text-end d-flex flex-column align-items-end gap-2">
                                @php
                                    $statusMap = [
                                        'dang_cho' => ['label' => 'Đang chờ', 'class' => 'bg-warning text-dark border-warning'],
                                        'dang_cho_xac_nhan' => ['label' => 'Đang chờ', 'class' => 'bg-warning text-dark border-warning'],
                                        'dang_su_dung' => ['label' => 'Đang Sử Dụng', 'class' => 'bg-success text-white border-warning'],
                                        'da_xac_nhan' => ['label' => 'Đã đặt cọc', 'class' => 'bg-primary text-white border-primary'],
                                        'da_huy' => ['label' => 'Đã hủy', 'class' => 'bg-danger text-white border-danger'],
                                        'hoan_thanh' => ['label' => 'Hoàn thành', 'class' => 'bg-success text-white border-success'],
                                    ];
                                    $s = $statusMap[$booking->trang_thai] ?? [
                                        'label' => ucfirst(str_replace('_', ' ', $booking->trang_thai)),
                                        'class' => 'bg-secondary text-white border-secondary',
                                    ];
                                @endphp

                                {{-- Status Badge --}}
                                <span class="badge {{ $s['class'] }} fw-bold" style="font-size:18px; padding:0.6rem 1rem; line-height:1;">{{ $s['label'] }}</span>

                                {{-- Action Buttons --}}
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="{{ route('account.booking.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                                        <i class="bi bi-arrow-left me-1"></i> Quay lại
                                    </a>
                                    
                                    <button onclick="window.print()" class="btn btn-outline-primary btn-sm px-3">
                                        <i class="bi bi-printer me-1"></i> In xác nhận
                                    </button>

                                    @if(in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan']) && $daysUntilCheckIn >= 1)
                                        <button type="button" class="btn btn-outline-primary btn-sm px-3" 
                                                data-bs-toggle="modal" data-bs-target="#changeRoomModal"
                                                title="Đổi phòng trước 24h check-in. Phòng đắt hơn: thanh toán chênh lệch. Phòng rẻ hơn: nhận voucher.">
                                            <i class="bi bi-arrow-left-right me-1"></i> Đổi phòng
                                        </button>
                                    @endif

                                    @if(in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan']))
                                        <button type="button" class="btn btn-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                            <i class="bi bi-x-circle me-1"></i> Hủy phòng
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Booking Body --}}
                        <div class="card-body p-4">
                            
                            {{-- Countdown Timer --}}
                            @if(in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan']) && $daysUntilCheckIn > 0)
                                <div class="alert alert-info border-0 mb-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-2">
                                                <i class="bi bi-clock-history me-2"></i>
                                                Thời gian đến ngày nhận phòng
                                            </h6>
                                            <div class="d-flex gap-3 flex-wrap">
                                                <div>
                                                    <div class="fs-3 fw-bold text-primary">{{ $daysUntilCheckIn }}</div>
                                                    <small class="text-muted">ngày</small>
                                                </div>
                                                <div>
                                                    <div class="fs-3 fw-bold text-primary">{{ $hoursUntilCheckIn % 24 }}</div>
                                                    <small class="text-muted">giờ</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mt-3 mt-md-0">
                                            @if($daysUntilCheckIn >= 7)
                                                <div class="alert alert-success mb-0 py-2">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    <small>Hủy miễn phí còn {{ $daysUntilCheckIn }} ngày</small>
                                                </div>
                                            @elseif($daysUntilCheckIn >= 1)
                                                <div class="alert alert-warning mb-0 py-2">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    <small>Hủy phòng sẽ bị phí</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Timeline Status --}}
                            <div class="mb-4">
                                <h6 class="mb-3"><i class="bi bi-activity me-2 text-primary"></i> Tiến trình đặt phòng</h6>
                                <div class="booking-timeline">
                                    <div class="timeline-step {{ in_array($booking->trang_thai, ['dang_cho', 'dang_cho_xac_nhan', 'da_xac_nhan', 'dang_su_dung', 'hoan_thanh']) ? 'completed' : '' }}">
                                        <div class="timeline-marker">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>Đặt phòng</h6>
                                            <small class="text-muted">{{ $formatDateVi($booking->created_at, 'd M Y H:i') }}</small>
                                        </div>
                                    </div>
                                    
                                    <div class="timeline-step {{ in_array($booking->trang_thai, ['da_xac_nhan', 'dang_su_dung', 'hoan_thanh']) ? 'completed' : ($booking->trang_thai === 'da_huy' ? 'cancelled' : '') }}">
                                        <div class="timeline-marker">
                                            <i class="bi {{ in_array($booking->trang_thai, ['da_xac_nhan', 'dang_su_dung', 'hoan_thanh']) ? 'bi-check-circle-fill' : ($booking->trang_thai === 'da_huy' ? 'bi-x-circle-fill' : 'bi-circle') }}"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>{{ $booking->trang_thai === 'da_huy' ? 'Đã hủy' : 'Xác nhận' }}</h6>
                                            <small class="text-muted">
                                                {{ in_array($booking->trang_thai, ['da_xac_nhan', 'dang_su_dung', 'hoan_thanh', 'da_huy']) ? $formatDateVi($booking->updated_at, 'd M Y H:i') : 'Chờ xác nhận' }}
                                            </small>
                                        </div>
                                    </div>
                                    
                                    @if($booking->trang_thai !== 'da_huy')
                                        <div class="timeline-step {{ in_array($booking->trang_thai, ['dang_su_dung', 'hoan_thanh']) ? 'completed' : '' }}">
                                            <div class="timeline-marker">
                                                <i class="bi {{ in_array($booking->trang_thai, ['dang_su_dung', 'hoan_thanh']) ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <h6>Nhận Phòng</h6>
                                                <small class="text-muted">{{ $formatDateVi($booking->ngay_nhan_phong, 'd M Y') }} 14:00</small>
                                            </div>
                                        </div>
                                        
                                        <div class="timeline-step {{ $booking->trang_thai === 'hoan_thanh' ? 'completed' : '' }}">
                                            <div class="timeline-marker">
                                                <i class="bi {{ $booking->trang_thai === 'hoan_thanh' ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <h6>Trả phòng</h6>
                                                <small class="text-muted">{{ $formatDateVi($booking->ngay_tra_phong, 'd M Y') }} 12:00</small>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <hr class="my-4">

                            {{-- Dates & Total --}}
                            <div class="row mb-4 g-3">
                                <div class="col-md-4">
                                    <div class="text-center text-md-start">
                                        <div class="text-muted small mb-1"><i class="bi bi-calendar-check me-1"></i> Nhận phòng</div>
                                        <div class="h6 mb-0 fw-bold">{{ $formatDateVi($booking->ngay_nhan_phong, 'D, d M Y') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center text-md-start">
                                        <div class="text-muted small mb-1"><i class="bi bi-calendar-x me-1"></i> Trả phòng</div>
                                        <div class="h6 mb-0 fw-bold">{{ $formatDateVi($booking->ngay_tra_phong, 'D, d M Y') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center text-md-end">
                                        <div class="text-muted small mb-1"><i class="bi bi-currency-dollar me-1"></i> Tổng tiền</div>
                                        @if(($booking->voucher_discount ?? 0) > 0)
                                            @php
                                                $originalTotal = $booking->tong_tien + $booking->voucher_discount;
                                            @endphp
                                            <div class="small text-muted text-decoration-line-through">{{ number_format($originalTotal, 0, ',', '.') }}đ</div>
                                        @endif
                                        <div class="h5 mb-0 fw-bold text-primary">{{ number_format($booking->tong_tien ?? ($booking->snapshot_total ?? 0), 0, ',', '.') }} VND</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Information --}}
                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="bi bi-credit-card me-2 text-success"></i> Thông tin thanh toán</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Hình thức:</span>
                                                <strong>{{ $depositType == 100 ? 'Thanh toán 100%' : 'Đặt cọc 50%' }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Đã thanh toán:</span>
                                                <strong class="text-success">{{ number_format($paidAmount, 0, ',', '.') }} ₫</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex justify-content-between">
                                                @php
                                                    $remaining = $totalAmount - $paidAmount;
                                                    
                                                    // Query actual downgrade voucher if user overpaid
                                                    $downgradeVoucher = null;
                                                    if ($remaining < 0) {
                                                        // Get latest ACTIVE downgrade voucher for this user
                                                        // We already know remaining < 0, so user has downgraded
                                                        $downgradeVoucher = \App\Models\Voucher::where('code', 'LIKE', 'DOWNGRADE%')
                                                            ->whereHas('users', function($q) use ($booking) {
                                                                $q->where('user_id', $booking->nguoi_dung_id);
                                                            })
                                                            ->where('active', true)
                                                            ->where('end_date', '>=', now())
                                                            ->orderBy('created_at', 'desc')
                                                            ->first();
                                                    }
                                                @endphp
                                                
                                                @if($remaining < 0 && $downgradeVoucher)
                                                    {{-- User đã downgrade và nhận voucher --}}
                                                    <span class="text-muted">
                                                        <i class="bi bi-gift me-1"></i>Voucher hoàn tiền:
                                                    </span>
                                                    <strong class="text-success">
                                                        {{ number_format($downgradeVoucher->value, 0, ',', '.') }} ₫
                                                    </strong>
                                                @elseif($remaining > 0)
                                                    {{-- Còn nợ --}}
                                                    <span class="text-muted">Còn lại:</span>
                                                    <strong class="text-danger">{{ number_format($remaining, 0, ',', '.') }} ₫</strong>
                                                @else
                                                    {{-- Đã trả đủ --}}
                                                    <span class="text-muted">Còn lại:</span>
                                                    <strong class="text-success">
                                                        <i class="bi bi-check-circle me-1"></i>0 ₫
                                                    </strong>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Voucher Used --}}
                                    @if($booking->voucherUsages && $booking->voucherUsages->count() > 0)
                                        <hr class="my-3">
                                        <div>
                                            <small class="text-muted d-block mb-2"><i class="bi bi-ticket-perforated me-1"></i> Voucher đã sử dụng:</small>
                                            @foreach($booking->voucherUsages as $usage)
                                                <div class="badge bg-success me-2">
                                                    {{ $usage->voucher->code ?? 'N/A' }}
                                                    @if($usage->discount_applied)
                                                        - Giảm {{ number_format($usage->discount_applied, 0, ',', '.') }} ₫
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Cancelled Rooms Info (from RefundRequest) --}}
                                    @php
                                        // Lấy các yêu cầu hoàn tiền từ hủy phòng đơn lẻ
                                        $cancelledRoomRefunds = \App\Models\RefundRequest::where('dat_phong_id', $booking->id)
                                            ->where('refund_type', 'single_room')
                                            ->orderBy('created_at', 'desc')
                                            ->get();
                                        $totalCancelledRefund = $cancelledRoomRefunds->sum('amount');
                                    @endphp
                                    @if($cancelledRoomRefunds->count() > 0)
                                        <hr class="my-3">
                                        <div>
                                            <small class="text-muted d-block mb-2">
                                                <i class="bi bi-x-circle me-1 text-danger"></i> Phòng đã hủy ({{ $cancelledRoomRefunds->count() }}):
                                            </small>
                                            @foreach($cancelledRoomRefunds as $refund)
                                                @php
                                                    $refundStatus = $refund->status;
                                                    $statusLabel = match($refundStatus) {
                                                        'pending' => 'Chờ xử lý',
                                                        'approved' => 'Đã duyệt',
                                                        'completed' => 'Đã hoàn',
                                                        'rejected' => 'Từ chối',
                                                        default => 'Không có'
                                                    };
                                                    $statusBadge = match($refundStatus) {
                                                        'pending' => 'bg-warning text-dark',
                                                        'approved' => 'bg-info',
                                                        'completed' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    // Lấy tên phòng từ admin_note (format: "Hủy phòng: Tên phòng | ...")
                                                    $roomNameFromNote = 'Phòng đã hủy';
                                                    if ($refund->admin_note) {
                                                        preg_match('/Hủy phòng:\s*([^|]+)/', $refund->admin_note, $matches);
                                                        $roomNameFromNote = trim($matches[1] ?? 'Phòng đã hủy');
                                                    }
                                                    // Lấy URL ảnh chứng minh hoàn tiền
                                                    $proofImageUrl = $refund->proof_image_url;
                                                @endphp
                                                <div class="bg-danger bg-opacity-10 rounded px-2 py-2 mb-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center">
                                                            <span class="small text-decoration-line-through text-muted me-2">
                                                                {{ $roomNameFromNote }}
                                                            </span>
                                                            <span class="badge {{ $statusBadge }} small">{{ $statusLabel }}</span>
                                                        </div>
                                                        <span class="small {{ $refundStatus === 'completed' ? 'text-success fw-semibold' : 'text-muted' }}">
                                                            {{ number_format($refund->amount, 0, ',', '.') }}đ ({{ $refund->percentage ?? 0 }}%)
                                                        </span>
                                                    </div>
                                                    
                                                    {{-- Hiển thị ảnh chứng minh hoàn tiền khi đã hoàn thành --}}
                                                    @if($refundStatus === 'completed' && $proofImageUrl)
                                                        <div class="mt-2 pt-2 border-top border-danger border-opacity-25">
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                                <small class="text-success fw-semibold me-2">Đã hoàn tiền:</small>
                                                                <a href="{{ $proofImageUrl }}" 
                                                                   target="_blank" 
                                                                   class="btn btn-sm btn-outline-success py-0 px-2"
                                                                   title="Xem ảnh chứng từ hoàn tiền">
                                                                    <i class="bi bi-image me-1"></i>Xem chứng từ
                                                                </a>
                                                            </div>
                                                            @if($refund->processed_at)
                                                                <small class="text-muted d-block mt-1">
                                                                    <i class="bi bi-calendar-check me-1"></i>
                                                                    Hoàn ngày: {{ $refund->processed_at->format('d/m/Y H:i') }}
                                                                </small>
                                                            @endif
                                                        </div>
                                                    @elseif($refundStatus === 'rejected')
                                                        <div class="mt-2 pt-2 border-top border-danger border-opacity-25">
                                                            <small class="text-danger">
                                                                <i class="bi bi-x-circle me-1"></i>
                                                                Yêu cầu hoàn tiền đã bị từ chối.
                                                                @if($refund->admin_note && !str_starts_with($refund->admin_note, 'Hủy phòng:'))
                                                                    Lý do: {{ $refund->admin_note }}
                                                                @endif
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($totalCancelledRefund > 0)
                                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                                    <span class="small fw-semibold">Tổng hoàn từ phòng đã hủy:</span>
                                                    <span class="text-success fw-bold">{{ number_format($totalCancelledRefund, 0, ',', '.') }} ₫</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Rooms Table --}}
                            <div class="mb-4">
                                @php
                                    // Tất cả datPhongItems còn lại đều active (cancelled items đã bị xóa)
                                    $activeRoomsCount = $booking->datPhongItems ? $booking->datPhongItems->count() : 0;
                                    $canCancelIndividualRoom = $activeRoomsCount > 1 && in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan']);
                                @endphp
                                
                                <h6 class="mb-3 d-flex align-items-center">
                                    <i class="bi bi-door-open-fill me-2 text-primary"></i> Phòng
                                    @if($activeRoomsCount > 1)
                                        <span class="badge bg-info ms-2">{{ $activeRoomsCount }} phòng</span>
                                    @endif
                                </h6>
                                @if ($booking->datPhongItems && $booking->datPhongItems->count())
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Phòng</th>
                                                    <th class="text-end">Giá/Đêm</th>
                                                    <th class="text-end">Số đêm</th>
                                                    <th class="text-end">Tổng tiền</th>
                                                    @if($canCancelIndividualRoom)
                                                        <th class="text-center" style="width: 100px;">Thao tác</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($booking->datPhongItems as $it)
                                                    @php
                                                        $roomName = $it->phong->name ?? ($it->loai_phong->name ?? 'Phòng ' . ($it->phong_id ?? 'N/A'));
                                                        $pricePer = $it->gia_tren_dem ?? 0;  // Post-voucher price
                                                        $nights = $it->so_dem ?? 1;
                                                        $qty = $it->so_luong ?? 1;
                                                        $subtotal = $pricePer * $nights * $qty;
                                                        
                                                        // Voucher allocated to this room
                                                        $voucherForThisRoom = $it->voucher_allocated ?? 0;
                                                        $hasVoucherForRoom = $voucherForThisRoom > 0;
                                                        
                                                        // Calculate original price by adding back the voucher
                                                        $originalPricePerNight = $hasVoucherForRoom 
                                                            ? ($pricePer + ($voucherForThisRoom / max(1, $nights)))
                                                            : $pricePer;
                                                        $originalSubtotal = $hasVoucherForRoom
                                                            ? ($subtotal + $voucherForThisRoom)
                                                            : $subtotal;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-door-closed-fill text-muted me-2"></i>
                                                                <div>
                                                                    <strong class="text-dark">{{ $roomName }}</strong>
                                                                    <div class="small text-muted">{{ $qty }} phòng</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            @if($hasVoucherForRoom)
                                                                <small class="text-muted text-decoration-line-through">{{ number_format($originalPricePerNight, 0, ',', '.') }}đ</small><br>
                                                                <span class="text-success fw-semibold">{{ number_format($pricePer, 0, ',', '.') }} VND</span>
                                                            @else
                                                                <span class="text-muted small">{{ number_format($pricePer, 0, ',', '.') }} VND</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-semibold">{{ $nights }}</td>
                                                        <td class="text-end">
                                                            @if($hasVoucherForRoom)
                                                                <small class="text-muted text-decoration-line-through">{{ number_format($originalSubtotal, 0, ',', '.') }}đ</small><br>
                                                                <span class="fw-semibold text-primary">{{ number_format($subtotal, 0, ',', '.') }} VND</span>
                                                            @else
                                                                <span class="fw-semibold text-primary">{{ number_format($subtotal, 0, ',', '.') }} VND</span>
                                                            @endif
                                                        </td>
                                                        @if($canCancelIndividualRoom)
                                                            <td class="text-center">
                                                                <button type="button" 
                                                                        class="btn btn-outline-danger btn-sm" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#cancelRoomModal{{ $it->id }}"
                                                                        title="Hủy phòng này">
                                                                    <i class="bi bi-x-circle"></i>
                                                                </button>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info d-flex align-items-center py-2">
                                        <i class="bi bi-info-circle me-2"></i> Chưa có thông tin phòng được ghi nhận.
                                    </div>
                                @endif
                            </div>

                            {{-- Addons --}}
                            @if ($booking->datPhongAddons && $booking->datPhongAddons->count())
                                <div class="mb-4">
                                    <h6 class="mb-3 d-flex align-items-center"><i class="bi bi-plus-circle-fill me-2 text-info"></i> Dịch vụ bổ sung</h6>
                                    <div class="row g-2">
                                        @foreach ($booking->datPhongAddons as $a)
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-tag-fill text-info me-2"></i>
                                                        <span class="fw-semibold">{{ $a->name ?? 'Dịch vụ' }}</span>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-bold text-primary">{{ number_format($a->price ?? 0, 0, ',', '.') }} VND</div>
                                                        <small class="text-muted">Số lượng: {{ $a->qty ?? 1 }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Contact Information --}}
                            <div class="mb-4">
                                <h6 class="mb-3"><i class="bi bi-person-circle me-2 text-primary"></i> Thông tin liên hệ</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="text-muted small mb-1">Tên người đặt</div>
                                        <div class="fw-semibold">{{ $booking->contact_name ?? ($user->name ?? 'N/A') }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-muted small mb-1">Email</div>
                                        <div class="fw-semibold">{{ $booking->contact_email ?? ($user->email ?? 'N/A') }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-muted small mb-1">Số điện thoại</div>
                                        <div class="fw-semibold">{{ $booking->contact_phone ?? ($user->phone ?? 'N/A') }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- CCCD Information --}}
                            @php
                                $cccdList = $meta['checkin_cccd_list'] ?? [];
                                $hasCCCDList = !empty($cccdList) && is_array($cccdList) && count($cccdList) > 0;
                                
                                // Backward compatibility
                                $cccdFront = $meta['checkin_cccd_front'] ?? null;
                                $cccdBack = $meta['checkin_cccd_back'] ?? null;
                                $cccd = $meta['checkin_cccd'] ?? null;
                                $hasFront = $cccdFront && \Illuminate\Support\Facades\Storage::disk('public')->exists($cccdFront);
                                $hasBack = $cccdBack && \Illuminate\Support\Facades\Storage::disk('public')->exists($cccdBack);
                                $isOldImage = $cccd && !$hasFront && !$hasBack && \Illuminate\Support\Facades\Storage::disk('public')->exists($cccd);
                            @endphp
                            @if($hasCCCDList || $hasFront || $hasBack || $isOldImage || (!empty($cccd) && !$isOldImage))
                            <hr class="my-4">
                            <h6 class="mb-3 d-flex align-items-center">
                                <i class="bi bi-card-text me-2 text-info"></i> Thông tin CCCD/CMND
                                @if($hasCCCDList)
                                    <span class="badge bg-info ms-2">{{ count($cccdList) }} người</span>
                                @endif
                            </h6>
                            <div class="alert alert-info border-0 p-3">
                                @if ($hasCCCDList)
                                    {{-- Hiển thị tất cả CCCD trong danh sách --}}
                                    <div class="row g-3">
                                        @foreach($cccdList as $index => $cccdItem)
                                            <div class="col-12 col-md-6 col-lg-4">
                                                <div class="card border-primary h-100">
                                                    <div class="card-header bg-light text-center">
                                                        <strong>Người {{ $index + 1 }}</strong>
                                                    </div>
                                                    <div class="card-body p-3">
                                                        @if(!empty($cccdItem['front']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($cccdItem['front']))
                                                            <div class="mb-3">
                                                                <div class="text-center">
                                                                    <small class="text-muted d-block mb-2">Mặt trước</small>
                                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($cccdItem['front']) }}" 
                                                                         alt="Mặt trước CCCD người {{ $index + 1 }}" 
                                                                         class="img-thumbnail w-100" 
                                                                         style="max-height: 300px; cursor: pointer; object-fit: contain;"
                                                                         onclick="window.open(this.src, '_blank')">
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if(!empty($cccdItem['back']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($cccdItem['back']))
                                                            <div>
                                                                <div class="text-center">
                                                                    <small class="text-muted d-block mb-2">Mặt sau</small>
                                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($cccdItem['back']) }}" 
                                                                         alt="Mặt sau CCCD người {{ $index + 1 }}" 
                                                                         class="img-thumbnail w-100" 
                                                                         style="max-height: 300px; cursor: pointer; object-fit: contain;"
                                                                         onclick="window.open(this.src, '_blank')">
                                                                </div>
                                                            </div>
                                                        @endif
                                                        <small class="text-muted d-block mt-2 text-center">Click để xem ảnh lớn</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif ($hasFront || $hasBack)
                                    {{-- Backward compatibility: hiển thị CCCD cũ --}}
                                    <div class="row g-3">
                                        @if ($hasFront)
                                            <div class="col-md-6">
                                                <div class="text-center">
                                                    <small class="text-muted d-block mb-2">Mặt trước</small>
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($cccdFront) }}" 
                                                         alt="Mặt trước CCCD" 
                                                         class="img-thumbnail w-100" 
                                                         style="max-height: 400px; cursor: pointer; object-fit: contain;"
                                                         onclick="window.open(this.src, '_blank')">
                                                    <br><small class="text-muted mt-2 d-block">Click để xem ảnh lớn</small>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($hasBack)
                                            <div class="col-md-6">
                                                <div class="text-center">
                                                    <small class="text-muted d-block mb-2">Mặt sau</small>
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($cccdBack) }}" 
                                                         alt="Mặt sau CCCD" 
                                                         class="img-thumbnail w-100" 
                                                         style="max-height: 400px; cursor: pointer; object-fit: contain;"
                                                         onclick="window.open(this.src, '_blank')">
                                                    <br><small class="text-muted mt-2 d-block">Click để xem ảnh lớn</small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @elseif ($isOldImage)
                                    {{-- Backward compatibility: hiển thị ảnh cũ --}}
                                    <div class="text-center">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($cccd) }}" 
                                             alt="Ảnh CCCD" 
                                             class="img-thumbnail" 
                                             style="max-width: 500px; max-height: 400px; cursor: pointer;"
                                             onclick="window.open(this.src, '_blank')">
                                        <br><small class="text-muted mt-2 d-block">Click để xem ảnh lớn</small>
                                    </div>
                                @else
                                    {{-- Backward compatibility: nếu là text cũ --}}
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-card-text fs-4 me-3"></i>
                                        <div>
                                            <div class="small text-muted mb-1">Số CCCD/CMND</div>
                                            <div class="h6 mb-0 fw-bold">{{ $cccd }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @endif

                            {{-- Overview --}}
                            <hr class="my-4">
                            <h6 class="mb-3 d-flex align-items-center"><i class="bi bi-list-check me-2 text-success"></i> Tổng quan</h6>
                            <div class="row g-3 mb-4">
                                @php
                                    $adults = $meta['adults_input'] ?? ($meta['computed_adults'] ?? ($meta['guests_adults'] ?? 0));
                                    $children = $meta['children_input'] ?? ($meta['chargeable_children'] ?? 0);
                                    $nights = $meta['nights'] ?? ($booking->datPhongItems->first()->so_dem ?? 1);
                                    $total = $booking->tong_tien ?? ($booking->snapshot_total ?? 0);
                                @endphp
                                <div class="col-md-3 col-6">
                                    <div class="card border-0 bg-light h-100 text-center p-3">
                                        <i class="bi bi-people-fill text-primary fs-2 mb-2 d-block"></i>
                                        <div class="small text-muted">Người lớn</div>
                                        <div class="h6 fw-bold text-dark">{{ $adults }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="card border-0 bg-light h-100 text-center p-3">
                                        <i class="bi bi-person-bounding-box text-info fs-2 mb-2 d-block"></i>
                                        <div class="small text-muted">Trẻ em</div>
                                        <div class="h6 fw-bold text-dark">{{ $children }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="card border-0 bg-light h-100 text-center p-3">
                                        <i class="bi bi-calendar3-event text-warning fs-2 mb-2 d-block"></i>
                                        <div class="small text-muted">Số đêm</div>
                                        <div class="h6 fw-bold text-dark">{{ $nights }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="card border-0 bg-light h-100 text-center p-3">
                                        <i class="bi bi-currency-dollar text-success fs-2 mb-2 d-block"></i>
                                        <div class="small text-muted">Tổng tiền</div>
                                        <div class="h6 fw-bold text-primary">{{ number_format($total, 0, ',', '.') }} VND</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Refund Policy - Updated with accurate calculation --}}
                            @if(in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan']))
                                <hr class="my-4">
                                <h6 class="mb-3 d-flex align-items-center"><i class="bi bi-shield-check me-2 text-secondary"></i> Chính sách hoàn tiền</h6>
                                <div class="alert alert-light border">
                                    <div class="mb-3">
                                        <strong>Nếu hủy bây giờ, bạn sẽ được hoàn:</strong>
                                        <div class="fs-4 text-success fw-bold mt-2">
                                            {{ number_format($refundAmount, 0, ',', '.') }} ₫ 
                                            <span class="text-muted fs-6">({{ $refundPercentage }}%)</span>
                                        </div>
                                    </div>
                                    
                                    <div class="accordion" id="policyAccordion">
                                        <div class="accordion-item border">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#policyDetails">
                                                    <i class="bi bi-file-text me-2"></i>
                                                    Xem bảng chính sách chi tiết
                                                </button>
                                            </h2>
                                            <div id="policyDetails" class="accordion-collapse collapse">
                                                <div class="accordion-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6 class="text-primary mb-3">Đặt cọc 50%</h6>
                                                            <table class="table table-sm table-bordered">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Thời gian hủy</th>
                                                                        <th>Hoàn lại</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="small">
                                                                    <tr>
                                                                        <td>≥ 7 ngày trước</td>
                                                                        <td><strong>100%</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>3-6 ngày trước</td>
                                                                        <td><strong>70%</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>1-2 ngày trước</td>
                                                                        <td><strong>30%</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>< 24 giờ</td>
                                                                        <td><strong>0%</strong></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="text-success mb-3">Thanh toán 100%</h6>
                                                            <table class="table table-sm table-bordered">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Thời gian hủy</th>
                                                                        <th>Hoàn lại</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="small">
                                                                    <tr>
                                                                        <td>≥ 7 ngày trước</td>
                                                                        <td><strong>90%</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>3-6 ngày trước</td>
                                                                        <td><strong>60%</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>1-2 ngày trước</td>
                                                                        <td><strong>40%</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>< 24 giờ</td>
                                                                        <td><strong>20%</strong></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="alert alert-info alert-sm mt-3 mb-0">
                                                        <small>
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            <strong>Lưu ý:</strong> Khách hàng thanh toán 100% được ưu đãi tỷ lệ hoàn tiền cao hơn khi hủy phòng.
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Cancel Booking Modal --}}
    @if(in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan']))
        <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-danger bg-opacity-10 border-0">
                        <h5 class="modal-title text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Xác nhận hủy đặt phòng
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border mb-3">
                            <strong>Mã đặt phòng:</strong> {{ $booking->ma_tham_chieu }}<br>
                            <strong>Số tiền được hoàn:</strong> <span class="text-success fs-5">{{ number_format($refundAmount, 0, ',', '.') }} ₫</span>
                        </div>
                        <p>Bạn có chắc chắn muốn hủy đặt phòng này không?</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-arrow-left me-1"></i>Quay lại
                        </button>
                        <form action="{{ route('account.booking.cancel', $booking->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-check-circle me-1"></i>Xác nhận hủy phòng
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Cancel Individual Room Modals --}}
    @if(in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan']) && $booking->datPhongItems)
        @php
            // Tất cả items còn lại đều active (cancelled items đã bị xóa)
            $activeRoomsForModal = $booking->datPhongItems;
        @endphp
        
        @if($activeRoomsForModal->count() > 1)
            @foreach($booking->datPhongItems as $roomItem)
                    @php
                        // Calculate estimated refund for this room
                        $roomName = $roomItem->phong->name ?? ($roomItem->loaiPhong->name ?? 'Phòng #' . $roomItem->id);
                        $roomPriceItem = ($roomItem->gia_tren_dem ?? 0) * ($roomItem->so_dem ?? 1) * ($roomItem->so_luong ?? 1);
                        $totalBooking = $booking->tong_tien ?? 1;
                        $roomProportionItem = $totalBooking > 0 ? ($roomPriceItem / $totalBooking) : 0;
                        $roomDepositItem = ($booking->deposit_amount ?? 0) * $roomProportionItem;
                        $roomRefundEstimate = $roomDepositItem * ($refundPercentage / 100);
                    @endphp
                    <div class="modal fade" id="cancelRoomModal{{ $roomItem->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-warning bg-opacity-10 border-0">
                                    <h5 class="modal-title text-warning">
                                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                                        Hủy phòng đơn lẻ
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="alert alert-warning border-0 mb-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Lưu ý:</strong> Bạn đang hủy 1 phòng trong booking có nhiều phòng. Các phòng còn lại vẫn được giữ nguyên.
                                    </div>
                                    
                                    <div class="card border-secondary mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title d-flex align-items-center">
                                                <i class="bi bi-door-closed-fill text-primary me-2"></i>
                                                {{ $roomName }}
                                            </h6>
                                            <div class="row g-2 small">
                                                <div class="col-6">
                                                    <span class="text-muted">Giá phòng:</span>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <strong>{{ number_format($roomPriceItem, 0, ',', '.') }} ₫</strong>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted">Phần cọc tương ứng:</span>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <strong>{{ number_format($roomDepositItem, 0, ',', '.') }} ₫</strong>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted">Hoàn ước tính ({{ $refundPercentage }}%):</span>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <strong class="text-success">{{ number_format($roomRefundEstimate, 0, ',', '.') }} ₫</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-question-circle me-1"></i>
                                        Số tiền hoàn thực tế sẽ được tính toán chính xác tại thời điểm hủy dựa trên chính sách hoàn tiền.
                                    </p>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-arrow-left me-1"></i>Quay lại
                                    </button>
                                    <form action="{{ route('account.booking.cancel-room-item', ['id' => $booking->id, 'itemId' => $roomItem->id]) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning">
                                            <i class="bi bi-x-circle me-1"></i>Xác nhận hủy phòng này
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
            @endforeach
        @endif
    @endif

    {{-- Room Change Modal --}}
    @if(in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan']) && $daysUntilCheckIn >= 1)
        <div class="modal fade" id="changeRoomModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                <div class="modal-content border-0 shadow-lg">
                    {{-- Header with Gradient --}}
                    <div class="modal-header border-0 py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div>
                            <h5 class="modal-title text-white fw-bold mb-1">
                                <i class="bi bi-arrow-left-right me-2"></i>
                                Đổi Phòng
                            </h5>
                            <p class="text-white-50 small mb-0">{{ $booking->ma_tham_chieu }}</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    {{-- Body --}}
                    <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                        {{-- Policy Alert - Compact --}}
                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle-fill fs-5 me-3 flex-shrink-0 text-info"></i>
                                <div class="small">
                                    <strong>Chính sách:</strong> Trước 24h Nhận phòng • Đổi phòng tăng giá: Thanh toán chênh lệch • Đổi phòng dưới giá: Nhận voucher • Miễn phí nếu cùng giá
                                </div>
                            </div>
                        </div>

                        {{-- Current Rooms - COMPACT GRID --}}
                        <div class="mb-4">
                            <h6 class="text-muted mb-3 d-flex align-items-center">
                                <i class="bi bi-pin-fill me-2 text-primary"></i>
                                Phòng hiện tại
                                @if($booking->datPhongItems && $booking->datPhongItems->count() > 1)
                                    <span class="badge bg-primary ms-2">{{ $booking->datPhongItems->count() }} phòng</span>
                                @endif
                            </h6>
                            
                            @if($booking->datPhongItems && $booking->datPhongItems->count() > 0)
                                <div class="row g-3">
                                    @foreach($booking->datPhongItems as $index => $currentItem)
                                        @php
                                            $currentRoom = $currentItem->phong;
                                            $currentRoomType = $currentItem->loaiPhong;
                                            $meta = is_array($booking->snapshot_meta) 
                                                ? $booking->snapshot_meta 
                                                : json_decode($booking->snapshot_meta, true);
                                            $totalGuests = ($meta['computed_adults'] ?? 0) + ($meta['chargeable_children'] ?? 0);
                                            $guestsPerRoom = $currentItem->so_nguoi_o ?? 0;
                                            
                                            if ($guestsPerRoom == 0) {
                                                $totalRooms = $booking->datPhongItems ? $booking->datPhongItems->count() : 1;
                                                $guestsPerRoom = $totalRooms > 0 ? ceil($totalGuests / $totalRooms) : $totalGuests;
                                            }
                                            
                                            $extraAdults = $currentItem->number_adult ?? 0;
                                            $extraChildren = $currentItem->number_child ?? 0;
                                            $extraAdultsCharge = $extraAdults * 150000;
                                            $extraChildrenCharge = $extraChildren * 60000;
                                            $extraCharge = $extraAdultsCharge + $extraChildrenCharge;
                                            $basePrice = $currentRoom->gia_cuoi_cung ?? 0;
                                        @endphp
                                        
                                        <div class="col-lg-6">
                                            <div class="card border-2 border-primary h-100 shadow-sm hover-shadow transition">
                                                <div class="card-body p-3">
                                                    <div class="d-flex gap-3">
                                                        {{-- Image --}}
                                                        <div class="flex-shrink-0">
                                                            @if($currentRoom && $currentRoom->images && $currentRoom->images->count() > 0)
                                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($currentRoom->images->first()->image_path) }}" 
                                                                     alt="Room" class="rounded shadow-sm" style="width: 90px; height: 90px; object-fit: cover;">
                                                            @else
                                                                <div class="bg-light text-secondary d-flex align-items-center justify-content-center rounded shadow-sm" 
                                                                     style="width: 90px; height: 90px;">
                                                                    <i class="bi bi-image fs-4"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        
                                                        {{-- Info --}}
                                                        <div class="flex-grow-1 min-width-0">
                                                            <h6 class="mb-2 fw-bold text-truncate">
                                                                <span class="text-primary">#{{ $currentRoom->ma_phong ?? 'N/A' }}</span>
                                                                <span class="text-muted small">{{ $currentRoomType->ten ?? 'N/A' }}</span>
                                                            </h6>
                                                            
                                                            <div class="small text-muted mb-2">
                                                                <div class="mb-1">
                                                                    <i class="bi bi-people me-1"></i>{{ $currentRoomType->suc_chua ?? 2 }} người
                                                                    <span class="mx-1">•</span>
                                                                    <i class="bi bi-person-check-fill text-info me-1"></i>
                                                                    <strong class="text-dark">{{ $guestsPerRoom }}</strong> đặt
                                                                </div>
                                                                @if($extraAdults > 0 || $extraChildren > 0)
                                                                    <small class="text-warning">
                                                                        <i class="bi bi-exclamation-circle me-1"></i>
                                                                        ({{ $extraAdults > 0 ? $extraAdults . ' NL' : '' }}{{ $extraAdults > 0 && $extraChildren > 0 ? ', ' : '' }}{{ $extraChildren > 0 ? $extraChildren . ' TE' : '' }} vượt)
                                                                    </small>
                                                                @endif
                                                            </div>
                                                            
                                                            {{-- Price --}}
                                                            <div class="d-flex justify-content-between align-items-end">
                                                                <div class="small">
                                                                    {{-- Always show base price --}}
                                                                    <div class="text-muted">
                                                                        <i class="bi bi-tag me-1"></i>Giá gốc: <strong>{{ number_format($basePrice, 0, ',', '.') }}đ</strong>
                                                                    </div>
                                                                    @if($extraCharge > 0)
                                                                        <div class="text-warning">
                                                                            <i class="bi bi-person-plus me-1"></i>+{{ number_format($extraCharge, 0, ',', '.') }}đ phụ thu
                                                                        </div>
                                                                    @else
                                                                        <div class="text-success">
                                                                            <i class="bi bi-check-circle me-1"></i>Không phụ thu khách
                                                                        </div>
                                                                    @endif
                                                                    @php
                                                                        $weekendNights = $meta['weekend_nights'] ?? 0;
                                                                    @endphp
                                                                    @if($weekendNights > 0)
                                                                        <div class="text-info">
                                                                            <i class="bi bi-calendar-event me-1"></i>{{ $weekendNights }} đêm cuối tuần (+10%)
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="text-end">
                                                                    <small class="text-muted d-block">Tổng/đêm</small>
                                                                @php
                                                                    // FIXED: Use voucher_allocated from THIS room item
                                                                    $voucherForThisRoom = $currentItem->voucher_allocated ?? 0;
                                                                    $nights = $currentItem->so_dem ?? 1;
                                                                    $currentPricePerNight = $currentItem->gia_tren_dem ?? 0;
                                                                    
                                                                    // Original price = current price + voucher allocated per night
                                                                    $originalPricePerNight = $voucherForThisRoom > 0
                                                                        ? ($currentPricePerNight + ($voucherForThisRoom / max(1, $nights)))
                                                                        : $currentPricePerNight;
                                                                    $hasVoucherForThisRoom = $voucherForThisRoom > 0;
                                                                @endphp
                                                                @if($hasVoucherForThisRoom)
                                                                    <small class="text-muted text-decoration-line-through d-block">{{ number_format($originalPricePerNight, 0, ',', '.') }}đ</small>
                                                                @endif
                                                                <strong class="text-success fs-6">{{ number_format($currentPricePerNight, 0, ',', '.') }}đ</strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    {{-- Change Button --}}
                                                    <button type="button" 
                                                            class="btn btn-outline-primary btn-sm w-100 mt-3"
                                                            onclick="openChangeRoomModal('{{ $currentRoom->id }}', '{{ $currentRoom->ma_phong }}')">
                                                        <i class="bi bi-shuffle me-1"></i>Đổi phòng này
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <hr class="my-4">

                        {{-- Available Rooms Section --}}
                        <div id="availableRoomsSection" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <h6 class="mb-0">
                                    <i class="bi bi-house-door me-2 text-primary"></i>
                                    <span id="changeRoomTitle">Chọn phòng mới</span>
                                </h6>
                                <div class="d-flex gap-2 flex-wrap">
                                    <select class="form-select form-select-sm" id="filterRoomType" style="width: auto;">
                                        <option value="">Tất cả loại</option>
                                    </select>
                                    <select class="form-select form-select-sm" id="filterPrice" style="width: auto;">
                                        <option value="">Mọi giá</option>
                                        <option value="same">Cùng giá</option>
                                        <option value="cheaper">Rẻ hơn</option>
                                        <option value="expensive">Đắt hơn</option>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="hideAvailableRooms()">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Available Rooms Grid --}}
                            <div class="row g-3" id="availableRoomsGrid">
                                <div class="col-12 text-center py-5" id="loadingRooms">
                                    <div class="spinner-border text-primary"></div>
                                    <p class="text-muted mt-3 mb-0">Đang tìm phòng trống...</p>
                                </div>
                            </div>
                        </div>

                        {{-- Price Summary --}}
                        <div id="priceSummarySection" class="d-none mt-4">
                            <div class="card border-0 shadow-sm bg-light">
                                <div class="card-body p-4">
                                    <h6 class="mb-3 fw-bold">
                                        <i class="bi bi-calculator me-2 text-primary"></i>Chi tiết giá
                                    </h6>
                                    
                                    {{-- Room Comparison --}}
                                    <div class="row g-3 mb-3">
                                        <div class="col-6">
                                            <div class="card bg-white border h-100">
                                                <div class="card-body p-3">
                                                    <small class="text-muted d-block mb-1"><i class="bi bi-door-closed"></i> Phòng hiện tại</small>
                                                    <div class="fw-bold small" id="oldRoomName">{{ $currentRoom->loaiPhong->ten ?? 'N/A' }}</div>
                                                    <small class="text-muted" id="oldRoomCode">#{{ $currentRoom->ma_phong ?? 'N/A' }}</small>
                                                    
                                                    {{-- Detailed price breakdown --}}
                                                    <div class="mt-2 pt-2 border-top" style="font-size: 0.75rem;">
                                                        <div class="d-flex justify-content-between text-muted mb-1">
                                                            <span>Giá TB/đêm:</span>
                                                            <span>{{ number_format($currentPriceOriginal ?? 0, 0, ',', '.') }}đ</span>
                                                        </div>
                                                        @if(isset($meta['weekend_nights']) && $meta['weekend_nights'] > 0)
                                                        <div class="d-flex justify-content-between text-info mb-1">
                                                            <span><i class="bi bi-calendar-event"></i> Cuối tuần:</span>
                                                            <span>{{ $meta['weekend_nights'] }} đêm (+10%)</span>
                                                        </div>
                                                        @endif
                                                        @php
                                                            $currentExtraAdults = $currentItem->number_adult ?? 0;
                                                            $currentExtraChildren = $currentItem->number_child ?? 0;
                                                        @endphp
                                                        @if($currentExtraAdults > 0)
                                                        <div class="d-flex justify-content-between text-warning mb-1">
                                                            <span><i class="bi bi-person-plus"></i> NL thêm:</span>
                                                            <span>{{ $currentExtraAdults }} ({{ number_format($currentExtraAdults * 150000) }}đ)</span>
                                                        </div>
                                                        @endif
                                                        @if($currentExtraChildren > 0)
                                                        <div class="d-flex justify-content-between text-warning mb-1">
                                                            <span><i class="bi bi-person"></i> TE thêm:</span>
                                                            <span>{{ $currentExtraChildren }} ({{ number_format($currentExtraChildren * 60000) }}đ)</span>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="mt-2">
                                                        <span class="badge bg-secondary small" id="oldRoomPricePerNight">
                                                            {{ number_format($currentPriceOriginal ?? 0, 0, ',', '.') }}đ/đêm
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="card bg-white border-primary border-2 h-100">
                                                <div class="card-body p-3">
                                                    <small class="text-muted d-block mb-1"><i class="bi bi-door-open"></i> Phòng mới</small>
                                                    <div class="fw-bold text-primary small" id="newRoomName">Chưa chọn</div>
                                                    <small class="text-muted" id="newRoomCode">-</small>
                                                    
                                                    {{-- New room price breakdown (will be updated by JS) --}}
                                                    <div class="mt-2 pt-2 border-top" style="font-size: 0.75rem;" id="newRoomBreakdown">
                                                        <div class="text-muted text-center py-2">
                                                            <i class="bi bi-arrow-left-circle"></i> Chọn phòng bên trái
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mt-2">
                                                        <span class="badge bg-primary small" id="newRoomPricePerNight">0đ/đêm</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Calculation --}}
                                    <div class="small">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Booking hiện tại:</span>
                                            <strong>{{ number_format($booking->tong_tien ?? 0, 0, ',', '.') }}đ</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Số đêm còn lại:</span>
                                            <strong id="nightsDisplay">{{ $meta['nights'] ?? 1 }} đêm</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-muted">Chênh lệch/đêm:</span>
                                            <strong id="priceDiffPerNight" class="text-primary">0đ</strong>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-3">
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold">Tổng chênh lệch:</span>
                                        <h5 class="mb-0" id="totalDifference">0đ</h5>
                                    </div>
                                    
                                    {{-- Voucher preservation indicator - ENHANCED DETAIL --}}
                                    @if($booking->voucher_discount > 0)
                                    @php
                                        $meta = is_array($booking->snapshot_meta) ? $booking->snapshot_meta : json_decode($booking->snapshot_meta, true);
                                        $voucherCode = $meta['ma_voucher'] ?? 'Voucher';
                                    @endphp
                                    <div class="mb-3 p-3 bg-success bg-opacity-10 rounded border border-success border-opacity-25">
                                        {{-- Voucher header with code badge --}}
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-success fw-bold">
                                                <i class="bi bi-ticket-perforated-fill me-1"></i>Voucher được giữ lại
                                            </span>
                                            <span class="badge bg-success">{{ $voucherCode }}</span>
                                        </div>
                                        
                                        <hr class="my-2 border-success border-opacity-25">
                                        
                                        {{-- Breakdown details --}}
                                        <div class="small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Giá phòng mới (trước voucher):</span>
                                                <span class="text-muted" id="priceBeforeVoucher">0đ</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-success fw-semibold">
                                                    <i class="bi bi-dash-circle me-1"></i>Giảm từ voucher:
                                                </span>
                                                <strong class="text-success">-{{ number_format($booking->voucher_discount, 0, ',', '.') }}đ</strong>
                                            </div>
                                        </div>
                                        
                                        <hr class="my-2 border-success border-opacity-25">
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold">Tổng sau voucher:</span>
                                            <strong class="text-success" id="priceAfterVoucher">0đ</strong>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded">
                                        <span class="fw-bold">Tổng booking mới:</span>
                                        <h4 class="mb-0 text-success" id="newTotal">{{ number_format($booking->tong_tien ?? 0, 0, ',', '.') }}đ</h4>
                                    </div>

                                    {{-- Payment/Voucher Info --}}
                                    <div id="paymentInfoSection" class="mt-3 d-none">
                                        <div class="alert alert-warning mb-0 small">
                                            <h6 class="fw-semibold mb-2 small"><i class="bi bi-wallet2"></i> Thanh toán</h6>
                                            <div class="d-flex justify-content-between">
                                                <span>Đã cọc:</span>
                                                <strong>{{ number_format($booking->deposit_amount ?? 0, 0, ',', '.') }}đ</strong>
                                            </div>
                                            <div id="paymentNeededInfo"></div>
                                        </div>
                                    </div>
                                    
                                    <div id="voucherInfoSection" class="mt-3 d-none">
                                        <div class="alert alert-success mb-0 small">
                                            <h6 class="fw-semibold mb-2 small"><i class="bi bi-gift"></i> Hoàn voucher</h6>
                                            <p class="mb-2">Số tiền chênh lệch hoàn qua voucher:</p>
                                            <div id="expectedVoucherValue" class="fs-5 fw-bold text-success"></div>
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle"></i> Hạn 30 ngày
                                            </small>
                                        </div>
                                    </div>
                                    
                                    {{-- NEW: Manual Voucher Selection Section (only show for upgrades) --}}
                                    <div id="voucherSelectionSection" class="mt-3 d-none">
                                        <div class="card border-success">
                                            <div class="card-header bg-success bg-opacity-10 border-success">
                                                <h6 class="mb-0 fw-semibold small text-success">
                                                    <i class="bi bi-gift-fill me-2"></i>
                                                    Sử dụng Voucher (Tùy chọn)
                                                </h6>
                                            </div>
                                            <div class="card-body p-3">
                                                <p class="text-muted small mb-3">
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Bạn có voucher hoàn tiề từ lần downgrade trước. Chọn voucher để giảm chi phí nâng cấp.
                                                </p>
                                                
                                                {{-- Voucher list container --}}
                                                <div id="availableVouchersList">
                                                    {{-- Vouchers will be loaded here via JS --}}
                                                    <div class="text-center py-3" id="loadingVouchers">
                                                        <div class="spinner-border spinner-border-sm text-success"></div>
                                                        <p class="text-muted small mt-2 mb-0">Đang tải voucher...</p>
                                                    </div>
                                                </div>
                                                
                                                {{-- No vouchers message --}}
                                                <div id="noVouchersMessage" class="text-center py-3 d-none">
                                                    <i class="bi bi-inbox text-muted fs-3 d-block mb-2"></i>
                                                    <p class="text-muted small mb-0">Không có voucher khả dụng</p>
                                                </div>
                                                
                                                {{-- Selected vouchers summary --}}
                                                <div id="selectedVouchersSummary" class="mt-3 d-none">
                                                    <hr>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-semibold small">Tổng giảm giá:</span>
                                                        <span class="badge bg-success fs-6" id="totalVoucherDiscount">0đ</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>Hủy
                        </button>
                        <button type="button" class="btn btn-primary" id="confirmChangeBtn" disabled>
                            <i class="bi bi-check-circle me-1"></i>Xác nhận đổi phòng
                        </button>
                    </div>
                </div>
            </div>
        </div>
                    <div class="modal-body p-4">
                        <i class="bi bi-clock-history me-2 text-info"></i>
                        Lịch Sử Đổi Phòng
                        <span class="badge bg-info ms-2">{{ $booking->roomChanges->count() }}</span>
                    </h6>
                </div>
                <div class="card-body p-4">
                    @foreach($booking->roomChanges->sortByDesc('created_at') as $change)
                        <div class="row align-items-center mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="col-md-2">
                                <small class="text-muted d-block">
                                    <i class="bi bi-calendar me-1"></i>
                                    {{ $change->created_at->format('d/m/Y') }}
                                </small>
                                <small class="text-muted">{{ $change->created_at->format('H:i') }}</small>
                            </div>
                            <div class="col-md-7">
                                <div class="d-flex align-items-center">
                                    <div class="text-center">
                                        <strong class="text-danger">{{ $change->oldRoom->ma_phong ?? 'N/A' }}</strong>
                                        <div class="small text-muted">{{ number_format($change->old_price) }}đ</div>
                                    </div>
                                    <div class="mx-3">
                                        <i class="bi bi-arrow-right fs-4 text-primary"></i>
                                    </div>
                                    <div class="text-center">
                                        <strong class="text-success">{{ $change->newRoom->ma_phong ?? 'N/A' }}</strong>
                                        <div class="small text-muted">{{ number_format($change->new_price) }}đ</div>
                                    </div>
                                    <div class="ms-3">
                                        @if($change->price_difference > 0)
                                            <span class="badge bg-danger">+{{ number_format($change->price_difference) }}đ</span>
                                        @elseif($change->price_difference < 0)
                                            <span class="badge bg-success">{{ number_format($change->price_difference) }}đ</span>
                                        @else
                                            <span class="badge bg-secondary">Cùng giá</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                @if($change->status === 'completed')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Hoàn tất
                                    </span>
                                @elseif($change->status === 'pending')
                                    <span class="badge bg-warning">
                                        <i class="bi bi-hourglass me-1"></i>Đang xử lý
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle me-1"></i>Thất bại
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    {{-- SweetAlert2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .card { border-radius: 12px; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.03); }
        .badge { min-width: 100px; }
        
        /* Timeline Styles */
        .booking-timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 20px 0;
        }
        
        .timeline-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 0;
        }
        
        .timeline-step.completed:not(:last-child)::after {
            background-color: #10b981;
        }
        
        .timeline-step.cancelled:not(:last-child)::after {
            background-color: #ef4444;
        }
        
        .timeline-marker {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0e0e0;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            border: 3px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .timeline-step.completed .timeline-marker {
            background-color: #10b981;
            color: white;
        }
        
        .timeline-step.cancelled .timeline-marker {
            background-color: #ef4444;
            color: white;
        }
        
        .timeline-marker i {
            font-size: 18px;
            color: #9ca3af;
        }
        
        .timeline-step.completed .timeline-marker i,
        .timeline-step.cancelled .timeline-marker i {
            color: white;
        }
        
        .timeline-content h6 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .timeline-content small {
            font-size: 12px;
        }

        /* Room Change Modal Styles */
        .room-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #e5e7eb;
        }

        .room-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
            border-color: #0d6efd;
        }

        .room-card.selected {
            border: 3px solid #0d6efd;
            box-shadow: 0 4px 16px rgba(13, 110, 253, 0.3);
            background-color: #f8f9ff;
        }

        .room-card.selected .select-room-btn {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        .room-card .card-img-top {
            transition: transform 0.3s ease;
        }

        .room-card:hover .card-img-top {
            transform: scale(1.05);
        }

        .price-badge-increase {
            background-color: #ef4444 !important;
        }

        .price-badge-decrease {
            background-color: #10b981 !important;
        }

        .price-badge-same {
            background-color: #6c757d !important;
        }

        /* Responsive adjustments for room change modal */
        @media (max-width: 768px) {
            .room-card-wrapper {
                margin-bottom: 1rem;
            }

            #changeRoomModal .modal-dialog {
                margin: 0.5rem;
            }

            #availableRoomsGrid {
                max-height: 400px;
                overflow-y: auto;
            }
        }
        
        @media (max-width: 768px) {
            .card-header { 
                flex-direction: column; 
                align-items: stretch !important; 
                text-align: center; 
            }
            .card-header .text-end { 
                text-align: center !important; 
            }
            
            .booking-timeline {
                flex-direction: column;
            }
            
            .timeline-step:not(:last-child)::after {
                top: 50px;
                left: 19px;
                width: 2px;
                height: calc(100% - 30px);
            }
            
            .timeline-step {
                text-align: left;
                padding-left: 60px;
                margin-bottom: 30px;
            }
            
            .timeline-marker {
                position: absolute;
                left: 0;
                top: 0;
            }
        }
        
        @media print {
            .btn, .modal { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>
@endpush

@push('scripts')
    {{-- SweetAlert2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const changeRoomModal = document.getElementById('changeRoomModal');
            if (!changeRoomModal) return;

            // Data for demo purposes - will be replaced with AJAX call
            // VOUCHER FIX: Use original price (before voucher) from backend
            // Use Math.round to avoid decimal issues in JavaScript
            let currentRoomPrice = {{ (int) round($currentPriceOriginal ?? 0) }};  // Preserves voucher discount
            console.log('🔍 DEBUG currentRoomPrice from backend:', currentRoomPrice);
            const nightsRemaining = {{ $meta['nights'] ?? 1 }};
            const oldTotal = {{ (int) ($booking->tong_tien ?? 0) }};
            
            // Map of room prices for lookup (integers only)
            const roomPrices = {
                @foreach($booking->datPhongItems as $item)
                    {{ $item->phong_id }}: {{ (int) round($item->gia_tren_dem ?? 0) }},
                @endforeach
            };
            
            let selectedRoomId = null;
            let selectedRoomPrice = 0;
            let oldRoomId = null; // Track which room is being changed
            let oldRoomCode = ''; // Track room code for display
            
            // Global function for onclick (can be called multiple times safely)
            window.openChangeRoomModal = function(roomId, roomCode) {
                oldRoomId = roomId;
                oldRoomCode = roomCode;
                
                // VOUCHER FIX: Don't override! Use backend's original price
                // currentRoomPrice = roomPrices[roomId] || 0;  // This used gia_tren_dem (discounted!)
                
                console.log('Changing room:', roomCode, 'ID:', roomId, 'Price:', currentRoomPrice);
                
                // CRITICAL: Clear cached data when switching rooms
                allAvailableRooms = [];
                selectedRoomId = null;
                selectedRoomPrice = 0;
                
                // Clear UI selections
                document.querySelectorAll('.room-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Update section title
                const title = document.getElementById('changeRoomTitle');
                if (title) {
                    title.textContent = `Chọn phòng mới cho #${roomCode}`;
                }
                
                // Show available rooms section
                const section = document.getElementById('availableRoomsSection');
                if (section) {
                    section.style.display = 'block';
                    
                    // Scroll to section smoothly
                    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                // FORCE reload available rooms (not using cache)
                loadAvailableRooms();
            };
            
            // Global function to hide available rooms section
            window.hideAvailableRooms = function() {
                const section = document.getElementById('availableRoomsSection');
                if (section) {
                    section.style.display = 'none';
                }
                
                // Reset state
                selectedRoomId = null;
                selectedRoomPrice = 0;
                oldRoomId = null;
                oldRoomCode = '';
                
                // Clear room selection in UI
                document.querySelectorAll('.room-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
            };

            
            // Reset modal state when closed (only attach once)
            if (!window.modalResetListenerAttached) {
                window.modalResetListenerAttached = true;
                
                changeRoomModal.addEventListener('hide.bs.modal', function() {
                    console.log('Modal closing, resetting state...');
                    selectedRoomId = null;
                    selectedRoomPrice = 0;
                    oldRoomId = null;
                    oldRoomCode = '';
                    
                    // Clear room selection in UI
                    document.querySelectorAll('.room-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Disable confirm button
                    const confirmBtn = document.getElementById('confirmChangeBtn');
                    if (confirmBtn) {
                        confirmBtn.disabled = true;
                    }
                });
            }



            // NOTE: loadAvailableRooms() is called by button click handlers
            // No need for separate modal event listener

            let allAvailableRooms = []; // Store all rooms for filtering

            function loadAvailableRooms() {
                const grid = document.getElementById('availableRoomsGrid');
                const loading = document.getElementById('loadingRooms');
                
                // Build URL with old_room_id parameter for multi-room support
                let apiUrl = '/account/bookings/{{ $booking->id }}/available-rooms';
                if (oldRoomId) {
                    apiUrl += `?old_room_id=${oldRoomId}`;
                }
                
                console.log('🔍 Loading available rooms for oldRoomId:', oldRoomId, 'URL:', apiUrl);
                
                // Fetch from API
                fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch');
                    return response.json();
                })
                .then(data => {
                    if (loading && loading.parentElement) loading.remove();
                    
                    if (data.success && data.available_rooms && data.available_rooms.length > 0) {
                        allAvailableRooms = data.available_rooms;
                        populateRoomTypeFilter(data.available_rooms);
                        renderRoomCards(data.available_rooms);
                        setupFilters();
                    } else {
                        showNoRoomsMessage();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (loading && loading.parentElement) loading.remove();
                    showErrorMessage();
                });
            }

            function populateRoomTypeFilter(rooms) {
                const typeFilter = document.getElementById('filterRoomType');
                const uniqueTypes = [...new Set(rooms.map(r => r.name))]; // Get unique room type names
                
                // Count rooms per type
                const typeCounts = {};
                rooms.forEach(r => {
                    typeCounts[r.name] = (typeCounts[r.name] || 0) + 1;
                });
                
                // Clear existing options except first
                typeFilter.innerHTML = '<option value="">Tất cả loại phòng</option>';
                
                // Add dynamic options
                uniqueTypes.forEach(typeName => {
                    const option = document.createElement('option');
                    option.value = typeName;
                    option.textContent = `${typeName} (${typeCounts[typeName]})`;
                    typeFilter.appendChild(option);
                });
            }

            function setupFilters() {
                const typeFilter = document.getElementById('filterRoomType');
                const priceFilter = document.getElementById('filterPrice');
                
                typeFilter.addEventListener('change', applyFilters);
                priceFilter.addEventListener('change', applyFilters);
            }

            function applyFilters() {
                const typeFilter = document.getElementById('filterRoomType').value;
                const priceFilter = document.getElementById('filterPrice').value;
                
                console.log('Applying filters:', {typeFilter, priceFilter}); // DEBUG
                console.log('All rooms:', allAvailableRooms); // DEBUG
                
                let filteredRooms = [...allAvailableRooms];
                
                // Filter by room type
                if (typeFilter) {
                    filteredRooms = filteredRooms.filter(r => {
                        const match = r.name === typeFilter;
                        if (!match) {
                            console.log(`Room ${r.code} name "${r.name}" doesn't match "${typeFilter}"`); // DEBUG
                        }
                        return match;
                    });
                    console.log('After type filter:', filteredRooms.length, 'rooms'); // DEBUG
                }
                
                // Filter by price
                if (priceFilter === 'same') {
                    filteredRooms = filteredRooms.filter(r => r.price_difference === 0);
                } else if (priceFilter === 'cheaper') {
                    filteredRooms = filteredRooms.filter(r => r.price_difference < 0);
                } else if (priceFilter === 'expensive') {
                    filteredRooms = filteredRooms.filter(r => r.price_difference > 0);
                }
                
                console.log('Final filtered rooms:', filteredRooms.length); // DEBUG
                
                // Re-render with filtered results
                if (filteredRooms.length > 0) {
                    renderRoomCards(filteredRooms);
                } else {
                    const grid = document.getElementById('availableRoomsGrid');
                    grid.innerHTML = `
                        <div class="col-12 text-center py-4">
                            <i class="bi bi-funnel text-muted fs-1 d-block mb-2"></i>
                            <p class="text-muted">Không có phòng nào phù hợp với bộ lọc</p>
                            <button class="btn btn-sm btn-outline-primary" onclick="resetFilters()">Xóa bộ lọc</button>
                        </div>
                    `;
                }
            }

            function resetFilters() {
                document.getElementById('filterRoomType').value = '';
                document.getElementById('filterPrice').value = '';
                applyFilters();
            }

            /**
             * Show room type quick view modal with details
             */
            async function showRoomTypeQuickView(roomTypeId, roomTypeName) {
                try {
                    // Show loading
                    Swal.fire({
                        title: 'Đang tải thông tin...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Fetch room type details
                    const response = await fetch(`/api/room-types/${roomTypeId}/quick-view`);
                    const result = await response.json();

                    if (!result.success) {
                        throw new Error('Không thể tải thông tin loại phòng');
                    }

                    const data = result.data;

                    // Build images carousel
                    let imagesHtml = '';
                    if (data.images && data.images.length > 0) {
                        imagesHtml = `
                            <div id="roomTypeCarousel" class="carousel slide mb-3" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    ${data.images.map((img, idx) => `
                                        <div class="carousel-item ${idx === 0 ? 'active' : ''}">
                                            <img src="${img.url}" class="d-block w-100" alt="${img.alt}" style="height: 300px; object-fit: cover; border-radius: 8px;">
                                        </div>
                                    `).join('')}
                                </div>
                                ${data.images.length > 1 ? `
                                    <button class="carousel-control-prev" type="button" data-bs-target="#roomTypeCarousel" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#roomTypeCarousel" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    </button>
                                ` : ''}
                            </div>
                        `;
                    }

                    // Build amenities list
                    let amenitiesHtml = '';
                    if (data.amenities && data.amenities.length > 0) {
                        amenitiesHtml = `
                            <div class="mb-3">
                                <h6 class="fw-bold mb-2"><i class="bi bi-stars me-1"></i>Tiện nghi</h6>
                                <div class="row g-2">
                                    ${data.amenities.map(amenity => `
                                        <div class="col-6">
                                            <i class="bi ${amenity.icon} text-primary me-1"></i>
                                            <span class="small">${amenity.name}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                    // Build bed types
                    let bedTypesHtml = '';
                    if (data.bed_types && data.bed_types.length > 0) {
                        bedTypesHtml = `
                            <div class="mb-3">
                                <h6 class="fw-bold mb-2"><i class="bi bi-door-open me-1"></i>Loại giường</h6>
                                <div class="d-flex gap-2 flex-wrap">
                                    ${data.bed_types.map(bed => `
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi ${bed.icon || 'bi-door-open'} me-1"></i>
                                            ${bed.quantity}x ${bed.name}
                                        </span>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                    // Show modal with room type details
                    Swal.fire({
                        title: `<i class="bi bi-house-door me-2"></i>${data.name}`,
                        width: '700px',
                        html: `
                            <div class="text-start">
                                ${imagesHtml}
                                
                                <div class="card border-0 bg-light mb-3">
                                    <div class="card-body p-3">
                                        <div class="row g-3">
                                            <div class="col-4 text-center">
                                                <i class="bi bi-people fs-4 text-primary"></i>
                                                <div class="small text-muted mt-1">Sức chứa</div>
                                                <div class="fw-bold">${data.capacity} người</div>
                                            </div>
                                            ${data.area ? `
                                                <div class="col-4 text-center">
                                                    <i class="bi bi-rulers fs-4 text-primary"></i>
                                                    <div class="small text-muted mt-1">Diện tích</div>
                                                    <div class="fw-bold">${data.area} m²</div>
                                                </div>
                                            ` : ''}
                                            <div class="col-4 text-center">
                                                <i class="bi bi-cash-coin fs-4 text-success"></i>
                                                <div class="small text-muted mt-1">Giá từ</div>
                                                <div class="fw-bold text-success">${data.base_price.toLocaleString('vi-VN')}đ</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                ${data.description ? `
                                    <div class="mb-3">
                                        <h6 class="fw-bold mb-2"><i class="bi bi-file-text me-1"></i>Mô tả</h6>
                                        <p class="text-muted small mb-0">${data.description}</p>
                                    </div>
                                ` : ''}

                                ${bedTypesHtml}
                                ${amenitiesHtml}

                                <div class="alert alert-info small mb-0">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Khách sạn sẽ tự động chọn phòng tốt nhất còn trống cho bạn
                                </div>
                            </div>
                        `,
                        showCloseButton: true,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'room-type-quick-view-modal'
                        }
                    });

                } catch (error) {
                    console.error('Error loading room type details:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Không thể tải thông tin loại phòng. Vui lòng thử lại!'
                    });
                }
            }

            function showNoRoomsMessage() {
                const grid = document.getElementById('availableRoomsGrid');
                grid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted">Không có phòng trống</h5>
                        <p class="text-muted">Hiện tại không có phòng nào phù hợp để đổi.</p>
                    </div>
                `;
            }

            function showErrorMessage() {
                const grid = document.getElementById('availableRoomsGrid');
                grid.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Không thể tải danh sách phòng. Vui lòng thử lại.
                        </div>
                    </div>
                `;
            }

            function renderRoomCards(rooms) {
                const grid = document.getElementById('availableRoomsGrid');
                grid.innerHTML = '';

                // ⭐ Group rooms by type (hide specific room numbers from customers)
                const roomsByType = {};
                rooms.forEach(room => {
                    const typeKey = room.name; // "Deluxe Room", "Suite Room", etc.
                    if (!roomsByType[typeKey]) {
                        roomsByType[typeKey] = {
                            type_name: room.name,
                            type_slug: room.type,
                            rooms: [],
                            min_price: room.price,
                            max_price: room.price,
                            total_available: 0,
                            sample_room: null // Store first room for submission
                        };
                    }
                    roomsByType[typeKey].rooms.push(room);
                    roomsByType[typeKey].total_available++;
                    roomsByType[typeKey].min_price = Math.min(roomsByType[typeKey].min_price, room.price);
                    roomsByType[typeKey].max_price = Math.max(roomsByType[typeKey].max_price, room.price);
                    
                    // Use first room as sample for submission
                    if (!roomsByType[typeKey].sample_room) {
                        roomsByType[typeKey].sample_room = room;
                    }
                });

                // Render each room type (not individual rooms)
                Object.values(roomsByType).forEach(roomType => {
                    const room = roomType.sample_room; // Use sample room for prices
                    
                    // CRITICAL FIX: Use price_difference from API (correctly calculated server-side)
                    // instead of recalculating here with wrong currentRoomPrice (which is an average)
                    const priceDiff = room.price_difference ?? (room.price - currentRoomPrice);
                    const priceDiffFormatted = Math.abs(priceDiff).toLocaleString('vi-VN');
                    
                    let badgeClass = 'price-badge-same';
                    let badgeText = 'Cùng giá';
                    
                    if (priceDiff > 0) {
                        badgeClass = 'price-badge-increase';
                        badgeText = '+' + priceDiffFormatted + 'đ';
                    } else if (priceDiff < 0) {
                        badgeClass = 'price-badge-decrease';
                        badgeText = '-' + priceDiffFormatted + 'đ';
                    }

                    // Build surcharge info if applicable (only show extra charges, base price is shown above)
                    let surchargeHtml = '';
                    if (room.extra_charge && room.extra_charge > 0) {
                        surchargeHtml = `
                            <div class="small text-warning mb-1">
                                <i class="bi bi-person-plus me-1"></i>Phụ thu khách thêm: <strong>+${room.extra_charge.toLocaleString('vi-VN')}đ</strong>
                                <span class="text-muted">(${room.extra_adults || 0} NL, ${room.extra_children || 0} TE)</span>
                            </div>
                        `;
                    }

                    // Build weekend surcharge info if applicable
                    let weekendHtml = '';
                    if (room.weekend_nights && room.weekend_nights > 0) {
                        weekendHtml = `
                            <div class="alert alert-info alert-sm p-2 mb-2" style="font-size: 0.75rem;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">
                                        <i class="bi bi-calendar-event me-1"></i>Phụ thu cuối tuần (${room.weekend_nights} đêm):
                                    </span>
                                    <strong class="text-primary">+10%</strong>
                                </div>
                            </div>
                        `;
                    }

                    const cardHtml = `
                        <div class="col-md-4 room-card-wrapper" data-room-id="${room.id}" data-price="${room.price}" data-type="${room.type}">
                            <div class="card room-card h-100">
                                <div class="position-relative overflow-hidden">
                                    <img src="${room.image}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="${roomType.type_name}">
                                    <span class="position-absolute top-0 end-0 m-2 badge ${badgeClass}">${badgeText}</span>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title mb-2 text-primary fw-bold">${roomType.type_name}</h6>
                                    
                                    <div class="alert alert-info p-2 mb-2" style="font-size: 0.85rem; background-color: #e7f3ff; border-color: #b8daff;">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <strong>${roomType.total_available}</strong> phòng trống
                                    </div>
                                    
                                    <div class="text-muted small mb-2">
                                        <i class="bi bi-people me-1"></i>Sức chứa: ${room.capacity} người
                                    </div>
                                    
                                    {{-- Always show base price --}}
                                    <div class="small text-muted mb-1">
                                        <i class="bi bi-tag me-1"></i>Giá gốc: <strong>${room.base_price.toLocaleString('vi-VN')}đ/đêm</strong>
                                    </div>
                                    
                                    ${surchargeHtml}
                                    ${weekendHtml}
                                    <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top">
                                        <span class="text-muted small">Giá TB/đêm</span>
                                        <strong class="text-success">${room.price.toLocaleString('vi-VN')}đ</strong>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-info btn-sm flex-fill view-room-type-btn" 
                                                data-room-type-id="${room.type_id}" 
                                                data-room-type-name="${roomType.type_name}">
                                            <i class="bi bi-info-circle me-1"></i>Chi tiết
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm flex-fill select-room-btn" data-room-id="${room.id}">
                                            <i class="bi bi-check-circle me-1"></i>Chọn loại phòng này
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    grid.insertAdjacentHTML('beforeend', cardHtml);
                });

                // Attach click handlers
                attachRoomSelectionHandlers();
            }

            function attachRoomSelectionHandlers() {
                // Attach select room button handlers
                const roomCards = document.querySelectorAll('.select-room-btn');
                
                roomCards.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const roomId = this.getAttribute('data-room-id');
                        const wrapper = this.closest('.room-card-wrapper');
                        const price = parseFloat(wrapper.getAttribute('data-price'));
                        
                        selectRoom(roomId, price, wrapper);
                    });
                });

                // Attach quick view button handlers
                const viewBtns = document.querySelectorAll('.view-room-type-btn');
                viewBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const roomTypeId = this.getAttribute('data-room-type-id');
                        const roomTypeName = this.getAttribute('data-room-type-name');
                        showRoomTypeQuickView(roomTypeId, roomTypeName);
                    });
                });
            }

            function selectRoom(roomId, price, wrapper) {
                // Remove previous selection
                document.querySelectorAll('.room-card').forEach(card => {
                    card.classList.remove('selected');
                });
                document.querySelectorAll('.select-room-btn').forEach(btn => {
                    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Chọn loại phòng này';
                });

                // Mark new selection
                const card = wrapper.querySelector('.room-card');
                card.classList.add('selected');
                
                const btn = wrapper.querySelector('.select-room-btn');
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Đã chọn';
                
                selectedRoomId = roomId;
                selectedRoomPrice = price;

                // Find full room data from allAvailableRooms
                const roomData = allAvailableRooms.find(r => r.id == roomId) || { price: price };

                // Update price summary with full room data
                updatePriceSummary(roomData);

                // Enable confirm button
                document.getElementById('confirmChangeBtn').disabled = false;
            }

            function updatePriceSummary(roomData) {
                const newRoomPrice = roomData.price || 0;
                
                // CRITICAL FIX: Use price_difference from API (correctly calculated server-side)
                // instead of recalculating here with wrong currentRoomPrice (which is an average)
                const priceDiffPerNight = roomData.price_difference ?? (newRoomPrice - currentRoomPrice);
                const totalDifference = priceDiffPerNight * nightsRemaining;
                const newTotal = oldTotal + totalDifference;

                // Show price summary section
                document.getElementById('priceSummarySection').classList.remove('d-none');

                // Update room comparison - Get room type name from selected card
                const selectedCard = document.querySelector('.room-card.selected');
                if (selectedCard) {
                    // NEW: Title is just room type name (e.g. "Deluxe Room")
                    const titleElement = selectedCard.querySelector('.card-title');
                    const roomTypeName = titleElement ? titleElement.textContent.trim() : 'N/A';
                    
                    document.getElementById('newRoomName').textContent = roomTypeName;
                    document.getElementById('newRoomCode').textContent = '-'; // Hide room code
                    document.getElementById('newRoomPricePerNight').textContent = Math.round(newRoomPrice).toLocaleString('vi-VN') + 'đ/đêm';
                    
                    // Update new room breakdown
                    const breakdownEl = document.getElementById('newRoomBreakdown');
                    if (breakdownEl) {
                        let breakdownHtml = '';
                        
                        // Base price
                        if (roomData.base_price) {
                            breakdownHtml += `
                                <div class="d-flex justify-content-between text-muted mb-1">
                                    <span>Giá gốc:</span>
                                    <span>${roomData.base_price.toLocaleString('vi-VN')}đ</span>
                                </div>`;
                        }
                        
                        // Extra adults
                        if (roomData.extra_adults && roomData.extra_adults > 0) {
                            breakdownHtml += `
                                <div class="d-flex justify-content-between text-warning mb-1">
                                    <span><i class="bi bi-person-plus"></i> NL thêm:</span>
                                    <span>${roomData.extra_adults} (+${roomData.extra_adults_charge.toLocaleString('vi-VN')}đ)</span>
                                </div>`;
                        }
                        
                        // Extra children
                        if (roomData.extra_children && roomData.extra_children > 0) {
                            breakdownHtml += `
                                <div class="d-flex justify-content-between text-warning mb-1">
                                    <span><i class="bi bi-person"></i> TE thêm:</span>
                                    <span>${roomData.extra_children} (+${roomData.extra_children_charge.toLocaleString('vi-VN')}đ)</span>
                                </div>`;
                        }
                        
                        // Weekend surcharge
                        if (roomData.weekend_nights && roomData.weekend_nights > 0) {
                            breakdownHtml += `
                                <div class="d-flex justify-content-between text-info mb-1">
                                    <span><i class="bi bi-calendar-event"></i> Cuối tuần:</span>
                                    <span>${roomData.weekend_nights} đêm (+10%)</span>
                                </div>`;
                        }
                        
                        // Average per night
                        breakdownHtml += `
                            <div class="d-flex justify-content-between text-primary fw-bold mt-2 pt-2 border-top">
                                <span>Giá TB/đêm:</span>
                                <span>${Math.round(newRoomPrice).toLocaleString('vi-VN')}đ</span>
                            </div>`;
                        
                        breakdownEl.innerHTML = breakdownHtml;
                    }
                }

                // Update basic price info
                document.getElementById('priceDiffPerNight').textContent = formatCurrency(priceDiffPerNight);
                document.getElementById('totalDifference').textContent = formatCurrency(totalDifference);
                document.getElementById('newTotal').textContent = Math.round(newTotal).toLocaleString('vi-VN') + 'đ';
                
                // VOUCHER: Show pre-voucher and post-voucher totals
                const voucherDiscount = {{ $booking->voucher_discount ?? 0 }};
                if (voucherDiscount > 0) {
                    const priceBeforeVoucher = newTotal + voucherDiscount;
                    const beforeVoucherElement = document.getElementById('priceBeforeVoucher');
                    if (beforeVoucherElement) {
                        beforeVoucherElement.textContent = Math.round(priceBeforeVoucher).toLocaleString('vi-VN') + 'đ';
                    }
                    // Also update post-voucher total
                    const afterVoucherElement = document.getElementById('priceAfterVoucher');
                    if (afterVoucherElement) {
                        afterVoucherElement.textContent = Math.round(newTotal).toLocaleString('vi-VN') + 'đ';
                    }
                }

                // Color coding for difference
                const diffElement = document.getElementById('totalDifference');
                const diffPerNightElement = document.getElementById('priceDiffPerNight');
                
                if (totalDifference > 0) {
                    // Upgrade - show payment info
                    diffElement.classList.remove('text-success');
                    diffElement.classList.add('text-danger');
                    diffPerNightElement.classList.remove('text-success');
                    diffPerNightElement.classList.add('text-danger');
                    
                    // Show payment section
                    document.getElementById('paymentInfoSection').classList.remove('d-none');
                    document.getElementById('voucherInfoSection').classList.add('d-none');
                    
                    // Calculate payment needed (basic estimate)
                    const depositPct = {{ $meta['deposit_percentage'] ?? 50 }};
                    const currentDeposit = {{ $booking->deposit_amount ?? 0 }};
                    const newDepositRequired = newTotal * (depositPct / 100);
                    const paymentNeeded = Math.max(0, newDepositRequired - currentDeposit);
                    
                    const paymentInfoHtml = paymentNeeded > 0 
                        ? `<div class="d-flex justify-content-between">
                               <span class="text-danger fw-semibold">Cần thanh toán thêm:</span>
                               <h5 class="mb-0 text-danger">${Math.round(paymentNeeded).toLocaleString('vi-VN')}đ</h5>
                           </div>
                           <small class="text-muted d-block mt-2">
                               <i class="bi bi-info-circle me-1"></i>
                               Bạn sẽ được chuyển đến cổng thanh toán VNPay
                           </small>`
                        : `<div class="alert alert-success mb-0">
                               <i class="bi bi-check-circle me-1"></i>
                               Không cần thanh toán thêm! Bạn đã cọc đủ tiền.
                           </div>`;
                    
                    document.getElementById('paymentNeededInfo').innerHTML = paymentInfoHtml;
                    
                    // ===== NEW: Load vouchers for upgrade =====
                    loadAvailableVouchers();
                    
                } else if (totalDifference < 0) {
                    // Downgrade - show voucher info
                    diffElement.classList.remove('text-danger');
                    diffElement.classList.add('text-success');
                    diffPerNightElement.classList.remove('text-danger');
                    diffPerNightElement.classList.add('text-success');
                    
                    // Show voucher section
                    document.getElementById('voucherInfoSection').classList.remove('d-none');
                    document.getElementById('paymentInfoSection').classList.add('d-none');
                    
                    // Calculate expected voucher value
                    const depositPct = {{ $meta['deposit_percentage'] ?? 50 }};
                    const currentDeposit = {{ $booking->deposit_amount ?? 0 }};
                    const newDepositRequired = newTotal * (depositPct / 100);
                    const voucherValue = Math.max(0, currentDeposit - newDepositRequired);
                    
                    document.getElementById('expectedVoucherValue').textContent = 
                        Math.round(voucherValue).toLocaleString('vi-VN') + 'đ';
                    
                } else {
                    // Same price
                    diffElement.classList.remove('text-danger', 'text-success');
                    diffPerNightElement.classList.remove('text-danger', 'text-success');
                    document.getElementById('paymentInfoSection').classList.add('d-none');
                    document.getElementById('voucherInfoSection').classList.add('d-none');
                }
            }

            function formatCurrency(amount) {
                const sign = amount >= 0 ? '+' : '';
                return sign + amount.toLocaleString('vi-VN') + ' ₫';
            }

            // Confirm button handler
            document.getElementById('confirmChangeBtn').addEventListener('click', function() {
                if (!selectedRoomId) {
                    alert('Vui lòng chọn phòng muốn đổi!');
                    return;
                }

                // TODO: Submit room change request
                alert(`Đang xử lý đổi sang phòng #${selectedRoomId}...\n`);
                
                // Will be replaced with actual form submission:
                // const form = document.createElement('form');
                // form.method = 'POST';
                // form.action = '/account/booking/{{ $booking->id }}/change-room';
                // form.innerHTML = `
                //     @csrf
                //     <input type="hidden" name="new_room_id" value="${selectedRoomId}">
                // `;
                // document.body.appendChild(form);
                // form.submit();
            });

            // Filter handlers
            document.getElementById('filterRoomType').addEventListener('change', filterRooms);
            document.getElementById('filterPrice').addEventListener('change', filterRooms);

            function filterRooms() {
                const typeFilter = document.getElementById('filterRoomType').value;
                const priceFilter = document.getElementById('filterPrice').value;
                const roomWrappers = document.querySelectorAll('.room-card-wrapper');

                roomWrappers.forEach(wrapper => {
                    let show = true;
                    const roomType = wrapper.getAttribute('data-type');
                    const roomPrice = parseFloat(wrapper.getAttribute('data-price'));
                    const priceDiff = roomPrice - currentRoomPrice;

                    // Type filter
                    if (typeFilter && roomType !== typeFilter) {
                        show = false;
                    }

                    // Price filter
                    if (priceFilter === 'same' && priceDiff !== 0) show = false;
                    if (priceFilter === 'cheaper' && priceDiff >= 0) show = false;
                    if (priceFilter === 'expensive' && priceDiff <= 0) show = false;

                    wrapper.style.display = show ? 'block' : 'none';
                });
            }
            
            // Confirm change button handler
            document.getElementById('confirmChangeBtn').addEventListener('click', function() {
                if (!selectedRoomId) {
                    alert('Vui lòng chọn loại phòng!');
                    return;
                }
                
                const nights = {{ $meta['nights'] ?? 1 }};
                
                // Find selected room data from allAvailableRooms (contains API data)
                const roomData = allAvailableRooms.find(r => r.id == selectedRoomId) || {};
                
                // CRITICAL FIX: Use price_difference from API (server-side calculated)
                // This ensures consistency with updatePriceSummary() display
                const priceDiffPerNight = roomData.price_difference ?? (selectedRoomPrice - currentRoomPrice);
                const priceDiff = Math.round(priceDiffPerNight * nights);  // Total difference
                
                // Use same formula as updatePriceSummary() to ensure consistency
                const newBookingTotal = Math.round(oldTotal + priceDiff);
                const currentBookingTotal = oldTotal;
                
                // For display purposes
                const oldRoomTotal = Math.round(currentRoomPrice * nights);
                const newRoomTotal = Math.round(selectedRoomPrice * nights);
                
                console.log('💰 Payment calculation (consistent with preview):', {
                    priceDiffPerNight,
                    priceDiff,
                    oldTotal,
                    newBookingTotal,
                    currentRoomPrice,
                    selectedRoomPrice
                });
                
                // Get selected room info
                const selectedWrapper = document.querySelector(`.room-card-wrapper[data-room-id="${selectedRoomId}"]`);
                const selectedRoomCode = selectedWrapper.querySelector('.card-title').textContent.trim();
                
                // Show confirmation based on price difference
                if (priceDiff > 0) {
                    // UPGRADE - Show payment confirmation
                    showUpgradeConfirmation(selectedRoomCode, priceDiff, nights, oldRoomTotal, newRoomTotal, currentBookingTotal, newBookingTotal);
                } else if (priceDiff < 0) {
                    // DOWNGRADE - Show voucher preview
                    showDowngradeConfirmation(selectedRoomCode, priceDiff, nights, oldRoomTotal, newRoomTotal, currentBookingTotal, newBookingTotal);
                } else {
                    // SAME PRICE - Direct confirmation
                    showSamePriceConfirmation(selectedRoomCode);
                }
            });
            
            function showUpgradeConfirmation(roomCode, priceDiff, nights, oldRoomTotal, newRoomTotal, currentBookingTotal, newBookingTotal) {
                const depositPct = {{ $booking->snapshot_meta['deposit_percentage'] ?? 50 }};
                const currentDeposit = {{ $booking->deposit_amount ?? 0 }};
                const voucherDiscount = {{ $booking->voucher_discount ?? 0 }};  // Voucher được giữ lại
                
                // Calculate based on FULL BOOKING total (not just changed room)
                const newDepositRequired = newBookingTotal * (depositPct / 100);
                const basePaymentNeeded = Math.max(0, newDepositRequired - currentDeposit);
                
                // ===== NEW: Get selected vouchers discount =====
                const selectedVouchers = document.querySelectorAll('.voucher-checkbox:checked');
                let selectedVoucherTotal = 0;
                let selectedVoucherCodes = [];
                selectedVouchers.forEach(cb => {
                    selectedVoucherTotal += parseFloat(cb.dataset.value) || 0;
                    const label = cb.closest('.voucher-item')?.querySelector('strong.text-primary');
                    if (label) selectedVoucherCodes.push(label.textContent.trim());
                });
                
                // Calculate final payment after voucher
                const finalPaymentNeeded = Math.max(0, basePaymentNeeded - selectedVoucherTotal);
                const excessVoucher = Math.max(0, selectedVoucherTotal - basePaymentNeeded);
                const actualVoucherUsed = Math.min(selectedVoucherTotal, basePaymentNeeded);
                
                console.log('💳 Upgrade confirmation:', {
                    depositPct,
                    currentBookingTotal,
                    newBookingTotal,
                    newDepositRequired,
                    currentDeposit,
                    basePaymentNeeded,
                    selectedVoucherTotal,
                    finalPaymentNeeded,
                    excessVoucher
                });
                
                // Build payment section HTML
                let paymentSectionHtml = '';
                let voucherBonusHtml = '';
                let voucherWarningHtml = '';
                let iconType = 'question';
                let confirmButtonText = '';
                let confirmButtonColor = '#0d6efd';
                
                // Build voucher warning if excess exists
                if (excessVoucher > 0 && selectedVoucherTotal > 0) {
                    voucherWarningHtml = `
                        <div class="alert alert-warning border-warning mb-3">
                            <h6 class="fw-semibold mb-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>Lưu ý quan trọng
                            </h6>
                            <p class="mb-2 small">
                                • Voucher của bạn: <strong>${formatMoney(selectedVoucherTotal)}</strong><br>
                                • Số tiền cần thanh toán: <strong>${formatMoney(basePaymentNeeded)}</strong><br>
                                • Số tiền thừa: <strong class="text-danger">${formatMoney(excessVoucher)}</strong>
                            </p>
                            <hr class="my-2">
                            <p class="mb-0 small">
                                <i class="bi bi-info-circle me-1"></i>
                                Voucher sẽ được tính là <strong class="text-danger">ĐÃ SỬ DỤNG</strong> sau khi xác nhận. 
                                Phần thừa ${formatMoney(excessVoucher)} sẽ <strong>KHÔNG</strong> được hoàn lại.
                            </p>
                        </div>
                    `;
                }
                
                if (finalPaymentNeeded > 0) {
                    // Need to pay more (with or without voucher)
                    iconType = 'info';
                    confirmButtonColor = '#0d6efd';
                    confirmButtonText = '<i class="bi bi-credit-card me-1"></i> Thanh toán VNPay';
                    
                    let voucherAppliedText = selectedVoucherTotal > 0 
                        ? `<small class="text-success d-block mt-2">
                               <i class="bi bi-check-circle me-1"></i>
                               Đã giảm ${formatMoney(selectedVoucherTotal)} từ voucher
                           </small>` 
                        : '';
                    
                    paymentSectionHtml = `
                        <div class="alert alert-warning border-warning mb-0">
                            <h6 class="fw-semibold mb-2">
                                <i class="bi bi-wallet2 me-1"></i>Thanh toán bổ sung
                            </h6>
                            <p class="mb-2 small">Bạn cần thanh toán thêm để hoàn tất nâng cấp:</p>
                            <div class="bg-white p-3 rounded shadow-sm text-center">
                                <div class="text-muted small mb-1">Số tiền cần thanh toán</div>
                                <div class="display-6 fw-bold text-danger">${formatMoney(finalPaymentNeeded)}</div>
                            </div>
                            ${voucherAppliedText}
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Bạn sẽ được chuyển đến cổng thanh toán VNPay
                            </small>
                        </div>
                    `;
                } else if (selectedVoucherTotal > 0) {
                    // Voucher covers everything
                    iconType = 'success';
                    confirmButtonColor = '#28a745';
                    confirmButtonText = '<i class="bi bi-check-circle me-1"></i> Xác nhận đổi phòng';
                    
                    paymentSectionHtml = `
                        <div class="alert alert-success border-success mb-0">
                            <div class="text-center">
                                <div class="fs-2 mb-2">✓</div>
                                <h6 class="fw-bold text-success mb-2">MIỄN PHÍ - VOUCHER ĐÃ COVER</h6>
                                <p class="mb-0">Voucher ${formatMoney(selectedVoucherTotal)} đã cover toàn bộ chi phí nâng cấp!</p>
                            </div>
                        </div>
                    `;
                } else {
                    // Already paid enough (no voucher)
                    iconType = 'success';
                    confirmButtonColor = '#28a745';
                    confirmButtonText = '<i class="bi bi-check-circle me-1"></i> Xác nhận đổi phòng';
                    
                    paymentSectionHtml = `
                        <div class="alert alert-success border-success mb-0">
                            <div class="text-center">
                                <div class="fs-2 mb-2">✓</div>
                                <h6 class="fw-bold text-success mb-2">KHÔNG CẦN THANH TOÁN THÊM</h6>
                                <p class="mb-0">Bạn đã cọc đủ tiền cho phòng mới!</p>
                            </div>
                        </div>
                    `;
                    
                    // Voucher bonus info
                    voucherBonusHtml = `
                        <div class="alert alert-info border-info mb-0 mt-3">
                            <h6 class="fw-semibold mb-2">
                                <i class="bi bi-gift me-1"></i>🎁 Bonus: Tự động áp dụng voucher
                            </h6>
                            <p class="mb-2 small">Nếu bạn có voucher hoàn tiền từ lần downgrade trước, voucher đó sẽ được <strong class="text-success">tự động áp dụng</strong> vào đơn này!</p>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Bạn vẫn sẽ giữ được lợi ích từ voucher khi nâng cấp lại phòng.
                            </small>
                        </div>
                    `;
                }
                
                Swal.fire({
                    icon: iconType,
                    title: 'Xác nhận đổi phòng',
                    width: '600px',
                    html: `
                        <div class="text-start">
                            <div class="alert alert-primary border-0 mb-3">
                                <h6 class="mb-2">
                                    <i class="bi bi-arrow-up-circle me-1"></i>
                                    Bạn đang nâng cấp lên loại phòng tốt hơn! 🌟
                                </h6>
                                <p class="mb-0 small">Khách sạn sẽ tự động chọn phòng tốt nhất còn trống cho bạn</p>
                            </div>
                            
                            {{-- Room Comparison --}}
                            <div class="card border mb-3">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold mb-3">So sánh phòng</h6>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="border-end pe-2">
                                                <div class="text-muted small mb-1">Loại phòng hiện tại</div>
                                                <div class="fw-bold">{{ $currentRoom->loaiPhong->ten ?? 'N/A' }}</div>
                                                <div class="text-muted small">Phòng của bạn</div>
                                                <div class="mt-2">
                                                    <span class="badge bg-secondary">${formatMoney(oldRoomTotal)}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="ps-2">
                                                <div class="text-muted small mb-1">Loại phòng mới</div>
                                                <div class="fw-bold text-primary">${roomCode}</div>
                                                <div class="text-muted small">Khách sạn sẽ chọn phòng tốt nhất</div>
                                                <div class="mt-2">
                                                    <span class="badge bg-primary">${formatMoney(newRoomTotal)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Price Breakdown --}}
                            <div class="card border mb-3">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold mb-3">
                                        <i class="bi bi-receipt me-1"></i>Chi tiết thanh toán
                                    </h6>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Booking hiện tại:</span>
                                        <strong>${formatMoney(currentBookingTotal)}</strong>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Booking mới:</span>
                                        <strong class="text-primary">${formatMoney(newBookingTotal)}</strong>
                                    </div>
                                    
                                    ${voucherDiscount > 0 ? `
                                    <div class="d-flex justify-content-between mb-2 bg-success bg-opacity-10 p-2 rounded">
                                        <span class="text-success">
                                            <i class="bi bi-ticket-perforated me-1"></i>Voucher được giữ lại:
                                        </span>
                                        <strong class="text-success">-${formatMoney(voucherDiscount)}</strong>
                                    </div>
                                    ` : ''}
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Số đêm:</span>
                                        <strong>${nights} đêm</strong>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Chênh lệch/đêm:</span>
                                        <strong class="text-danger">+${formatMoney(priceDiff/nights)}</strong>
                                    </div>
                                    
                                    <hr class="my-2">
                                    
                                    <div class="d-flex justify-content-between p-2 bg-light rounded">
                                        <span class="fw-semibold text-danger">Tổng chênh lệch:</span>
                                        <h5 class="mb-0 text-danger">+${formatMoney(priceDiff)}</h5>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Deposit Info --}}
                            <div class="card border mb-3">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold mb-3">
                                        <i class="bi bi-wallet2 me-1"></i>Thông tin cọc
                                    </h6>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Đã cọc:</span>
                                        <strong>${formatMoney(currentDeposit)}</strong>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Cọc cần cho phòng mới (${depositPct}%):</span>
                                        <strong class="text-primary">${formatMoney(newDepositRequired)}</strong>
                                    </div>
                                    
                                    ${selectedVoucherTotal > 0 ? `
                                    <div class="d-flex justify-content-between mb-2 bg-success bg-opacity-10 p-2 rounded">
                                        <span class="text-success">
                                            <i class="bi bi-gift me-1"></i>Voucher DOWNGRADE áp dụng:
                                        </span>
                                        <strong class="text-success">-${formatMoney(selectedVoucherTotal)}</strong>
                                    </div>
                                    ` : ''}
                                    
                                    <hr class="my-2">
                                    
                                    <div class="d-flex justify-content-between p-2 ${finalPaymentNeeded > 0 ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10'} rounded">
                                        <span class="fw-semibold">${finalPaymentNeeded > 0 ? 'Cần thanh toán thêm:' : 'Đã đủ:'}</span>
                                        <h5 class="mb-0 ${finalPaymentNeeded > 0 ? 'text-danger' : 'text-success'}">${finalPaymentNeeded > 0 ? formatMoney(finalPaymentNeeded) : '✓ 0đ'}</h5>
                                    </div>
                                </div>
                            </div>
                            
                            ${voucherWarningHtml}
                            ${paymentSectionHtml}
                            ${voucherBonusHtml}
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: confirmButtonText,
                    cancelButtonText: 'Hủy',
                    confirmButtonColor: confirmButtonColor,
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitRoomChange();
                    }
                });
            }
            
            function showSamePriceConfirmation(roomCode) {
                Swal.fire({
                    title: 'Xác nhận đổi phòng',
                    html: `
                        <p>Bạn có chắc muốn đổi sang phòng <strong>${roomCode}</strong>?</p>
                        <p class="text-success">Không cần thanh toán thêm (cùng giá).</p>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Xác nhận đổi phòng',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitRoomChange();
                    }
                });
            }
            
            function showDowngradeConfirmation(roomCode, priceDiff, nights, oldRoomTotal, newRoomTotal, currentBookingTotal, newBookingTotal) {
                const depositPct = {{ $booking->snapshot_meta['deposit_percentage'] ?? 50 }};
                const currentDeposit = {{ $booking->deposit_amount ?? 0 }};
                const voucherDiscount = {{ $booking->voucher_discount ?? 0 }};  // Voucher được giữ lại
                
                // CRITICAL FIX: Use Math.round() to avoid decimal issues (e.g., 904.999,5đ)
                const newDepositRequired = Math.round(newBookingTotal * (depositPct / 100));
                const refundAmount = Math.round(currentDeposit - newDepositRequired);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Xác nhận đổi phòng',
                    width: '600px',
                    html: `
                        <div class="text-start">
                            <div class="alert alert-info border-0 mb-3">
                                <h6 class="mb-2">
                                    <i class="bi bi-arrow-down-circle me-1"></i>
                                    Bạn đang chọn phòng rẻ hơn - Tiết kiệm tiền! 🎉
                                </h6>
                                <p class="mb-0 small">Số tiền chênh lệch sẽ được hoàn lại qua voucher</p>
                            </div>
                            
                            {{-- Room Comparison --}}
                            <div class="card border mb-3">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold mb-3">So sánh phòng</h6>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="border-end pe-2">
                                                <div class="text-muted small mb-1">Phòng hiện tại</div>
                                                <div class="fw-bold">{{ $currentRoom->loaiPhong->ten ?? 'N/A' }}</div>
                                                <div class="text-muted small">#{{ $currentRoom->ma_phong ?? 'N/A' }}</div>
                                                <div class="mt-2">
                                                    <span class="badge bg-secondary">${formatMoney(oldRoomTotal)}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="ps-2">
                                                <div class="text-muted small mb-1">Phòng mới</div>
                                                <div class="fw-bold text-success">${roomCode}</div>
                                                <div class="text-muted small">-</div>
                                                <div class="mt-2">
                                                    <span class="badge bg-success">${formatMoney(newRoomTotal)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Price Breakdown --}}
                            <div class="card border mb-3">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold mb-3">
                                        <i class="bi bi-receipt me-1"></i>Chi tiết thanh toán
                                    </h6>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Booking hiện tại:</span>
                                        <strong>${formatMoney(currentBookingTotal)}</strong>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Booking mới:</span>
                                        <strong class="text-success">${formatMoney(newBookingTotal)}</strong>
                                    </div>
                                    
                                    ${voucherDiscount > 0 ? `
                                    <div class="d-flex justify-content-between mb-2 bg-success bg-opacity-10 p-2 rounded">
                                        <span class="text-success">
                                            <i class="bi bi-ticket-perforated me-1"></i>Voucher được giữ lại:
                                        </span>
                                        <strong class="text-success">-${formatMoney(voucherDiscount)}</strong>
                                    </div>
                                    ` : ''}
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Số đêm:</span>
                                        <strong>${nights} đêm</strong>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Chênh lệch/đêm:</span>
                                        <strong class="text-success">${formatMoney(Math.abs(priceDiff/nights))}</strong>
                                    </div>
                                    
                                    <hr class="my-2">
                                    
                                    <div class="d-flex justify-content-between p-2 bg-light rounded">
                                        <span class="fw-semibold text-success">Tổng tiết kiệm:</span>
                                        <h5 class="mb-0 text-success">${formatMoney(Math.abs(priceDiff))}</h5>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Deposit Info --}}
                            <div class="card border mb-3">
                                <div class="card-body p-3">
                                    <h6 class="fw-semibold mb-3">
                                        <i class="bi bi-wallet2 me-1"></i>Thông tin cọc
                                    </h6>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Đã cọc:</span>
                                        <strong>${formatMoney(currentDeposit)}</strong>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Cọc cần cho phòng mới (${depositPct}%):</span>
                                        <strong class="text-success">${formatMoney(newDepositRequired)}</strong>
                                    </div>
                                    
                                    <hr class="my-2">
                                    
                                    <div class="d-flex justify-content-between p-2 bg-success bg-opacity-10 rounded">
                                        <span class="fw-semibold">Chênh lệch cọc:</span>
                                        <h5 class="mb-0 text-success">${formatMoney(refundAmount)}</h5>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Voucher Gift Box --}}
                            <div class="alert alert-success border-success mb-0" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                                <div class="text-center">
                                    <div class="fs-1 mb-2">🎁</div>
                                    <h5 class="fw-bold text-success mb-2">BẠN SẼ NHẬN</h5>
                                    <div class="bg-white p-3 rounded shadow-sm mb-3">
                                        <div class="text-muted small mb-1">Voucher hoàn tiền</div>
                                        <div class="display-6 fw-bold text-success">${formatMoney(refundAmount)}</div>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>Hạn sử dụng: 30 ngày
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Voucher sẽ tự động được thêm vào tài khoản của bạn và có thể sử dụng cho booking tiếp theo
                                    </small>
                                </div>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Xác nhận đổi phòng',
                    cancelButtonText: 'Hủy',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitRoomChange();
                    }
                });
            }
            
            function submitRoomChange() {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/account/bookings/{{ $booking->id }}/change-room';
                
                // CSRF token
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);
                
                // Old room ID (which room to change)
                const oldRoomInput = document.createElement('input');
                oldRoomInput.type = 'hidden';
                oldRoomInput.name = 'old_room_id';
                oldRoomInput.value = oldRoomId;
                form.appendChild(oldRoomInput);
                
                // New room ID
                const roomInput = document.createElement('input');
                roomInput.type = 'hidden';
                roomInput.name = 'new_room_id';
                roomInput.value = selectedRoomId;
                form.appendChild(roomInput);
                
                // ===== NEW: Add selected vouchers =====
                const selectedVouchers = document.querySelectorAll('.voucher-checkbox:checked');
                selectedVouchers.forEach(checkbox => {
                    const voucherInput = document.createElement('input');
                    voucherInput.type = 'hidden';
                    voucherInput.name = 'voucher_ids[]';
                    voucherInput.value = checkbox.value;
                    form.appendChild(voucherInput);
                });
                
                document.body.appendChild(form);
                form.submit();
            }
            
            function formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
            }
            
            // ===== NEW: Voucher handling functions =====
            let availableVouchers = [];
            let selectedVoucherIds = [];
            
            async function loadAvailableVouchers() {
                const bookingId = {{ $booking->id }};
                const voucherSection = document.getElementById('voucherSelectionSection');
                const loadingEl = document.getElementById('loadingVouchers');
                const listEl = document.getElementById('availableVouchersList');
                const noVouchersEl = document.getElementById('noVouchersMessage');
                
                // Always show section when loading
                voucherSection.classList.remove('d-none');
                
                try {
                    const response = await fetch(`/account/bookings/${bookingId}/available-vouchers`);
                    const data = await response.json();
                    
                    console.log('🎫 Vouchers loaded:', data);
                    
                    loadingEl.style.display = 'none';
                    
                    if (data.success && data.vouchers.length > 0) {
                        availableVouchers = data.vouchers;
                        renderVouchers(data.vouchers);
                        listEl.style.display = 'block';
                        noVouchersEl.classList.add('d-none');
                    } else {
                        // No vouchers available
                        listEl.style.display = 'none';
                        noVouchersEl.classList.remove('d-none');
                    }
                } catch (error) {
                    console.error('Error loading vouchers:', error);
                    loadingEl.style.display = 'none';
                    loadingEl.innerHTML = '<p class="text-danger small">Lỗi tải voucher</p>';
                    // Still show section to display error
                    listEl.style.display = 'none';
                    noVouchersEl.classList.add('d-none');
                }
            }
            
            function renderVouchers(vouchers) {
                const listEl = document.getElementById('availableVouchersList');
                
                listEl.innerHTML = vouchers.map(v => `
                    <div class="form-check border rounded p-3 mb-2 voucher-item" style="transition: all 0.2s;">
                        <input class="form-check-input voucher-checkbox" 
                               type="checkbox" 
                               value="${v.id}" 
                               id="voucher_${v.id}"
                               data-value="${v.value}"
                               onchange="recalculateWithVouchers()">
                        <label class="form-check-label w-100 cursor-pointer" for="voucher_${v.id}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong class="text-primary">${v.code}</strong>
                                    <p class="text-muted small mb-0 mt-1">${v.note || ''}</p>
                                </div>
                                <div class="text-end ms-3">
                                    <span class="badge bg-success fs-6">
                                        -${new Intl.NumberFormat('vi-VN').format(v.value)}đ
                                    </span>
                                    <small class="text-muted d-block mt-1">HSD: ${v.end_date}</small>
                                </div>
                            </div>
                        </label>
                    </div>
                `).join('');
            }
            
            function recalculateWithVouchers() {
                const checkboxes = document.querySelectorAll('.voucher-checkbox:checked');
                let totalVoucherDiscount = 0;
                
                checkboxes.forEach(cb => {
                    totalVoucherDiscount += parseFloat(cb.dataset.value) || 0;
                });
                
                // Update summary section
                const summaryEl = document.getElementById('selectedVouchersSummary');
                const totalDiscountEl = document.getElementById('totalVoucherDiscount');
                
                if (totalVoucherDiscount > 0) {
                    summaryEl.classList.remove('d-none');
                    totalDiscountEl.textContent = formatMoney(totalVoucherDiscount);
                } else {
                    summaryEl.classList.add('d-none');
                }
                
                // Update payment info with voucher discount
                const paymentInfoEl = document.getElementById('paymentNeededInfo');
                if (paymentInfoEl) {
                    // Recalculate payment needed
                    const depositPct = {{ $meta['deposit_percentage'] ?? 50 }};
                    const currentDeposit = {{ $booking->deposit_amount ?? 0 }};
                    const newTotal = parseFloat(document.getElementById('newTotal').textContent.replace(/[^0-9]/g, '')) || 0;
                    const newDepositRequired = newTotal * (depositPct / 100);
                    const basePaymentNeeded = Math.max(0, newDepositRequired - currentDeposit);
                    const finalPaymentNeeded = Math.max(0, basePaymentNeeded - totalVoucherDiscount);
                    
                    // Check for excess voucher value
                    const excessVoucher = Math.max(0, totalVoucherDiscount - basePaymentNeeded);
                    const actualUsed = Math.min(totalVoucherDiscount, basePaymentNeeded);
                    
                    let excessWarning = '';
                    if (excessVoucher > 0 && totalVoucherDiscount > 0) {
                        excessWarning = `<div class="alert alert-warning py-2 mt-2 small">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Lưu ý quan trọng:</strong><br>
                            • Voucher của bạn: <strong>${formatMoney(totalVoucherDiscount)}</strong><br>
                            • Số tiền cần thanh toán: <strong>${formatMoney(basePaymentNeeded)}</strong><br>
                            • Số tiền thừa: <strong class="text-danger">${formatMoney(excessVoucher)}</strong><br>
                            <hr class="my-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Voucher sẽ được tính là <strong class="text-danger">ĐÃ SỬ DỤNG</strong> sau khi xác nhận. 
                            Phần thừa ${formatMoney(excessVoucher)} sẽ không được hoàn lại.
                        </div>`;
                    }
                    
                    let paymentInfoHtml = '';
                    
                    if (finalPaymentNeeded > 0) {
                        // Need to pay via VNPay
                        paymentInfoHtml = `<div class="d-flex justify-content-between">
                               <span class="text-danger fw-semibold">Cần thanh toán:</span>
                               <h5 class="mb-0 text-danger">${formatMoney(finalPaymentNeeded)}</h5>
                           </div>
                           ${totalVoucherDiscount > 0 ? `<small class="text-success d-block mt-1">
                               <i class="bi bi-check-circle me-1"></i>
                               Đã giảm ${formatMoney(totalVoucherDiscount)} từ voucher
                           </small>` : ''}
                           <small class="text-muted d-block mt-2">
                               <i class="bi bi-info-circle me-1"></i>
                               Bạn sẽ được chuyển đến cổng thanh toán VNPay
                           </small>`;
                    } else if (totalVoucherDiscount > 0) {
                        // Voucher covers everything
                        paymentInfoHtml = `<div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle me-1"></i>
                                Miễn phí! Voucher đã cover toàn bộ chi phí.
                            </div>${excessWarning}`;
                    } else {
                        // No voucher, no payment (shouldn't happen in upgrade)
                        paymentInfoHtml = `<div class="d-flex justify-content-between">
                               <span class="text-danger fw-semibold">Cần thanh toán:</span>
                               <h5 class="mb-0 text-danger">${formatMoney(basePaymentNeeded)}</h5>
                           </div>
                           <small class="text-muted d-block mt-2">
                               <i class="bi bi-info-circle me-1"></i>
                               Bạn sẽ được chuyển đến cổng thanh toán VNPay
                           </small>`;
                    }
                    
                    paymentInfoEl.innerHTML = paymentInfoHtml;
                }
            }
            
            // Check for room change success and show notification
            @if(session('room_change_success'))
                @php
                    $changeInfo = session('room_change_success');
                @endphp
                Swal.fire({
                    icon: 'success',
                    title: '🎉 Đổi phòng thành công!',
                    html: `
                        <div class="text-start">
                            @if(isset($changeInfo['voucher_code']))
                                {{-- Downgrade with voucher --}}
                                <p class="mb-3">Phòng đã được thay đổi và voucher hoàn tiền đã được tạo!</p>
                                <div class="alert alert-success mb-3">
                                    <h5 class="mb-2"><i class="bi bi-gift me-2"></i>Voucher hoàn tiền</h5>
                                    <div class="bg-white p-3 rounded border">
                                        <div class="text-center mb-2">
                                            <h4 class="text-primary mb-0">{{ $changeInfo['voucher_code'] }}</h4>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between">
                                            <span>Giá trị:</span>
                                            <strong class="text-success">{{ number_format($changeInfo['refund_amount']) }}đ</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Hạn sử dụng:</span>
                                            <strong>30 ngày</strong>
                                        </div>
                                    </div>
                                    <p class="mt-2 mb-0 small text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Voucher đã được thêm vào tài khoản của bạn
                                    </p>
                                </div>
                            @else
                                {{-- Upgrade or same price --}}
                                <p class="mb-3">Thanh toán đã được xác nhận. Thông tin phòng đã được cập nhật:</p>
                            @endif
                            
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-muted">Loại phòng cũ:</td>
                                        <td><strong class="text-danger">{{ $changeInfo['old_room'] }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Loại phòng mới:</td>
                                        <td><strong class="text-success">{{ $changeInfo['new_room'] }}</strong></td>
                                    </tr>
                                    @if(!isset($changeInfo['voucher_code']))
                                        <tr>
                                            <td class="text-muted">Chênh lệch:</td>
                                            <td><strong class="text-primary">{{ number_format($changeInfo['price_difference']) }}đ</strong></td>
                                        </tr>
                                        @if(isset($changeInfo['voucher_discount']) && $changeInfo['voucher_discount'] > 0)
                                            <tr>
                                                <td class="text-muted"><i class="bi bi-gift me-1"></i> Voucher áp dụng:</td>
                                                <td><strong class="text-success">-{{ number_format($changeInfo['voucher_discount']) }}đ</strong></td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td class="text-muted">Đã thanh toán:</td>
                                            <td><strong class="text-info">{{ number_format($changeInfo['payment_amount']) }}đ</strong></td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            <p class="mt-3 mb-0 small text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Chi tiết đổi phòng đã được ghi nhận trong lịch sử bên dưới.
                            </p>
                        </div>
                    `,
                    confirmButtonText: 'Đã hiểu',
                    confirmButtonColor: '#28a745',
                    width: '500px',
                    showClass: {
                        popup: 'animate__animated animate__bounceIn'
                    }
                });
            @endif
            
            // Check for errors and show notification
            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'Đóng',
                    confirmButtonColor: '#dc3545'
                });
            @endif
            
            // Check for info messages
            @if(session('info'))
                Swal.fire({
                    icon: 'info',
                    title: 'Thông báo',
                    text: '{{ session('info') }}',
                    confirmButtonText: 'Đã hiểu',
                    confirmButtonColor: '#0dcaf0'
                });
            @endif
        });
    </script>
@endpush<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-2px);
    }
    .transition {
        transition: all 0.3s ease;
    }
    .min-width-0 {
        min-width: 0;
    }
</style>
