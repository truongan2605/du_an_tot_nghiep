@extends('layouts.admin')

@section('title', 'Quản Lý Hoàn Tiền')

@section('content')
<div class="container-fluid p-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Quản Lý Yêu Cầu Hoàn Tiền</h4>
            <p class="text-muted small mb-0">Xem và xử lý các yêu cầu hoàn tiền từ việc hủy đặt phòng</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="small text-muted">Chờ xử lý</div>
                            <h4 class="mb-0">{{ $stats['pending'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="small text-muted">Đã duyệt</div>
                            <h4 class="mb-0">{{ $stats['approved'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="small text-muted">Hoàn tiền</div>
                            <h4 class="mb-0">{{ $stats['completed'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="small text-muted">Từ chối</div>
                            <h4 class="mb-0">{{ $stats['rejected'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Trạng thái</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        <option value="pending" @selected(request('status') == 'pending')>Chờ xử lý</option>
                        <option value="approved" @selected(request('status') == 'approved')>Đã duyệt</option>
                        <option value="completed" @selected(request('status') == 'completed')>Hoàn tiền</option>
                        <option value="rejected" @selected(request('status') == 'rejected')>Từ chối</option>
                    </select>
                </div>
                <div class="col-auto flex-grow-1">
                    <label class="form-label small mb-1">Tìm kiếm (Mã đặt phòng)</label>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="VD: DP12345678" class="form-control form-control-sm" />
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm">Lọc</button>
                    <a href="{{ route('staff.refunds.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Mã đặt phòng</th>
                            <th>Khách hàng</th>
                            <th>Booking Info</th>
                            <th class="text-end">Refund</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($refunds as $refund)
                            @php
                                $booking = $refund->datPhong;
                                $meta = $booking->snapshot_meta ?? [];
                                $depositType = $meta['deposit_percentage'] ?? 50;
                                
                                // Calculate days before checkin
                                $cancelledAt = $booking->cancelled_at ? \Carbon\Carbon::parse($booking->cancelled_at) : $refund->requested_at;
                                $checkInDate = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
                                $now = \Carbon\Carbon::now();
                                $daysBeforeCheckIn = $cancelledAt->diffInDays($checkInDate, false); // Positive if cancelled BEFORE check-in
                            @endphp
                            
                            <tr>
                                {{-- Expand icon --}}
                                <td>
                                    <button class="btn btn-sm btn-link text-decoration-none" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#details{{ $refund->id }}" 
                                            aria-expanded="false">
                                        <i class="fas fa-chevron-right rotate-icon"></i>
                                    </button>
                                </td>
                                
                                {{-- Booking Code --}}
                                <td>
                                    <div>
                                        <strong class="text-primary">{{ $booking->ma_tham_chieu }}</strong>
                                    </div>
                                    <small class="text-muted">
                                        <i class="far fa-calendar"></i> 
                                        {{ $booking->ngay_nhan_phong->format('d/m/Y') }}
                                    </small>
                                </td>
                                
                                {{-- Customer --}}
                                <td>
                                    <div>{{ $booking->nguoiDung->name ?? 'N/A' }}</div>
                                    <small class="text-muted">
                                        <i class="fas fa-phone"></i> {{ $booking->contact_phone ?? '' }}
                                    </small>
                                </td>
                                
                                {{-- Booking Info --}}
                                <td>
                                    <div class="small">
                                        <span class="badge {{ $depositType == 100 ? 'bg-success' : 'bg-info' }} mb-1">
                                            {{ $depositType }}% payment
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        Cancelled: {{ $cancelledAt->diffForHumans() }}
                                    </div>
                                    <div class="small">
                                        <span class="badge {{ $daysBeforeCheckIn >= 7 ? 'bg-success' : ($daysBeforeCheckIn >= 3 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ abs($daysBeforeCheckIn) }} days {{ $daysBeforeCheckIn >= 0 ? 'before' : 'after' }}
                                        </span>
                                    </div>
                                </td>
                                
                                {{-- Refund Amount --}}
                                <td class="text-end">
                                    <div>
                                        <strong class="text-success">{{ number_format($refund->amount, 0, ',', '.') }} ₫</strong>
                                    </div>
                                    <small class="text-muted">
                                        {{ $refund->percentage }}% of {{ number_format($booking->deposit_amount, 0, ',', '.') }} ₫
                                    </small>
                                </td>
                                
                                {{-- Status --}}
                                <td class="text-center">
                                    <span class="badge {{ $refund->status_badge_class }}">
                                        {{ $refund->status_label }}
                                    </span>
                                    @if($refund->processed_at)
                                        <div class="small text-muted mt-1">
                                            {{ $refund->processed_at->format('d/m H:i') }}
                                        </div>
                                    @endif
                                </td>
                                
                                {{-- Actions --}}
                                <td class="text-center">
                                    @if($refund->status === 'pending')
                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $refund->id }}">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $refund->id }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @elseif($refund->status === 'approved')
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#completeModal{{ $refund->id }}">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </button>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                            
                            {{-- Expandable Details Row --}}
                            <tr class="collapse" id="details{{ $refund->id }}">
                                <td colspan="7" class="bg-light">
                                    <div class="p-3">
                                        <div class="row">
                                            {{-- Booking Details --}}
                                            <div class="col-md-4">
                                                <h6 class="text-primary mb-2">
                                                    <i class="fas fa-calendar-alt"></i> Booking Details
                                                </h6>
                                                <table class="table table-sm table-borderless mb-0">
                                                    <tr>
                                                        <td class="text-muted">Check-in:</td>
                                                        <td><strong>{{ $booking->ngay_nhan_phong->format('d/m/Y') }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Check-out:</td>
                                                        <td><strong>{{ $booking->ngay_tra_phong->format('d/m/Y') }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Nights:</td>
                                                        <td>{{ $booking->ngay_nhan_phong->diffInDays($booking->ngay_tra_phong) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Total:</td>
                                                        <td><strong>{{ number_format($booking->tong_tien, 0, ',', '.') }} ₫</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Deposit:</td>
                                                        <td>
                                                            <span class="badge {{ $depositType == 100 ? 'bg-success' : 'bg-info' }}">
                                                                {{ $depositType }}%
                                                            </span>
                                                            {{ number_format($booking->deposit_amount, 0, ',', '.') }} ₫
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            
                                            {{-- Cancellation Details --}}
                                            <div class="col-md-4">
                                                <h6 class="text-danger mb-2">
                                                    <i class="fas fa-ban"></i> Cancellation Details
                                                </h6>
                                                <table class="table table-sm table-borderless mb-0">
                                                    <tr>
                                                        <td class="text-muted">Cancelled at:</td>
                                                        <td><strong>{{ $cancelledAt->format('d/m/Y H:i') }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Days before:</td>
                                                        <td>
                                                            <span class="badge {{ $daysBeforeCheckIn >= 7 ? 'bg-success' : ($daysBeforeCheckIn >= 3 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                                                {{ abs(round($daysBeforeCheckIn, 1)) }} days
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Reason:</td>
                                                        <td>{{ $booking->cancellation_reason ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Requested:</td>
                                                        <td>{{ $refund->requested_at->format('d/m/Y H:i') }}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                            
                                            {{-- Refund Calculation --}}
                                            <div class="col-md-4">
                                                <h6 class="text-success mb-2">
                                                    <i class="fas fa-calculator"></i> Refund Calculation
                                                </h6>
                                                <table class="table table-sm table-borderless mb-0">
                                                    <tr>
                                                        <td class="text-muted">Paid amount:</td>
                                                        <td><strong>{{ number_format($booking->deposit_amount, 0, ',', '.') }} ₫</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Refund %:</td>
                                                        <td>
                                                            <span class="badge bg-primary">{{ $refund->percentage }}%</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Refund amount:</td>
                                                        <td><strong class="text-success">{{ number_format($refund->amount, 0, ',', '.') }} ₫</strong></td>
                                                    </tr>
                                                    @if($refund->admin_note)
                                                        <tr>
                                                            <td class="text-muted" colspan="2">
                                                                <div class="alert alert-info alert-sm mb-0 mt-2 p-2">
                                                                    <small><strong>Admin note:</strong><br>{{ $refund->admin_note }}</small>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </table>
                                            </div>
                                        </div>
                                        
                                        {{-- Refund Policy Reference --}}
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="text-info mb-2">
                                                    <i class="fas fa-info-circle"></i> Chính Sách Hoàn Tiền (Tham Khảo)
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="small">Thời gian hủy</th>
                                                                <th class="small text-center">Đặt cọc 50%</th>
                                                                <th class="small text-center">Thanh toán 100%</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr class="{{ $daysBeforeCheckIn >= 7 ? 'table-success' : '' }}">
                                                                <td class="small">≥ 7 ngày trước check-in</td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-success">Hoàn 100%</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-success">Hoàn 90%</span>
                                                                </td>
                                                            </tr>
                                                            <tr class="{{ $daysBeforeCheckIn >= 3 && $daysBeforeCheckIn < 7 ? 'table-warning' : '' }}">
                                                                <td class="small">3-6 ngày trước</td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-warning text-dark">Hoàn 70%</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-warning text-dark">Hoàn 60%</span>
                                                                </td>
                                                            </tr>
                                                            <tr class="{{ $daysBeforeCheckIn >= 1 && $daysBeforeCheckIn < 3 ? 'table-warning' : '' }}">
                                                                <td class="small">1-2 ngày trước</td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-warning text-dark">Hoàn 30%</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-warning text-dark">Hoàn 40%</span>
                                                                </td>
                                                            </tr>
                                                            <tr class="{{ $daysBeforeCheckIn < 1 ? 'table-danger' : '' }}">
                                                                <td class="small">< 24 giờ</td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-danger">Không hoàn</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-warning text-dark">Hoàn 20%</span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="alert alert-info alert-sm mb-0 p-2">
                                                    <small>
                                                        <strong><i class="fas fa-check-circle"></i> Case hiện tại:</strong>
                                                        Deposit <strong>{{ $depositType }}%</strong>, 
                                                        hủy <strong>{{ abs(round($daysBeforeCheckIn, 1)) }} ngày</strong> {{ $daysBeforeCheckIn >= 0 ? 'trước' : 'sau' }} check-in
                                                        → Policy: <strong class="text-primary">{{ $refund->percentage }}%</strong>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- Approve Modal --}}
                            <div class="modal fade" id="approveModal{{ $refund->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('staff.refunds.approve', $refund->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Duyệt Yêu Cầu Hoàn Tiền</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Xác nhận duyệt yêu cầu hoàn tiền:</p>
                                                <ul>
                                                    <li><strong>Mã đặt phòng:</strong> {{ $refund->datPhong->ma_tham_chieu }}</li>
                                                    <li><strong>Khách hàng:</strong> {{ $refund->datPhong->nguoiDung->name ?? 'N/A' }}</li>
                                                    <li><strong>Số tiền:</strong> {{ number_format($refund->amount, 0, ',', '.') }} ₫</li>
                                                </ul>
                                                <div class="mb-3">
                                                    <label class="form-label">Ghi chú (tùy chọn)</label>
                                                    <textarea name="note" class="form-control" rows="2" placeholder="Thêm ghi chú..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-success">Xác nhận duyệt</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Reject Modal --}}
                            <div class="modal fade" id="rejectModal{{ $refund->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('staff.refunds.reject', $refund->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Từ Chối Yêu Cầu Hoàn Tiền</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> Vui lòng cung cấp lý do từ chối
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Lý do từ chối <span class="text-danger">*</span></label>
                                                    <textarea name="reason" class="form-control" rows="3" placeholder="Nhập lý do từ chối..." required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-danger">Xác nhận từ chối</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Complete Modal --}}
                            <div class="modal fade" id="completeModal{{ $refund->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('staff.refunds.complete', $refund->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Đánh Dấu Đã Hoàn Tiền</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Xác nhận đã chuyển tiền cho khách hàng:</p>
                                                <ul>
                                                    <li><strong>Số tiền:</strong> {{ number_format($refund->amount, 0, ',', '.') }} ₫</li>
                                                </ul>
                                                <div class="mb-3">
                                                    <label class="form-label">Ghi chú xác nhận (tùy chọn)</label>
                                                    <textarea name="note" class="form-control" rows="2" placeholder="VD: Đã chuyển qua VNPay..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-primary">Xác nhận đã hoàn</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <div>Không có yêu cầu hoàn tiền nào</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-3">
                {{ $refunds->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<style>
    .rotate-icon {
        transition: transform 0.3s ease;
    }
    
    [aria-expanded="true"] .rotate-icon {
        transform: rotate(90deg);
    }
    
    .collapse.show {
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
</style>
@endsection
