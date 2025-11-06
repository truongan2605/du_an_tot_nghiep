@extends('layouts.staff')

@section('title', 'Dashboard Phòng Chuyên Nghiệp')

@section('content')
<div class="p-3">
    <h3 class="mb-3 fw-bold text-dark">Tổng Quan Phòng</h3>
    <div class="mb-3 d-flex flex-wrap gap-2">
        <span class="badge rounded-pill bg-success">Trống</span>
        <span class="badge rounded-pill bg-warning text-dark">Đã Đặt</span>
        <span class="badge rounded-pill bg-primary">Đang Ở</span>
        <span class="badge rounded-pill bg-danger">Bảo Trì</span>
        <span class="badge rounded-pill bg-secondary">Không Sử Dụng</span>
    </div>
    <div class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" id="roomSearch" placeholder="Tìm mã phòng (e.g., 101)...">
            </div>
            <div class="col-md-4">
                <select class="form-select" id="statusFilter">
                    <option value="">Tất cả trạng thái</option>
                    <option value="trong">Trống</option>
                    <option value="da_dat">Đã Đặt</option>
                    <option value="dang_o">Đang Ở</option>
                    <option value="bao_tri">Bảo Trì</option>
                    <option value="khong_su_dung">Không Sử Dụng</option>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-primary w-100" onclick="filterRooms()">Áp Dụng Lọc</button>
                <button class="btn btn-outline-secondary w-100 mt-1" onclick="clearFilters()">Xóa Lọc</button>
            </div>
        </div>
    </div>
    @foreach($floors as $floor => $rooms)
        @php
            $totalRooms = $rooms->count();
            $occupiedRooms = $rooms->where('trang_thai', '!=', 'trong')->count();
            $occupancyPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
        @endphp
        <div class="floor-section mb-5 p-3 rounded-3 shadow-sm bg-white">
            <h5 class="floor-title">
                Tầng {{ $floor }}
        
                <span class="badge bg-info ms-2">
                    Occupancy: {{ $occupancyPercent }}%
                </span>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $occupancyPercent }}%;" aria-valuenow="{{ $occupancyPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </h5>
            <div class="row g-3" id="floor-{{ $floor }}-rooms">
                @foreach($rooms->take(20) as $index => $room)  
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
                        $img = $room->firstImageUrl() ?? '/placeholder-room.jpg';  
                        $isHidden = $index >= 20 ? 'd-none' : '';  
                    @endphp
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 {{ $isHidden }}" data-room-code="{{ strtolower($room->ma_phong) }}" data-status="{{ $room->trang_thai }}">
                        <div class="card room-tile shadow-sm h-100 rounded-2 overflow-hidden text-center position-relative"
                             role="button" tabindex="0"
                             aria-label="Phòng {{ $room->ma_phong }} - Trạng thái: {{ ucfirst(str_replace('_', ' ', $room->trang_thai)) }}"
                             data-bs-toggle="tooltip"
                             data-bs-html="true"
                             data-status="{{ $room->trang_thai }}"
                             title="
                             <b>Phòng:</b> {{ $room->ma_phong }}<br>
                             <b>Loại:</b> {{ $room->loaiPhong->ten ?? '???' }}<br>
                             <b>Khách:</b> {{ $guest }}<br>
                             <b>Check-in:</b> {{ $checkin }}<br>
                             <b>Check-out:</b> {{ $checkout }}<br>
                             <b>Sức chứa:</b> {{ $room->suc_chua }} - Giường: {{ $room->so_giuong }}<br>
                             <b>Giá:</b> {{ number_format($room->gia_mac_dinh, 0) }} VND
                             "
                             onclick="openRoomModal({{ $room->id }})">
                         
                            <div class="quick-actions position-absolute top-0 end-0 p-1 d-none">
                                @if(in_array($room->trang_thai, ['da_dat', 'dang_o']))
                                    <button class="btn btn-sm btn-success me-1" onclick="event.stopPropagation(); quickAction('checkin', {{ $room->id }})">Check-in</button>
                                    <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); quickAction('checkout', {{ $room->id }})">Check-out</button>
                                @endif
                            </div>
                            <img src="{{ $img }}" class="card-img-top" alt="Hình ảnh phòng {{ $room->ma_phong }}" loading="lazy" style="height:100px; object-fit:cover;">
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
                @if($rooms->count() > 20)
                    <div class="col-12 text-center">
                        <button class="btn btn-outline-secondary" onclick="loadMore('{{ $floor }}')">Tải Thêm Phòng</button>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
    <div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalLabel">Chi Tiết Phòng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="modalContent">Nội dung sẽ được tải...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <a id="modalActionLink" class="btn btn-primary" href="#">Hành Động</a>
                </div>
            </div>
        </div>
    </div>
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
.room-tile:hover .quick-actions {
    display: flex !important;  
}
.card-body small {
    font-size: 0.85rem;
}
.card-footer .badge {
    font-size: 0.75rem;
    padding: 0.3em 0.5em;
}
.quick-actions .btn {
    font-size: 0.7rem;
    padding: 0.2em 0.4em;
}
#roomSearch, #statusFilter {
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
#roomSearch:focus, #statusFilter:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el, { sanitize: false }); 
    });

  
    document.querySelectorAll('.room-tile').forEach(tile => {
        tile.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openRoomModal(this.dataset.roomId || '');  
            }
        });
    });
});


function filterRooms() {
    const search = document.getElementById('roomSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    document.querySelectorAll('[data-room-code]').forEach(container => {
        const roomCode = container.dataset.roomCode;
        const roomStatus = container.dataset.status;
        const show = (roomCode.includes(search) || search === '') && (status === '' || roomStatus === status);
        container.style.display = show ? 'block' : 'none';
    });
}


function clearFilters() {
    document.getElementById('roomSearch').value = '';
    document.getElementById('statusFilter').value = '';
    filterRooms();
}


document.getElementById('roomSearch').addEventListener('keyup', filterRooms);
document.getElementById('statusFilter').addEventListener('change', filterRooms);

function loadMore(floor) {
    const hiddenRooms = document.querySelectorAll(`#floor-${floor}-rooms [class*="d-none"]`);
    hiddenRooms.forEach(room => room.classList.remove('d-none'));

    const btn = event.target;
    if (hiddenRooms.length === 0) btn.style.display = 'none';
}

function openRoomModal(roomId) {
  
    fetch(`/staff/rooms/${roomId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalContent').innerHTML = html;
            document.getElementById('modalActionLink').href = `/staff/rooms/${roomId}/edit`;
            new bootstrap.Modal(document.getElementById('roomModal')).show();
        })
        .catch(error => console.error('Lỗi load modal:', error));
}


function quickAction(action, roomId) {
    if (action === 'checkin') {
        window.location.href = `/staff/checkin?room_id=${roomId}`;
    } else if (action === 'checkout') {
        window.location.href = `/staff/checkout?room_id=${roomId}`;
    }
}
</script>
@endsection