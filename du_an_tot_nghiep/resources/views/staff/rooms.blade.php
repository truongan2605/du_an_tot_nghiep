@extends('layouts.staff')

@section('title', 'Quản Lý Phòng')

@section('content')
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Quản Lý Phòng</h2>
        <div class="d-flex gap-2">
            <span class="badge bg-success px-3 py-2">Trống: {{ $rooms->where('trang_thai', 'trong')->count() }}</span>
            <span class="badge bg-primary px-3 py-2">Đang ở: {{ $rooms->where('trang_thai', 'dang_o')->count() }}</span>
            <span class="badge bg-warning px-3 py-2">Dọn dẹp: {{ $rooms->where('trang_thai', 'dang_don_dep')->count() }}</span>
        </div>
    </div>

    {{-- Form lọc --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('staff.rooms') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Mã Phòng</label>
                    <input type="text" name="ma_phong" class="form-control" 
                           placeholder="Nhập mã phòng..." value="{{ request('ma_phong') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Trạng Thái</label>
                    <select name="trang_thai" class="form-select">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="trong" {{ request('trang_thai') == 'trong' ? 'selected' : '' }}>Trống</option>
                        <option value="dang_o" {{ request('trang_thai') == 'dang_o' ? 'selected' : '' }}>Đang ở</option>
                        <option value="dang_don_dep" {{ request('trang_thai') == 'dang_don_dep' ? 'selected' : '' }}>Đang dọn dẹp</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-search"></i> Tìm kiếm
                    </button>
                    <a href="{{ route('staff.rooms') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Danh sách phòng dạng card --}}
    <div class="row g-3">
        @forelse ($rooms as $room)
            @php
                $statusConfig = [
                    'trong' => ['label' => 'Trống', 'icon' => 'door-open', 'color' => 'success'],
                    'dang_o' => ['label' => 'Đang ở', 'icon' => 'person-check', 'color' => 'primary'],
                    'dang_don_dep' => ['label' => 'Dọn dẹp', 'icon' => 'brush', 'color' => 'warning'],
                ];
                $status = $statusConfig[$room->trang_thai] ?? ['label' => 'N/A', 'icon' => 'question', 'color' => 'secondary'];

                $activeBookings = $room->datPhongItems
                    ->map(fn($item) => $item->datPhong)
                    ->filter()
                    ->unique('id')
                    ->sortBy('ngay_nhan_phong');
                
                $currentBooking = $activeBookings->first();
                $bookingCount = $activeBookings->count();
            @endphp

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0 room-card">
                    {{-- Header --}}
                    <div class="card-header bg-{{ $status['color'] }} bg-opacity-10 border-0 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $room->ma_phong }}</h5>
                            <small class="text-muted">{{ $room->tang?->ten ?? 'Chưa có tầng' }}</small>
                        </div>
                        <span class="badge bg-{{ $status['color'] }} rounded-pill">
                            <i class="bi bi-{{ $status['icon'] }}"></i> {{ $status['label'] }}
                        </span>
                    </div>

                    {{-- Body --}}
                    <div class="card-body">
                        @if($room->trang_thai === 'trong')
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle display-4 d-block mb-2"></i>
                                <p class="mb-0">Phòng sẵn sàng</p>
                            </div>
                        @elseif($currentBooking)
                            <div class="booking-info">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-person-circle text-primary me-2"></i>
                                    <span class="fw-semibold">{{ $currentBooking->nguoiDung?->name ?? $currentBooking->contact_name ?? 'Khách' }}</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-calendar-check text-success me-2"></i>
                                    <small>{{ \Carbon\Carbon::parse($currentBooking->ngay_nhan_phong)->format('d/m/Y') }}</small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-calendar-x text-danger me-2"></i>
                                    <small>{{ \Carbon\Carbon::parse($currentBooking->ngay_tra_phong)->format('d/m/Y') }}</small>
                                </div>
                                
                                @if($bookingCount > 1)
                                    <div class="alert alert-info py-2 px-3 mt-3 mb-0 small">
                                        <i class="bi bi-info-circle"></i> Còn {{ $bookingCount - 1 }} booking khác
                                    </div>
                                @endif
                            </div>
                        @elseif($room->trang_thai === 'dang_don_dep')
                            <div class="text-center py-4 text-warning">
                                <i class="bi bi-hourglass-split display-4 d-block mb-2"></i>
                                <p class="mb-0">Đang dọn dẹp...</p>
                            </div>
                        @endif
                    </div>

                    {{-- Footer Actions --}}
                    <div class="card-footer bg-transparent border-0 pt-0">
                        <div class="d-flex gap-2 flex-wrap">
                            @if ($room->trang_thai === 'trong')
                                <button class="btn btn-outline-secondary btn-sm flex-grow-1" disabled>
                                    <i class="bi bi-check2"></i> Sẵn sàng
                                </button>

                            @elseif ($room->trang_thai === 'dang_o' && $currentBooking && \Carbon\Carbon::parse($currentBooking->ngay_tra_phong)->isToday())
                                <form action="{{ route('staff.checkout.process', $currentBooking->id) }}" method="POST" class="flex-grow-1">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm w-100"
                                            onclick="return confirm('Xác nhận check-out phòng {{ $room->ma_phong }}?')">
                                        <i class="bi bi-box-arrow-left"></i> Check-out
                                    </button>
                                </form>
                                <a href="{{ route('staff.bookings.show', $currentBooking->id) }}" 
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>

                            @elseif ($room->trang_thai === 'dang_o' && $currentBooking)
                                <a href="{{ route('staff.bookings.show', $currentBooking->id) }}" 
                                   class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="bi bi-eye"></i> Xem chi tiết
                                </a>

                            @elseif ($room->trang_thai === 'dang_don_dep')
                                <form action="{{ route('staff.rooms.update', $room->id) }}" method="POST" class="flex-grow-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="trang_thai" value="trong">
                                    <button type="submit" class="btn btn-success btn-sm w-100"
                                            onclick="return confirm('Xác nhận hoàn tất dọn dẹp?')">
                                        <i class="bi bi-check-circle"></i> Hoàn tất
                                    </button>
                                </form>
                            @endif
                            
                            @if($bookingCount > 0)
                                <button type="button" class="btn btn-outline-info btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#bookingModal{{ $room->id }}"
                                        title="Xem tất cả bookings">
                                    <i class="bi bi-list-ul"></i> 
                                    <span class="badge bg-info">{{ $bookingCount }}</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Modal chi tiết bookings --}}
            @if($bookingCount > 0)
            <div class="modal fade" id="bookingModal{{ $room->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title">
                                <i class="bi bi-door-open text-primary"></i> 
                                Lịch booking phòng <strong>{{ $room->ma_phong }}</strong>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach($activeBookings as $index => $booking)
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="avatar-circle bg-primary text-white">
                                                {{ $index + 1 }}
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="fw-bold text-primary">{{ $booking->ma_tham_chieu }}</div>
                                            <div class="small text-muted">
                                                <i class="bi bi-person"></i> 
                                                {{ $booking->nguoiDung?->name ?? $booking->contact_name ?? 'Khách' }}
                                            </div>
                                        </div>
                                        <div class="col-auto text-end">
                                            <div class="small">
                                                <i class="bi bi-calendar-check text-success"></i>
                                                {{ \Carbon\Carbon::parse($booking->ngay_nhan_phong)->format('d/m/Y') }}
                                            </div>
                                            <div class="small">
                                                <i class="bi bi-calendar-x text-danger"></i>
                                                {{ \Carbon\Carbon::parse($booking->ngay_tra_phong)->format('d/m/Y') }}
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            @php
                                                $statusMap = [
                                                    'dang_cho' => ['label' => 'Chờ', 'class' => 'warning'],
                                                    'da_xac_nhan' => ['label' => 'Đã XN', 'class' => 'info'],
                                                    'dang_o' => ['label' => 'Đang ở', 'class' => 'primary'],
                                                    'da_gan_phong' => ['label' => 'Đã gán', 'class' => 'success'],
                                                ];
                                                $bookingStatus = $statusMap[$booking->trang_thai] ?? ['label' => 'N/A', 'class' => 'secondary'];
                                            @endphp
                                            <span class="badge bg-{{ $bookingStatus['class'] }}">
                                                {{ $bookingStatus['label'] }}
                                            </span>
                                        </div>
                                        <div class="col-auto">
                                            <a href="{{ route('staff.bookings.show', $booking->id) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @empty
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <p class="text-muted mt-3 mb-0">Không tìm thấy phòng nào</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Phân trang --}}
    <div class="d-flex justify-content-center mt-4">
        {{ $rooms->links('pagination::bootstrap-5') }}
    </div>
</div>

{{-- Custom CSS --}}
<style>
    .room-card {
        transition: all 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }
    
    .card-header {
        padding: 1rem;
    }
    
    .card-body {
        min-height: 150px;
    }
    
    .booking-info {
        font-size: 0.9rem;
    }
    
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
    }
    
    .list-group-item {
        border-left: none;
        border-right: none;
        padding: 1rem;
    }
    
    .list-group-item:first-child {
        border-top: none;
    }
    
    .list-group-item:last-child {
        border-bottom: none;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .bg-opacity-10 {
        opacity: 0.1;
        background-blend-mode: lighten;
    }
</style>
@endsection