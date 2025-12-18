@extends('layouts.admin')

@section('title', 'Đổi phòng lỗi')

@section('content')

<style>
    /* Copy style từ change-room.blade.php */
    .room-type-section { margin-bottom: 32px; }
    .room-type-title { font-size: 20px; font-weight: bold; margin-bottom: 10px; color: #dc3545; }
    .rooms-slider { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 6px; }
    .room-card { min-width: 260px; max-width: 260px; border: 1px solid #e6e6e6; border-radius: 12px; padding: 12px; background: #ffffff; transition: .15s ease; cursor: pointer; flex-shrink: 0; }
    .room-card:hover { transform: translateY(-4px); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
    .room-card.selected { border-color: #dc3545; box-shadow: 0 10px 30px rgba(220,53,69,.25); }
    .room-card.downgrade { opacity: 0.6; border-color: #ffc107; }
    .summary-box { background: #fff; border-radius: 12px; padding: 16px; border: 1px solid #e9ecef; position: sticky; top: 20px; }
    .loading-spinner { display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 300px; gap: 15px; }
    .alert-no-charge { background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 12px; margin-bottom: 20px; }
</style>

<div class="container-fluid mt-4">
    
    {{-- Cảnh báo --}}
    <div class="alert-no-charge">
        <i class="fas fa-info-circle"></i>
        <strong>Đổi phòng lỗi:</strong> Khách hàng sẽ KHÔNG bị tính thêm tiền. 
        Chỉ cập nhật phòng mới trong hệ thống.
    </div>

    <div class="mb-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="row">

        {{-- LEFT: DANH SÁCH PHÒNG --}}
        <div class="col-md-8">

            <h4 class="mb-3 text-danger">
                <i class="fas fa-exclamation-triangle"></i>
                Đổi phòng lỗi cho 
                <strong>#{{ $item->phong->ma_phong }}</strong> — {{ $item->phong->name }}
            </h4>

            <div class="mb-3">
                <button type="button" class="btn btn-outline-warning" id="toggle-lower-price">
                    <i class="fas fa-eye"></i> Xem thêm phòng giá thấp hơn
                </button>
            </div>

            <div id="rooms-container">
                <div class="loading-spinner">
                    <div class="spinner-border text-danger" role="status" style="width: 3rem; height: 3rem;"></div>
                    <span class="text-muted">Đang tải phòng trống...</span>
                </div>
            </div>

        </div>

        {{-- RIGHT: SUMMARY --}}
        <div class="col-md-4">

            <div class="summary-box">

                <h5 class="mb-3 text-danger">
                    <i class="fas fa-exclamation-triangle"></i> Đổi phòng lỗi
                </h5>

                <div class="alert alert-warning small">
                    <i class="fas fa-info-circle"></i>
                    Không tính thêm tiền
                </div>

                {{-- PHÒNG HIỆN TẠI --}}
                <div class="small text-muted">Phòng hiện tại</div>
                <div class="fw-bold" id="current-room-name">{{ $item->phong->name }}</div>
                <div id="current-room-price" class="text-muted">
                    Giá: {{ number_format($item->phong->tong_gia) }}đ/đêm
                </div>

                <hr>

                {{-- PHÒNG MỚI --}}
                <div id="new-room-summary" style="display:none;">
                    <div class="small text-muted">Phòng mới</div>
                    <div id="new-room-name" class="fw-bold"></div>
                    <div id="new-room-price" class="text-success"></div>
                    <div id="new-room-comparison" class="small mt-2"></div>
                    <hr>
                </div>

             {{-- TỔNG BOOKING --}}
<div class="d-flex justify-content-between">
    <span class="fw-bold">Tổng booking hiện tại</span>
    <span class="fw-bold" id="booking-current">{{ number_format($booking->tong_tien) }}đ</span>
</div>

<div id="booking-change-info" class="mt-2" style="display:none;">
    <div class="d-flex justify-content-between">
        <span class="text-primary fw-bold">Tổng booking sau đổi</span>
        <span class="text-primary fw-bold" id="booking-after">-</span>
    </div>
    
    <div class="alert mt-2 small text-center" id="change-alert">
        <!-- Sẽ được update bằng JS -->
    </div>
</div>

                <hr>

                {{-- FORM SUBMIT --}}
                <form id="submit-change" method="POST"
                      action="{{ route('admin.change-room-error.apply', $item->id) }}">
                    @csrf
                    <input type="hidden" name="new_room_id" id="new_room_id">

                    <button type="submit" class="btn btn-danger w-100" disabled id="confirm-btn">
                        <i class="fas fa-exchange-alt"></i> Xác nhận đổi phòng lỗi
                    </button>
                </form>

            </div>

        </div>

    </div>
</div>


{{-- JAVASCRIPT --}}
<script>
let showingLowerPrice = false;

// Load phòng trống
async function loadAvailableRooms() {
    try {
        const url = "{{ route('admin.change-room-error.available-rooms', $item->id) }}" + 
                    "?show_lower_price=" + (showingLowerPrice ? '1' : '0');
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            renderRooms(data.available_rooms);
            updateToggleButton(data.showing_lower_price);
        } else {
            showError(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Lỗi kết nối: ' + error.message);
    }
}

// Render phòng
function renderRooms(roomsByType) {
    const container = document.getElementById('rooms-container');
    
    if (!roomsByType || !Array.isArray(roomsByType)) {
        roomsByType = Object.values(roomsByType || {});
    }
    
    if (roomsByType.length === 0) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i>
                Không có phòng trống.
            </div>
        `;
        return;
    }

    // Group phòng
    const groupedRooms = {};
    roomsByType.forEach(typeGroup => {
        if (typeGroup.rooms) {
            typeGroup.rooms.forEach(room => {
                const typeId = room.type_id;
                if (!groupedRooms[typeId]) {
                    groupedRooms[typeId] = {
                        type_name: room.type_name,
                        rooms: []
                    };
                }
                groupedRooms[typeId].rooms.push(room);
            });
        }
    });

    let html = '';
    
    Object.values(groupedRooms).forEach((typeGroup) => {
        html += `
            <div class="room-type-section">
                <div class="room-type-title">
                    <i class="fas fa-door-open"></i> ${typeGroup.type_name}
                </div>
                <div class="rooms-slider">
        `;

        typeGroup.rooms.forEach((room) => {
            const isDowngrade = room.is_downgrade;
            const downgradeClass = isDowngrade ? 'downgrade' : '';
            
            const badge = room.is_upgrade 
                ? `<span class="badge bg-success">Nâng cấp</span>`
                : `<span class="badge bg-warning text-dark">Hạ cấp</span>`;

            const roomJsonEscaped = JSON.stringify(room)
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            html += `
                <div class="room-card ${downgradeClass}" id="room-${room.id}" 
                     onclick='selectRoom(${room.id}, \`${roomJsonEscaped}\`)'>
                    <img src="${room.image}" 
                         style="height:150px;width:100%;object-fit:cover;" 
                         class="rounded mb-2"
                         onerror="this.src='/images/room-placeholder.jpg'">

                    <strong>#${room.code} - ${room.name}</strong>
                    <div class="text-muted small">
                        <i class="fas fa-users"></i> Sức chứa: ${room.capacity}
                    </div>

                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Giá/đêm</span>
                            <span class="fw-bold">${formatNumber(room.price_per_night)}đ</span>
                        </div>

                        ${room.extra_charge > 0 ? `
                        <div class="d-flex justify-content-between mt-1">
                            <span class="small text-muted">Phụ thu/đêm</span>
                            <span class="text-warning">${formatNumber(room.extra_charge)}đ</span>
                        </div>
                        ` : ''}

                        <div class="mt-2">
                            ${badge}
                        </div>
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Chọn phòng
function selectRoom(roomId, roomDataStr) {
    try {
        const roomData = typeof roomDataStr === 'string' ? JSON.parse(roomDataStr) : roomDataStr;
        
        document.querySelectorAll('.room-card').forEach(e => e.classList.remove('selected'));
        document.getElementById('room-' + roomId)?.classList.add('selected');

        document.getElementById('new_room_id').value = roomId;
        document.getElementById('confirm-btn').disabled = false;

        document.getElementById('new-room-summary').style.display = 'block';
        document.getElementById('new-room-name').textContent = `#${roomData.code} - ${roomData.name}`;
        document.getElementById('new-room-price').textContent = `Giá: ${formatNumber(roomData.price_per_night)}đ/đêm`;
        
        // ✅ TÍNH TOÁN CHÊNH LỆCH
        const bookingCurrent = {{ $booking->tong_tien }};
        const oldRoomPrice = {{ $item->phong->tong_gia ?? 0 }};
        const oldExtraFee = {{ $extraFee ?? 0 }};
        const nights = 2; // bookingData.nights
        
        const priceDiff = (roomData.price_per_night - oldRoomPrice) * nights;
        const extraFeeDiff = (roomData.extra_charge - oldExtraFee) * nights;
        const totalDiff = priceDiff + extraFeeDiff;
        
        const bookingAfter = bookingCurrent + totalDiff;
        
        document.getElementById('booking-change-info').style.display = 'block';
        document.getElementById('booking-after').textContent = formatNumber(Math.max(0, bookingAfter)) + 'đ';
        
        const alertBox = document.getElementById('change-alert');
        
        if (totalDiff > 0) {
            // Nâng cấp - miễn phí
            alertBox.className = 'alert alert-success mt-2 small text-center';
            alertBox.innerHTML = '<strong>NÂNG CẤP MIỄN PHÍ</strong><br>Không tính thêm tiền';
            document.getElementById('booking-after').textContent = formatNumber(bookingCurrent) + 'đ';
            
        } else if (totalDiff < 0) {
            // Hạ cấp - hoàn tiền
            const refund = Math.abs(totalDiff);
            alertBox.className = 'alert alert-warning mt-2 small text-center';
            alertBox.innerHTML = `<strong>HẠ CẤP</strong><br>Hoàn lại: ${formatNumber(refund)}đ`;
            document.getElementById('booking-after').textContent = formatNumber(bookingAfter) + 'đ';
            
        } else {
            // Ngang bằng
            alertBox.className = 'alert alert-info mt-2 small text-center';
            alertBox.innerHTML = '<strong>PHÒNG NGANG BẰNG</strong><br>Không đổi giá';
            document.getElementById('booking-after').textContent = formatNumber(bookingCurrent) + 'đ';
        }
        
        const comparison = roomData.is_upgrade 
            ? `<span class="text-success"><i class="fas fa-arrow-up"></i> Nâng cấp (miễn phí)</span>`
            : `<span class="text-warning"><i class="fas fa-arrow-down"></i> Hạ cấp (hoàn ${formatNumber(Math.abs(totalDiff))}đ)</span>`;
        
        document.getElementById('new-room-comparison').innerHTML = comparison;

    } catch (error) {
        console.error('Error:', error);
        alert('Lỗi khi chọn phòng!');
    }
}


// Toggle nút xem phòng giá thấp
document.getElementById('toggle-lower-price').addEventListener('click', function() {
    showingLowerPrice = !showingLowerPrice;
    loadAvailableRooms();
});

function updateToggleButton(showing) {
    const btn = document.getElementById('toggle-lower-price');
    if (showing) {
        btn.innerHTML = '<i class="fas fa-eye-slash"></i> Ẩn phòng giá thấp hơn';
        btn.classList.remove('btn-outline-warning');
        btn.classList.add('btn-warning');
    } else {
        btn.innerHTML = '<i class="fas fa-eye"></i> Xem thêm phòng giá thấp hơn';
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-outline-warning');
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(num));
}

function showError(message) {
    document.getElementById('rooms-container').innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Lỗi:</strong> ${message}
        </div>
    `;
}

// Confirm trước khi submit
document.getElementById('submit-change').addEventListener('submit', function(e) {
    const roomData = document.getElementById('new_room_id').value;
    if (!roomData) {
        e.preventDefault();
        alert('Vui lòng chọn phòng!');
        return false;
    }
    
    if (!confirm('Xác nhận đổi phòng lỗi? (KHÔNG tính thêm tiền)')) {
        e.preventDefault();
        return false;
    }
});

// Load on ready
document.addEventListener('DOMContentLoaded', loadAvailableRooms);
</script>

@endsection