@extends('layouts.admin')

@section('title', 'Đổi phòng')

@section('content')

<style>
    .room-type-section {
        margin-bottom: 32px;
    }

    .room-type-title {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #2c3e50;
    }

    .rooms-slider {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 6px;
        scroll-behavior: smooth;
    }

    .rooms-slider:hover {
        overflow-x: scroll;
    }

    .rooms-slider::-webkit-scrollbar {
        height: 8px;
    }
    .rooms-slider::-webkit-scrollbar-thumb {
        background: #cfcfcf;
        border-radius: 4px;
    }

    .room-card {
        min-width: 260px;
        max-width: 260px;
        border: 1px solid #e6e6e6;
        border-radius: 12px;
        padding: 12px;
        background: #ffffff;
        transition: .15s ease;
        cursor: pointer;
        flex-shrink: 0;
    }

    .room-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }

    .room-card.selected {
        border-color: #0d6efd;
        box-shadow: 0 10px 30px rgba(13,110,253,.25);
    }

    .summary-box {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        border: 1px solid #e9ecef;
        position: sticky;
        top: 20px;
    }

    .loading-spinner {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 300px;
        gap: 15px;
    }
</style>

@php
    $booking = $item->datPhong;
    $soDem   = (int) $item->so_dem;

    // GIÁ PHÒNG CŨ
    $oldRoomPrice = (float)$item->gia_tren_dem * $soDem;

    // PHỤ THU CŨ
    $adultExtra = (int)$item->number_adult;
    $childExtra = (int)$item->number_child;
    $extraFee = ($adultExtra * 150000) + ($childExtra * 60000);
    if ($extraFee < 0) $extraFee = 0;

    // CHIA VOUCHER THEO SỐ PHÒNG
    $roomCount = $booking->items->count() ?: 1;

    $voucherItem = 0;
    if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
        $voucherItem = (float)$booking->discount_amount / $roomCount;
    } elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
        $voucherItem = (float)$booking->voucher_discount / $roomCount;
    }

    // TÍNH NGƯỢC GIÁ PHÒNG GỐC
    $currentRoomOriginalPrice = $oldRoomPrice - $extraFee + $voucherItem;
@endphp


<div class="container-fluid mt-4">
    
    {{-- BACK BUTTON --}}
    <div class="mb-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="row">

        {{-- ===================================================== --}}
        {{-- LEFT: DANH SÁCH PHÒNG --}}
        {{-- ===================================================== --}}
        <div class="col-md-8">

            <h4 class="mb-3">
                Chọn phòng mới cho 
                <strong>#{{ $item->phong->ma_phong }}</strong> — {{ $item->phong->name }}
            </h4>

            <div id="rooms-container">
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-muted">Đang tải phòng trống...</span>
                </div>
            </div>

        </div>



        {{-- ===================================================== --}}
        {{-- RIGHT: SUMMARY --}}
        {{-- ===================================================== --}}
        <div class="col-md-4">

            <div class="summary-box">

                <h5 class="mb-3">Tóm tắt giá</h5>

                {{-- PHÒNG HIỆN TẠI --}}
                <div class="small text-muted">Phòng hiện tại</div>
                <div class="fw-bold" id="current-room-name">{{ $item->phong->name }} — {{ $soDem }} đêm</div>

                <div id="current-room-price">
                    {{ number_format($currentRoomOriginalPrice) }}đ
                    <small class="text-muted">({{ number_format($currentRoomOriginalPrice / $soDem) }}/đêm)</small>
                </div>

                <div class="mt-1 text-muted small">+ Phụ thu: {{ number_format($extraFee) }}đ</div>

                <hr>

                {{-- PHÒNG MỚI --}}
                <div id="new-room-summary" style="display:none;">
                    <div class="small text-muted">Phòng mới</div>
                    <div id="new-room-name" class="fw-bold"></div>
                    <div id="new-room-total" class="fw-semibold mb-1"></div>
                    <div id="new-room-extra" class="small text-muted mt-1"></div>
                    <hr>
                </div>

                {{-- VOUCHER --}}
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Voucher chia cho phòng:</span>
                    <span id="voucher-applied" class="fw-bold text-danger">
                        {{ $voucherItem > 0 ? '-' . number_format($voucherItem) . 'đ' : 'Không có' }}
                    </span>
                </div>

                <hr>

                {{-- TỔNG BOOKING --}}
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Tổng booking hiện tại</span>
                    <span id="booking-current-total">{{ number_format($booking->tong_tien) }}đ</span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <span class="fw-bold text-primary">Tổng booking nếu đổi</span>
                    <span id="booking-new-total-txt" class="fw-bold text-primary">-</span>
                </div>

                <hr>

                {{-- FORM SUBMIT --}}
                <form id="submit-change" method="POST"
                      action="{{ route('admin.change-room.apply', $item->id) }}">
                    @csrf
                    <input type="hidden" name="new_room_id" id="new_room_id">

                    <button type="submit" class="btn btn-primary w-100" disabled id="confirm-btn">
                        <i class="fas fa-exchange-alt"></i> Xác nhận đổi phòng
                    </button>
                </form>

            </div>

        </div>

    </div>
