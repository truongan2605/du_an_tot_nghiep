@extends('layouts.staff')

@section('title', 'Dashboard Phòng Chuyên Nghiệp')

@section('content')
<div class="p-3">
    <h3 class="mb-3 fw-bold text-dark">Tổng Quan Phòng</h3>

    <!-- Legend -->
    <div class="mb-3 d-flex flex-wrap gap-2">
        <span class="badge rounded-pill bg-success">Trống</span>
        <span class="badge rounded-pill bg-warning text-dark">Đã Đặt</span>
        <span class="badge rounded-pill bg-primary">Đang Ở</span>
        <span class="badge rounded-pill bg-danger">Bảo Trì</span>
        <span class="badge rounded-pill bg-secondary">Không Sử Dụng</span>
    </div>

    @foreach($floors as $floor => $rooms)
        <div class="floor-section mb-5 p-3 rounded-3 shadow-sm bg-white">
            <h5 class="floor-title">Tầng {{ $floor }}</h5>
            <div class="row g-3">
                @foreach($rooms as $room)
                    @php
                        switch($room->trang_thai) {
                            case 'trong': $bg = 'bg-success text-white'; break;
                            case 'da_dat': $bg = 'bg-warning text-dark'; break;
                            case 'dang_o': $bg = 'bg-primary text-white'; break;
                            case 'bao_tri': $bg = 'bg-danger text-white'; break;
                            case 'khong_su_dung': $bg = 'bg-secondary text-white'; break;
                            default: $bg = 'bg-light'; break;
                        }
                        $guest = $room->phongDaDats->last()?->datPhong?->user->name ?? 'Chưa có khách';
                        $checkin = $room->phongDaDats->last()?->checkin_datetime?->format('d/m/Y') ?? '-';
                        $checkout = $room->phongDaDats->last()?->checkout_datetime?->format('d/m/Y') ?? '-';
                        $img = $room->firstImageUrl();
                    @endphp
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                        <div class="card room-tile shadow-sm h-100 rounded-2 overflow-hidden text-center"
                             data-bs-toggle="tooltip"
                             data-bs-html="true"
                             title="
                             <b>Phòng:</b> {{ $room->ma_phong }}<br>
                             <b>Loại:</b> {{ $room->loaiPhong->ten ?? '???' }}<br>
                             <b>Khách:</b> {{ $guest }}<br>
                             <b>Check-in:</b> {{ $checkin }}<br>
                             <b>Check-out:</b> {{ $checkout }}<br>
                             <b>Sức chứa:</b> {{ $room->suc_chua }} - Giường: {{ $room->so_giuong }}<br>
                             <b>Giá:</b> {{ number_format($room->gia_mac_dinh,0) }} VND
                             ">
                            <img src="{{ $img }}" class="card-img-top" alt="Room Image" style="height:100px; object-fit:cover;">
                            <div class="card-body p-1">
                                <small class="d-block fw-bold">{{ $room->ma_phong }}</small>
                                <small class="text-truncate d-block" style="max-width:100%;">{{ $room->loaiPhong->ten ?? '???' }}</small>
                            </div>
                            <div class="card-footer py-1">
                                <span class="badge {{ $bg }} rounded-pill">{{ ucfirst(str_replace('_',' ',$room->trang_thai)) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<style>
.floor-section {
    border-left: 5px solid #0d6efd;
    padding-left: 1rem;
    background: #f8f9fa;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.floor-section + .floor-section {
    margin-top: 2rem;
}
.floor-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #0d6efd;
    border-bottom: 2px solid #0d6efd;
    padding-bottom: 0.3rem;
    margin-bottom: 1rem;
}
.room-tile {
    transition: transform 0.15s, box-shadow 0.2s;
    cursor: pointer;
}
.room-tile:hover {
    transform: scale(1.05);
    box-shadow: 0 0 15px rgba(0,0,0,0.25);
}
.card-body small {
    font-size: 0.85rem;
}
.card-footer .badge {
    font-size: 0.75rem;
    padding: 0.3em 0.5em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el)
    });
});
</script>
@endsection
