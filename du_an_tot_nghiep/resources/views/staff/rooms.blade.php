@extends('layouts.admin')
@section('title', 'Quản Lý Phòng')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3 py-md-4">
    {{-- Header + Stats --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Quản lý phòng</h1>
        <div class="d-flex gap-3">
            @php
                $dangSuDung = $rooms->filter(fn($r) => $r->datPhongItems->pluck('datPhong.trang_thai')->contains('dang_su_dung'))->count();
                $choCheckin = $rooms->filter(fn($r) => $r->datPhongItems->pluck('datPhong.trang_thai')->contains('da_xac_nhan'))->count();
                $dangDonDep = $rooms->where('trang_thai', 'dang_don_dep')->count();
                $trong = $rooms->filter(fn($r) => !$r->datPhongItems->pluck('datPhong.trang_thai')->contains('dang_su_dung') && $r->trang_thai === 'trong')->count();
            @endphp
            <div class="d-flex gap-2">
            <span class="badge bg-success px-3 py-2">Trống: {{ $trong }}</span>
            <span class="badge bg-primary px-3 py-2">Chờ check-in: {{ $choCheckin }}</span>
            <span class="badge bg-warning px-3 py-2">Dọn dẹp: {{ $dangDonDep }}</span>
            <span class="badge bg-danger px-3 py-2">Đã check-in: {{ $dangSuDung }}</span>
        </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('staff.rooms') }}" class="row g-2 g-md-3 align-items-end">
                <div class="col-sm-6 col-md-4">
                    <input type="text" name="ma_phong" class="form-control" placeholder="Mã phòng..." value="{{ request('ma_phong') }}">
                </div>
                <div class="col-sm-6 col-md-3">
                    <select name="trang_thai" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="da_xac_nhan" {{ request('trang_thai') == 'da_xac_nhan' ? 'selected' : '' }}>Chờ check-in</option>
                        <option value="dang_su_dung" {{ request('trang_thai') == 'dang_su_dung' ? 'selected' : '' }}>Đã check-in</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Tìm</button>
                </div>
                <div class="col-sm-6 col-md-3">
                    <a href="{{ route('staff.rooms') }}" class="btn btn-outline-secondary w-100">Làm mới</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Rooms Grid --}}
    <div class="row g-3 g-md-4">
        @forelse ($rooms as $room)
            @php
                $allBookings = $room->datPhongItems()
                    ->with('datPhong.nguoiDung')
                    ->get()
                    ->map->datPhong
                    ->filter()
                    ->unique('id')
                    ->sortBy('ngay_nhan_phong');

                $currentBooking = $allBookings->first(fn($b) => in_array($b->trang_thai, ['da_xac_nhan', 'dang_su_dung']));
                $bookingCount = $allBookings->count();

                $status = 'trong';
                if ($currentBooking?->trang_thai === 'dang_su_dung') $status = 'dang_su_dung';
                elseif ($currentBooking?->trang_thai === 'da_xac_nhan') $status = 'cho_checkin';
                elseif ($room->trang_thai === 'dang_don_dep') $status = 'dang_don_dep';

                $statusMap = [
                    'trong' => ['label' => 'Trống', 'color' => 'success', 'bg' => 'bg-success-subtle'],
                    'cho_checkin' => ['label' => 'Chờ Check-in', 'color' => 'primary', 'bg' => 'bg-primary-subtle'],
                    'dang_su_dung' => ['label' => 'Đã Check-in', 'color' => 'danger', 'bg' => 'bg-danger-subtle'],
                    'dang_don_dep' => ['label' => 'Dọn', 'color' => 'warning', 'bg' => 'bg-warning-subtle']
                ];
                $st = $statusMap[$status];
            @endphp

            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="card h-100 border-0 shadow-sm hover-lift rounded-3">
                    <div class="card-header p-3 {{ $st['bg'] }} border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $room->ma_phong }}</h6>
                                <small class="text-muted">{{ $room->tang?->ten ?? '-' }}</small>
                            </div>
                            <span class="badge bg-{{ $st['color'] }}">{{ $st['label'] }}</span>
                        </div>
                    </div>

                    <div class="card-body p-3">
                        @if ($status === 'trong')
                            <div class="text-center py-3">
                                <i class="bi bi-check-lg text-success fs-1 d-block mb-2"></i>
                                <small class="text-muted">Sẵn sàng</small>
                            </div>

                        @elseif ($currentBooking)
                            <div class="mb-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <small class="fw-semibold text-dark">{{ $currentBooking->nguoiDung?->name ?? 'Khách' }}</small>
                                    <span class="badge bg-light text-dark small">{{ $bookingCount }} lượt</span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>{{ \Carbon\Carbon::parse($currentBooking->ngay_nhan_phong)->format('d/m H:i') }}</span>
                                    <span>{{ \Carbon\Carbon::parse($currentBooking->ngay_tra_phong)->format('d/m H:i') }}</span>
                                </div>
                            </div>

                        @else
                            <div class="text-center py-3">
                                <i class="bi bi-gear-fill text-warning fs-2 d-block mb-2"></i>
                                <small class="text-muted">Đang dọn dẹp</small>
                            </div>
                        @endif

                        {{-- Nút mở Modal --}}
                        @if($bookingCount > 0)
                            <div class="text-end mt-2">
                                <button type="button" class="btn btn-outline-info btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#bookingModal{{ $room->id }}"
                                        title="Xem tất cả bookings">
                                    <i class="bi bi-list-ul"></i> 
                                    <span class="badge bg-info">{{ $bookingCount }}</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="card-footer p-3 border-0 bg-light">
                        @if ($status === 'trong')
                            <button class="btn btn-success btn-sm w-100" disabled>Sẵn sàng</button>
                        @elseif ($currentBooking)
                            <a href="{{ route('staff.bookings.show', $currentBooking->id) }}" class="btn btn-primary btn-sm w-100">Xem booking</a>
                        @else
                            <form action="{{ route('staff.rooms.update', $room->id) }}" method="POST" class="w-100">
                                @csrf @method('PATCH')
                                <input type="hidden" name="trang_thai" value="trong">
                                <button type="submit" class="btn btn-success btn-sm w-100">Hoàn tất</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Modal Booking List --}}
            @if($bookingCount > 0)
            <div class="modal fade" id="bookingModal{{ $room->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-light border-0">
                            <h5 class="modal-title">
                                Lịch booking phòng <strong>{{ $room->ma_phong }}</strong>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="list-group list-group-flush">
                                @foreach($allBookings as $index => $booking)
                                @php
                                    $statusMap = [
                                        'da_xac_nhan' => ['label' => 'Đã Xác Nhận', 'class' => 'info'],
                                        'dang_su_dung' => ['label' => 'Đã Check-in', 'class' => 'primary'],
                                        'hoan_thanh' => ['label' => 'Hoàn thành', 'class' => 'success'],
                                        'da_huy' => ['label' => 'Hủy', 'class' => 'danger'],
                                    ];
                                    $bs = $statusMap[$booking->trang_thai] ?? ['label' => 'N/A', 'class' => 'secondary'];
                                @endphp
                                <div class="list-group-item px-3 py-2">
                                    <div class="row align-items-center g-2">
                                        <div class="col-auto">
                                            <div class="avatar-circle bg-primary text-white small">
                                                {{ $index + 1 }}
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="fw-bold text-primary small">{{ $booking->ma_tham_chieu ?? 'N/A' }}</div>
                                            <div class="small text-muted">
                                                {{ $booking->nguoiDung?->name ?? $booking->contact_name ?? 'Khách' }}
                                            </div>
                                        </div>
                                        <div class="col-auto text-end">
                                            <div class="small">
                                                {{ \Carbon\Carbon::parse($booking->ngay_nhan_phong)->format('d/m/Y') }}
                                            </div>
                                            <div class="small text-danger">
                                                {{ \Carbon\Carbon::parse($booking->ngay_tra_phong)->format('d/m/Y') }}
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <span class="badge bg-{{ $bs['class'] }} small">
                                                {{ $bs['label'] }}
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
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @empty
            <div class="col-12 text-center py-5">
                <i class="bi bi-building fs-1 text-muted mb-3 d-block"></i>
                <p class="text-muted">Không có phòng nào</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $rooms->links('pagination::bootstrap-5') }}
    </div>
</div>

<style>
.hover-lift {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}
.hover-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}
.avatar-circle {
    width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;
}
.bg-success-subtle { background-color: rgba(25,135,84,.05) !important; }
.bg-primary-subtle { background-color: rgba(13,110,253,.05) !important; }
.bg-danger-subtle { background-color: rgba(220,53,69,.05) !important; }
.bg-warning-subtle { background-color: rgba(255,193,7,.05) !important; }
</style>
@endsection