</div>


{{-- ========================================= --}}
{{-- JAVASCRIPT --}}
{{-- ========================================= --}}
<script>
let bookingData = null;
let selectedRoomData = null;
const OLD_ROOM_PRICE_PER_NIGHT = {{ $item->gia_tren_dem }};
const OLD_EXTRA_PER_NIGHT = {{ $extraFee / $soDem }};

// ✅ HÀM TÍNH GIÁ VỚI WEEKEND +10%
function calculatePriceWithWeekend(pricePerNight, checkInDate, checkOutDate) {
    const start = new Date(checkInDate);
    const end = new Date(checkOutDate);
    
    let total = 0;
    let details = [];
    
    let current = new Date(start);
    while (current < end) {
        const dayOfWeek = current.getDay(); // 0=CN, 5=T6, 6=T7
        
        let nightPrice;
        let isWeekend = false;
        
        // Kiểm tra weekend
        if ([0, 5, 6].includes(dayOfWeek)) {
            nightPrice = pricePerNight * 1.10; // +10%
            isWeekend = true;
        } else {
            nightPrice = pricePerNight;
        }
        
        total += nightPrice;
        
        details.push({
            date: current.toISOString().split('T')[0],
            dayOfWeek: dayOfWeek,
            price: nightPrice,
            isWeekend: isWeekend
        });
        
        current.setDate(current.getDate() + 1);
    }
    
    return {
        total: total,
        details: details
    };
}

