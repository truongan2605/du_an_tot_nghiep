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
        $totalAmount = $booking->snapshot_total ?? ($booking->tong_tien ?? 0);
        $paidAmount = $booking->deposit_amount ?? 0;
        $depositType = 50;
        if ($paidAmount > 0 && $totalAmount > 0) {
            $percentage = ($paidAmount / $totalAmount) * 100;
            $depositType = ($percentage >= 90) ? 100 : 50;
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
                                                <h6>Check-in</h6>
                                                <small class="text-muted">{{ $formatDateVi($booking->ngay_nhan_phong, 'd M Y') }} 14:00</small>
                                            </div>
                                        </div>
                                        
                                        <div class="timeline-step {{ $booking->trang_thai === 'hoan_thanh' ? 'completed' : '' }}">
                                            <div class="timeline-marker">
                                                <i class="bi {{ $booking->trang_thai === 'hoan_thanh' ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <h6>Check-out</h6>
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
                                        <div class="h5 mb-0 fw-bold text-primary">{{ number_format($booking->snapshot_total ?? ($booking->tong_tien ?? 0), 0, ',', '.') }} VND</div>
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
                                                <span class="text-muted">Còn lại:</span>
                                                <strong class="text-danger">{{ number_format($totalAmount - $paidAmount, 0, ',', '.') }} ₫</strong>
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
                                </div>
                            </div>

                            {{-- Rooms Table --}}
                            <div class="mb-4">
                                <h6 class="mb-3 d-flex align-items-center"><i class="bi bi-door-open-fill me-2 text-primary"></i> Phòng</h6>
                                @if ($booking->datPhongItems && $booking->datPhongItems->count())
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Phòng</th>
                                                    <th class="text-end">Giá/Đêm</th>
                                                    <th class="text-end">Số đêm</th>
                                                    <th class="text-end">Tổng phụ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($booking->datPhongItems as $it)
                                                    @php
                                                        $roomName = $it->phong->name ?? ($it->loai_phong->name ?? 'Phòng ' . ($it->phong_id ?? 'N/A'));
                                                        $pricePer = $it->gia_tren_dem ?? 0;
                                                        $nights = $it->so_dem ?? 1;
                                                        $qty = $it->so_luong ?? 1;
                                                        // Always calculate from current price (handles room changes)
                                                        $subtotal = $pricePer * $nights * $qty;
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
                                                        <td class="text-end text-muted small">{{ number_format($pricePer, 0, ',', '.') }} VND</td>
                                                        <td class="text-end fw-semibold">{{ $nights }}</td>
                                                        <td class="text-end fw-semibold text-primary">{{ number_format($subtotal, 0, ',', '.') }} VND</td>
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
                                    $total = $booking->snapshot_total ?? ($booking->tong_tien ?? 0);
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

    {{-- Room Change Modal --}}
    @if(in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan']) && $daysUntilCheckIn >= 1)
        <div class="modal fade" id="changeRoomModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary bg-opacity-10 border-0">
                        <h5 class="modal-title text-primary">
                            <i class="bi bi-arrow-left-right me-2"></i>
                            Đổi Phòng - {{ $booking->ma_tham_chieu }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        {{-- Room Change Policy --}}
                        <div class="alert alert-info border-info mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i>
                                <div class="flex-grow-1">
                                    <h6 class="alert-heading mb-2">
                                        <i class="bi bi-shield-check me-1"></i> Chính sách đổi phòng
                                    </h6>
                                    <ul class="mb-0 small">
                                        <li class="mb-1">
                                            <strong>Thời gian:</strong> Chỉ áp dụng trước <strong>24 giờ</strong> check-in
                                        </li>
                                        <li class="mb-1">
                                            <strong>Phòng đắt hơn:</strong> Thanh toán chênh lệch qua VNPay
                                        </li>
                                        <li class="mb-1">
                                            <strong>Phòng rẻ hơn:</strong> Nhận voucher (hạn 6 tháng) hoặc hoàn tiền về ví VNPay
                                        </li>
                                        <li class="mb-1">
                                            <strong>Giới hạn:</strong> Tối đa <strong>2 lần</strong> đổi phòng cho mỗi booking
                                        </li>
                                        <li class="mb-0">
                                            <strong>Miễn phí:</strong> Không mất phí nếu đổi sang phòng cùng giá
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Current Room Section --}}
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-pin-fill me-2"></i>Phòng hiện tại
                                @if($booking->datPhongItems && $booking->datPhongItems->count() > 1)
                                    <span class="badge bg-info">{{ $booking->datPhongItems->count() }} phòng</span>
                                @endif
                            </h6>
                            
                            @if($booking->datPhongItems && $booking->datPhongItems->count() > 0)
                                @foreach($booking->datPhongItems as $index => $currentItem)
                                    @php
                                        $currentRoom = $currentItem->phong;
                                        $currentRoomType = $currentItem->loaiPhong;
                                    @endphp
                                    
                                    <div class="card border-primary bg-light {{ $index > 0 ? 'mt-3' : '' }}">
                                        <div class="card-body p-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-2">
                                                    @if($currentRoom && $currentRoom->images && $currentRoom->images->count() > 0)
                                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($currentRoom->images->first()->image_path) }}" 
                                                             alt="Room" class="img-thumbnail" style="height: 80px; object-fit: cover;">
                                                    @else
                                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                                                             style="height: 80px; border-radius: 8px;">
                                                            <i class="bi bi-image fs-3"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-5">
                                                    <h6 class="mb-1">
                                                        <strong class="text-primary">#{{ $currentRoom->ma_phong ?? 'N/A' }}</strong>
                                                        - {{ $currentRoomType->ten ?? 'N/A' }}
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class="bi bi-people me-1"></i>{{ $currentRoomType->so_nguoi ?? 2 }} người
                                                        <span class="mx-2">•</span>
                                                        <i class="bi bi-star-fill text-warning me-1"></i>{{ $currentRoomType->hang ?? 'Standard' }}
                                                    </small>
                                                </div>
                                                <div class="col-md-3 text-center">
                                                    <div class="text-muted small mb-1">Giá hiện tại</div>
                                                    <div class="h6 mb-0 text-success fw-bold">
                                                        {{ number_format($currentItem->gia_tren_dem ?? 0, 0, ',', '.') }} ₫<small class="text-muted">/đêm</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary"
                                                            onclick="openChangeRoomModal('{{ $currentRoom->id }}', '{{ $currentRoom->ma_phong }}')">
                                                        <i class="bi bi-shuffle me-1"></i>Đổi phòng
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <hr class="my-4">

                        {{-- Available Rooms Section (Hidden by default, shows when clicking change room) --}}
                        <div class="mb-4" id="availableRoomsSection" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted mb-0">
                                    <i class="bi bi-house-door me-2"></i>
                                    <span id="changeRoomTitle">Chọn phòng mới</span>
                                </h6>
                                <div class="d-flex gap-2">
                                    <select class="form-select form-select-sm" id="filterRoomType" style="width: auto;">
                                        <option value="">Tất cả loại phòng</option>
                                        {{-- Options will be populated dynamically --}}
                                    </select>
                                    <select class="form-select form-select-sm" id="filterPrice" style="width: auto;">
                                        <option value="">Mọi mức giá</option>
                                        <option value="same">Cùng giá</option>
                                        <option value="cheaper">Rẻ hơn (Downgrade)</option>
                                        <option value="expensive">Đắt hơn (Upgrade)</option>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="hideAvailableRooms()">
                                        <i class="bi bi-x-lg me-1"></i>Hủy
                                    </button>
                                </div>
                            </div>

                            {{-- Available Rooms Grid (Template - will be populated via AJAX) --}}
                            <div class="row g-3" id="availableRoomsGrid">
                                {{-- Loading State --}}
                                <div class="col-12 text-center py-5" id="loadingRooms">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Đang tải...</span>
                                    </div>
                                    <p class="text-muted mt-3">Đang tìm phòng trống...</p>
                                </div>

                                {{-- Sample Room Cards (These will be generated dynamically) --}}
                                {{-- Example structure for JavaScript to replicate:
                                <div class="col-md-4 room-card-wrapper" data-room-id="123" data-price="2000000" data-type="suite">
                                    <div class="card room-card h-100 border-2">
                                        <div class="position-relative">
                                            <img src="..." class="card-img-top" style="height: 150px; object-fit: cover;">
                                            <span class="position-absolute top-0 end-0 m-2 badge bg-success">+500,000đ</span>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title mb-2">#305 - Suite Room</h6>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span class="text-muted small">Giá/đêm</span>
                                                <strong class="text-success">2,000,000đ</strong>
                                            </div>
                                            <button class="btn btn-outline-primary btn-sm w-100 select-room-btn">
                                                <i class="bi bi-check-circle me-1"></i>Chọn phòng
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                --}}
                            </div>
                        </div>

                        {{-- Price Summary Section (Hidden by default, shown when room selected) --}}
                        <div id="priceSummarySection" class="d-none">
                            <hr class="my-4">
                            <div class="alert alert-info border-0">
                                <h6 class="mb-3">
                                    <i class="bi bi-calculator me-2"></i>Tổng quan giá
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Tổng tiền cũ:</span>
                                            <strong id="oldTotal">{{ number_format($booking->tong_tien ?? 0, 0, ',', '.') }} ₫</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Số đêm còn lại:</span>
                                            <strong id="nightsRemaining">{{ $meta['nights'] ?? 1 }} đêm</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Chênh lệch/đêm:</span>
                                            <strong id="priceDiffPerNight" class="text-primary">0 ₫</strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-white border">
                                            <div class="card-body p-3">
                                                <div class="text-muted small mb-1">Tổng chênh lệch</div>
                                                <div class="h4 mb-2" id="totalDifference">0 ₫</div>
                                                <hr class="my-2">
                                                <div class="text-muted small mb-1">Tổng tiền mới</div>
                                                <div class="h3 mb-0 text-success fw-bold" id="newTotal">{{ number_format($booking->tong_tien ?? 0, 0, ',', '.') }} ₫</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Chênh lệch được tính dựa trên số đêm còn lại của kỳ nghỉ
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
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
    @endif

    {{-- Room Changes History --}}
    @if($booking->roomChanges && $booking->roomChanges->count() > 0)
        <div class="container mt-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-light py-3">
                    <h6 class="mb-0 fw-bold">
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
            let currentRoomPrice = {{ $booking->datPhongItems->first()->gia_tren_dem ?? 0 }};  // Default to first, will be updated
            const nightsRemaining = {{ $meta['nights'] ?? 1 }};
            const oldTotal = {{ $booking->tong_tien ?? 0 }};
            
            // Map of room prices for lookup
            const roomPrices = {
                @foreach($booking->datPhongItems as $item)
                    {{ $item->phong_id }}: {{ $item->gia_tren_dem }},
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
                
                // CRITICAL: Update currentRoomPrice based on which room is being changed
                currentRoomPrice = roomPrices[roomId] || 0;
                
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
                renderRoomCards(allAvailableRooms);
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

                rooms.forEach(room => {
                    const priceDiff = room.price - currentRoomPrice;
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

                    const cardHtml = `
                        <div class="col-md-4 room-card-wrapper" data-room-id="${room.id}" data-price="${room.price}" data-type="${room.type}">
                            <div class="card room-card h-100">
                                <div class="position-relative overflow-hidden">
                                    <img src="${room.image}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="${room.name}">
                                    <span class="position-absolute top-0 end-0 m-2 badge ${badgeClass}">${badgeText}</span>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title mb-2 text-primary fw-bold">#${room.code} - ${room.name}</h6>
                                    <div class="text-muted small mb-2">
                                        <i class="bi bi-people me-1"></i>${room.capacity} người
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted small">Giá/đêm</span>
                                        <strong class="text-success">${room.price.toLocaleString('vi-VN')}đ</strong>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm w-100 select-room-btn" data-room-id="${room.id}">
                                        <i class="bi bi-check-circle me-1"></i>Chọn phòng
                                    </button>
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
                const roomCards = document.querySelectorAll('.select-room-btn');
                
                roomCards.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const roomId = this.getAttribute('data-room-id');
                        const wrapper = this.closest('.room-card-wrapper');
                        const price = parseFloat(wrapper.getAttribute('data-price'));
                        
                        selectRoom(roomId, price, wrapper);
                    });
                });
            }

            function selectRoom(roomId, price, wrapper) {
                // Remove previous selection
                document.querySelectorAll('.room-card').forEach(card => {
                    card.classList.remove('selected');
                });
                document.querySelectorAll('.select-room-btn').forEach(btn => {
                    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Chọn phòng';
                });

                // Mark new selection
                const card = wrapper.querySelector('.room-card');
                card.classList.add('selected');
                
                const btn = wrapper.querySelector('.select-room-btn');
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Đã chọn';
                
                selectedRoomId = roomId;
                selectedRoomPrice = price;

                // Update price summary
                updatePriceSummary(price);

                // Enable confirm button
                document.getElementById('confirmChangeBtn').disabled = false;
            }

            function updatePriceSummary(newRoomPrice) {
                const priceDiffPerNight = newRoomPrice - currentRoomPrice;
                const totalDifference = priceDiffPerNight * nightsRemaining;
                const newTotal = oldTotal + totalDifference;

                // Show price summary section
                document.getElementById('priceSummarySection').classList.remove('d-none');

                // Update values
                document.getElementById('priceDiffPerNight').textContent = formatCurrency(priceDiffPerNight);
                document.getElementById('totalDifference').textContent = formatCurrency(totalDifference);
                document.getElementById('newTotal').textContent = formatCurrency(newTotal);

                // Color coding
                const diffElement = document.getElementById('totalDifference');
                if (totalDifference > 0) {
                    diffElement.classList.remove('text-success');
                    diffElement.classList.add('text-danger');
                } else if (totalDifference < 0) {
                    diffElement.classList.remove('text-danger');
                    diffElement.classList.add('text-success');
                } else {
                    diffElement.classList.remove('text-danger', 'text-success');
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
                alert(`Đang xử lý đổi sang phòng #${selectedRoomId}...\n(Chức năng backend đang được phát triển)`);
                
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
                    alert('Vui lòng chọn phòng!');
                    return;
                }
                
                const nights = {{ $meta['nights'] ?? 1 }};
                const oldRoomTotal = currentRoomPrice * nights;  // This room only
                const newRoomTotal = selectedRoomPrice * nights;  // New room only
                const priceDiff = newRoomTotal - oldRoomTotal;
                
                // CRITICAL: Calculate FULL BOOKING total (for multi-room support)
                const currentBookingTotal = {{ $booking->tong_tien }};  // All rooms
                const newBookingTotal = currentBookingTotal - oldRoomTotal + newRoomTotal;
                
                console.log('💰 Payment calculation:', {
                    oldRoomTotal,
                    newRoomTotal,
                    priceDiff,
                    currentBookingTotal,
                    newBookingTotal
                });
                
                // Get selected room info
                const selectedWrapper = document.querySelector(`.room-card-wrapper[data-room-id="${selectedRoomId}"]`);
                const selectedRoomCode = selectedWrapper.querySelector('.card-title').textContent.trim();
                
                // Show confirmation based on price difference
                if (priceDiff > 0) {
                    // UPGRADE - Show payment confirmation
                    showUpgradeConfirmation(selectedRoomCode, priceDiff, nights, oldRoomTotal, newRoomTotal, currentBookingTotal, newBookingTotal);
                } else if (priceDiff < 0) {
                    // DOWNGRADE - Show refund/voucher options
                    Swal.fire({
                        icon: 'info',
                        title: 'Chức năng downgrade',
                        text: 'Tính năng đổi sang phòng rẻ hơn đang được phát triển.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    // SAME PRICE - Direct confirmation
                    showSamePriceConfirmation(selectedRoomCode);
                }
            });
            
            function showUpgradeConfirmation(roomCode, priceDiff, nights, oldRoomTotal, newRoomTotal, currentBookingTotal, newBookingTotal) {
                const depositPct = {{ $booking->snapshot_meta['deposit_percentage'] ?? 50 }};
                const currentDeposit = {{ $booking->deposit_amount ?? 0 }};
                
                // Calculate based on FULL BOOKING total (not just changed room)
                const newDepositRequired = newBookingTotal * (depositPct / 100);
                const paymentNeeded = newDepositRequired - currentDeposit;
                
                console.log('💳 Upgrade confirmation:', {
                    depositPct,
                    currentBookingTotal,
                    newBookingTotal,
                    newDepositRequired,
                    currentDeposit,
                    paymentNeeded
                });
                
                Swal.fire({
                    title: 'Xác nhận đổi phòng',
                    html: `
                        <div class="text-start">
                            <div class="mb-3">
                                <strong>Phòng cũ:</strong> {{ $currentRoom->ma_phong ?? 'N/A' }}<br>
                                <strong>Giá phòng cũ:</strong> ${formatMoney(oldRoomTotal)}
                            </div>
                            <div class="mb-3">
                                <strong>Phòng mới:</strong> ${roomCode}<br>
                                <strong>Giá phòng mới:</strong> ${formatMoney(newRoomTotal)}
                            </div>
                            <hr>
                            <div class="mb-3 text-danger">
                                <strong>Chênh lệch phòng:</strong> +${formatMoney(priceDiff)}<br>
                                <small>(${formatMoney(priceDiff/nights)}/đêm × ${nights} đêm)</small>
                            </div>
                            <hr>
                            <div class="mb-3 bg-light p-2 rounded">
                                <strong>Tổng booking hiện tại:</strong> ${formatMoney(currentBookingTotal)}<br>
                                <strong>Tổng booking mới:</strong> ${formatMoney(newBookingTotal)}<br>
                                <strong>Deposit cần (${depositPct}%):</strong> ${formatMoney(newDepositRequired)}<br>
                                <strong>Đã cọc:</strong> ${formatMoney(currentDeposit)}
                            </div>
                            <div class="alert alert-primary mb-0">
                                <strong class="fs-5">CẦN THANH TOÁN:</strong><br>
                                <span class="fs-4 text-primary">${formatMoney(paymentNeeded)}</span>
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-credit-card me-1"></i> Thanh toán VNPay',
                    cancelButtonText: 'Hủy',
                    confirmButtonColor: '#0d6efd'
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
                
                document.body.appendChild(form);
                form.submit();
            }
            
            function formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
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
                            <p class="mb-3">Thanh toán đã được xác nhận. Thông tin phòng đã được cập nhật:</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-muted">Phòng cũ:</td>
                                        <td><strong class="text-danger">#{{ $changeInfo['old_room'] }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Phòng mới:</td>
                                        <td><strong class="text-success">#{{ $changeInfo['new_room'] }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Chênh lệch:</td>
                                        <td><strong class="text-primary">{{ number_format($changeInfo['price_difference']) }}đ</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Đã thanh toán:</td>
                                        <td><strong class="text-info">{{ number_format($changeInfo['payment_amount']) }}đ</strong></td>
                                    </tr>
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
@endpush