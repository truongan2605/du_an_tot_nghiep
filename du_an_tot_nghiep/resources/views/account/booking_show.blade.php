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

        // Calculate countdown
        $checkInDate = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
        $now = \Carbon\Carbon::now();
        $daysUntilCheckIn = (int) $now->diffInDays($checkInDate, false);
        $hoursUntilCheckIn = (int) $now->diffInHours($checkInDate, false);
        
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
                                                        $subtotal = $it->tong_item ?? $pricePer * $nights * $qty;
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
@endsection

@push('styles')
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