async function loadAvailableRooms() {
    try {
        const response = await fetch("/admin/change-room/{{ $item->id }}/available-rooms?old_room_id={{ $item->phong_id }}", {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        console.log('📦 Full API Response:', data);

        if (data.success) {
            if (!data.booking_info) {
                throw new Error('Missing booking_info in response');
            }
            
            bookingData = data.booking_info;
            console.log('✅ bookingData set:', bookingData);
            
            if (!data.available_rooms) {
                throw new Error('Missing available_rooms in response');
            }
            
            renderAvailableRooms(data.available_rooms);
        } else {
            showError(data.message || 'Không thể tải phòng trống');
        }
    } catch (error) {
        console.error('❌ Error loading rooms:', error);
        showError('Lỗi kết nối API: ' + error.message);
    }
}

// ===== HIỂN THỊ DANH SÁCH PHÒNG =====
function renderAvailableRooms(roomsData) {
    const container = document.getElementById('rooms-container');
    
    console.log('🏨 Rendering rooms:', roomsData);
    
    if (!roomsData) {
        showError('Dữ liệu phòng không hợp lệ');
        return;
    }
    
    if (!Array.isArray(roomsData)) {
        roomsData = Object.values(roomsData);
    }
    
    if (roomsData.length === 0) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i>
                Không có phòng trống trong khoảng thời gian này.
            </div>
        `;
        return;
    }

    const groupedRooms = {};
    roomsData.forEach(room => {
        const typeId = room.type_id || 'unknown';
        if (!groupedRooms[typeId]) {
            groupedRooms[typeId] = {
                type_id: typeId,
                type_name: room.type_name || room.name || 'Không xác định',
                rooms: []
            };
        }
        groupedRooms[typeId].rooms.push(room);
    });

    console.log('🏨 Grouped rooms:', groupedRooms);

    let html = '';
    
    Object.values(groupedRooms).forEach((typeGroup) => {
        console.log(`🏨 Processing type: ${typeGroup.type_name}, rooms: ${typeGroup.rooms.length}`);
        
        html += `
            <div class="room-type-section">
                <div class="room-type-title">
                    <i class="fas fa-door-open"></i> ${typeGroup.type_name}
                </div>
                <div class="rooms-slider">
        `;

        typeGroup.rooms.forEach((room) => {
            if (!room || !room.id) {
                console.warn('⚠️ Invalid room:', room);
                return;
            }
            
            if (!bookingData || !bookingData.nights) {
                console.error('❌ bookingData not available!');
                return;
            }
            
            // ✅ SỬ DỤNG PRICE_TOTAL TỪ API (đã tính weekend)
            const totalDiff = room.price_difference || 0;
            const diffBadge = totalDiff < 0 
                ? `<span class="badge bg-success"><i class="fas fa-arrow-down"></i> Tiết kiệm ${formatNumber(Math.abs(totalDiff))}đ</span>`
                : totalDiff > 0
                ? `<span class="badge bg-danger"><i class="fas fa-arrow-up"></i> Tăng ${formatNumber(totalDiff)}đ</span>`
                : `<span class="badge bg-secondary">Không đổi</span>`;

            const roomJsonEscaped = JSON.stringify(room)
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            html += `
                <div class="room-card" id="room-${room.id}" onclick='selectRoom(${room.id}, \`${roomJsonEscaped}\`)'>
                    <img src="${room.image || '/images/room-placeholder.jpg'}" 
                         style="height:150px;width:100%;object-fit:cover;" 
                         class="rounded mb-2"
                         onerror="this.src='/images/room-placeholder.jpg'">

                    <strong>#${room.code || ''} - ${room.name || 'Không tên'}</strong>
                    <div class="text-muted small">
                        <i class="fas fa-users"></i> Sức chứa: ${room.capacity || 0} người
                    </div>

                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Giá/đêm</span>
                            <span class="fw-bold">${formatNumber(room.price_per_night || 0)}đ</span>
                        </div>

                        <div class="d-flex justify-content-between mt-1">
                            <span class="small text-muted">Tổng (có weekend)</span>
                            <span class="fw-bold text-primary">${formatNumber(room.price_total || 0)}đ</span>
                        </div>

                        ${(room.extra_charge || 0) > 0 ? `
                        <div class="d-flex justify-content-between mt-1">
                            <span class="small text-muted">+ Phụ thu</span>
                            <span class="text-warning">${formatNumber(room.extra_charge * bookingData.nights)}đ</span>
                        </div>
                        ` : ''}

                        <div class="mt-2">
                            ${diffBadge}
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

    if (html === '') {
        showError('Không có phòng nào hợp lệ');
        return;
    }

    container.innerHTML = html;
    console.log('✅ Rooms rendered successfully');
}

// ===== CHỌN PHÒNG =====
function selectRoom(roomId, roomDataStr) {
    try {
        const roomData = typeof roomDataStr === 'string'
            ? JSON.parse(roomDataStr)
            : roomDataStr;

        document.querySelectorAll('.room-card')
            .forEach(e => e.classList.remove('selected'));
        document.getElementById('room-' + roomId)?.classList.add('selected');

        selectedRoomData = roomData;
        document.getElementById('new_room_id').value = roomId;
        document.getElementById('confirm-btn').disabled = false;

        // ✅ SỬ DỤNG price_total TỪ API (đã tính weekend ở backend)
        const newTotal = roomData.price_total || 0;
        const totalDiff = roomData.price_difference || 0;

        // ===== UPDATE UI =====
        document.getElementById('new-room-summary').style.display = 'block';
        document.getElementById('new-room-name').textContent =
            `#${roomData.code} - ${roomData.name}`;

        document.getElementById('new-room-total').textContent =
            formatNumber(newTotal) + 'đ (đã tính weekend)';

        if ((roomData.extra_charge || 0) > 0) {
            document.getElementById('new-room-extra').textContent =
                `Phụ thu: ${formatNumber(roomData.extra_charge)}đ / đêm × ${bookingData.nights} đêm`;
        } else {
            document.getElementById('new-room-extra').textContent =
                'Không có phụ thu';
        }

        // ===== TỔNG BOOKING =====
        const bookingCurrent = {{ $booking->tong_tien }};
        const bookingNew = bookingCurrent + totalDiff;

        const txt = document.getElementById('booking-new-total-txt');
        txt.textContent = formatNumber(bookingNew) + 'đ';

        txt.classList.remove('text-success', 'text-danger', 'text-primary');
        if (totalDiff > 0) {
            txt.classList.add('text-danger');
        } else if (totalDiff < 0) {
            txt.classList.add('text-success');
        } else {
            txt.classList.add('text-primary');
        }

        console.log('💰 CALC OK', {
            newTotal,
            totalDiff,
            bookingNew
        });

    } catch (err) {
        console.error('❌ selectRoom error', err);
        alert('Lỗi khi chọn phòng');
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

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Page loaded, loading rooms...');
    loadAvailableRooms();
    
    document.getElementById('submit-change').addEventListener('submit', function(e) {
        if (!selectedRoomData) {
            e.preventDefault();
            alert('Vui lòng chọn phòng muốn đổi!');
            return false;
        }
        
        if (!confirm(`Bạn có chắc muốn đổi sang phòng #${selectedRoomData.code} - ${selectedRoomData.name}?`)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

@endsection