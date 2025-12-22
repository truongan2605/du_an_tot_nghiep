@extends('layouts.admin')

@section('title', 'Đổi phòng lỗi')

@section('content')

<style>
    .room-type-section { margin-bottom: 32px; }
    .room-type-title { font-size: 20px; font-weight: bold; margin-bottom: 10px; color: #dc3545; }
    .rooms-slider { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 6px; scroll-behavior: smooth; }
    .rooms-slider:hover { overflow-x: scroll; }
    .rooms-slider::-webkit-scrollbar { height: 8px; }
    .rooms-slider::-webkit-scrollbar-thumb { background: #cfcfcf; border-radius: 4px; }
    .room-card { min-width: 260px; max-width: 260px; border: 1px solid #e6e6e6; border-radius: 12px; padding: 12px; background: #ffffff; transition: .15s ease; cursor: pointer; flex-shrink: 0; }
    .room-card:hover { transform: translateY(-4px); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
    .room-card.selected { border-color: #dc3545; box-shadow: 0 10px 30px rgba(220,53,69,.25); }
    .room-card.downgrade { opacity: 0.75; border-color: #ffc107; }
    .summary-box { background: #fff; border-radius: 12px; padding: 16px; border: 1px solid #e9ecef; position: sticky; top: 20px; }
    .loading-spinner { display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 300px; gap: 15px; }
    .alert-no-charge { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 12px; margin-bottom: 20px; }
</style>

@php
    $booking = $item->datPhong;
    $checkIn = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
    $checkOut = \Carbon\Carbon::parse($booking->ngay_tra_phong);
    $soDem = $checkIn->diffInDays($checkOut);

    // TÍNH SỐ ĐÊM CUỐI TUẦN
    function calculateWeekendNightsInBlade($checkIn, $checkOut) {
        $start = \Carbon\Carbon::parse($checkIn);
        $end = \Carbon\Carbon::parse($checkOut);
        $weekendNights = 0;
        $current = $start->copy();
        
        while ($current->lt($end)) {
            $dayOfWeek = $current->dayOfWeek;
            if ($dayOfWeek == \Carbon\Carbon::FRIDAY || 
                $dayOfWeek == \Carbon\Carbon::SATURDAY || 
                $dayOfWeek == \Carbon\Carbon::SUNDAY) {
                $weekendNights++;
            }
            $current->addDay();
        }
        return $weekendNights;
    }
    
    $weekendNights = calculateWeekendNightsInBlade($checkIn, $checkOut);
    $weekdayNights = $soDem - $weekendNights;

    // GIÁ PHÒNG CŨ
    $currentRoomBasePrice = $item->phong->tong_gia ?? 0;
    $adultExtra = (int)$item->number_adult;
    $childExtra = (int)$item->number_child;
    $extraFee = ($adultExtra * 150000) + ($childExtra * 60000);

    // TÍNH GIÁ CUỐI TUẦN
    $basePrice = $currentRoomBasePrice;
    $weekdayTotal = ($basePrice + $extraFee) * $weekdayNights;
    $weekendBaseTotal = $basePrice * $weekendNights;
    $weekendSurcharge = $basePrice * 0.1 * $weekendNights;
    $weekendExtraTotal = $extraFee * $weekendNights;
    $weekendTotal = $weekendBaseTotal + $weekendSurcharge + $weekendExtraTotal;
    $currentRoomTotal = $weekdayTotal + $weekendTotal;

    // VOUCHER
    $roomCount = $booking->datPhongItems->count() ?: 1;
    $voucherItem = 0;
    if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
        $voucherItem = (float)$booking->discount_amount / $roomCount;
    } elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
        $voucherItem = (float)$booking->voucher_discount / $roomCount;
    }

    $currentRoomTotalAfterVoucher = $currentRoomTotal - $voucherItem;
@endphp

<div class="container-fluid mt-4">
    
    {{-- Cảnh báo --}}
    <div class="alert-no-charge">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Đổi phòng lỗi:</strong> Nâng cấp = MIỄN PHÍ | Hạ cấp = HOÀN TIỀN cho khách
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
                <button type="button" class="btn btn-outline-warning btn-sm" id="toggle-lower-price">
                    <i class="fas fa-eye"></i> Xem thêm phòng giá thấp hơn
                </button>
            </div>

            <div id="rooms-container">
                <div class="loading-spinner">
                    <div class="spinner-border text-danger" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
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

                <div class="alert alert-warning small mb-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Chính sách:</strong><br>
                    • Nâng cấp: Miễn phí<br>
                    • Hạ cấp: Hoàn tiền
                </div>

                {{-- PHÒNG HIỆN TẠI --}}
                <div class="small text-muted">Phòng hiện tại</div>
                <div class="fw-bold mb-2">{{ $item->phong->name }} (#{{ $item->phong->ma_phong }}) — {{ $soDem }} đêm</div>

                <div class="card bg-light p-2 mb-2">
                    <div class="d-flex justify-content-between small">
                        <span>Giá gốc:</span>
                        <span>{{ number_format($currentRoomBasePrice) }}đ/đêm</span>
                    </div>
                    
                    @if($extraFee > 0)
                    <div class="d-flex justify-content-between small text-warning">
                        <span>+ Phụ thu:</span>
                        <span>{{ number_format($extraFee) }}đ/đêm</span>
                    </div>
                    <div class="small text-muted" style="font-size: 0.7rem;">
                        @if($adultExtra > 0) {{ $adultExtra }} người lớn @endif
                        @if($childExtra > 0)@if($adultExtra > 0), @endif{{ $childExtra }} trẻ em @endif
                    </div>
                    @endif
                    
                    @if($weekendNights > 0)
                    <div class="d-flex justify-content-between small text-danger">
                        <span>+ Cuối tuần:</span>
                        <span>{{ number_format($weekendSurcharge) }}đ</span>
                    </div>
                    <div class="small text-muted" style="font-size: 0.7rem;">
                        +10% × {{ $weekendNights }} đêm (T6,T7,CN)
                    </div>
                    @endif
                    
                    <div class="d-flex justify-content-between small border-top pt-1 mt-1">
                        <span class="fw-bold">Tổng trước voucher:</span>
                        <span class="fw-bold">{{ number_format($currentRoomTotal) }}đ</span>
                    </div>
                    
                    @if($voucherItem > 0)
                    <div class="d-flex justify-content-between small text-success">
                        <span>- Voucher:</span>
                        <span>{{ number_format($voucherItem) }}đ</span>
                    </div>
                    @endif
                    
                    <div class="d-flex justify-content-between border-top pt-1 mt-1">
                        <span class="fw-bold text-primary">Tổng thực tế:</span>
                        <span class="fw-bold text-primary">{{ number_format($currentRoomTotalAfterVoucher) }}đ</span>
                    </div>
                </div>

                <hr>

                {{-- PHÒNG MỚI --}}
                <div id="new-room-summary" style="display:none;">
                    <div class="small text-muted">Phòng mới</div>
                    <div id="new-room-name" class="fw-bold"></div>
                    <div id="new-room-total" class="fw-semibold mb-1"></div>
                    <div id="new-room-extra" class="small text-muted mt-1"></div>
                    <hr>
                </div>

                {{-- TỔNG BOOKING --}}
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Tổng booking hiện tại</span>
                    <span id="booking-current-total">{{ number_format($booking->tong_tien) }}đ</span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <span class="fw-bold text-primary">Tổng booking nếu đổi</span>
                    <span id="booking-new-total-txt" class="fw-bold text-primary">-</span>
                </div>

                <div id="change-note" class="alert mt-2 small" style="display:none;"></div>

                <hr>

                {{-- FORM SUBMIT --}}
                <form id="submit-change" method="POST" action="{{ route('admin.change-room-error.apply', $item->id) }}">
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
let bookingData = null;

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
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('📦 API Response:', data);

        if (data.success) {
            bookingData = data.booking_info;
            renderRooms(data.available_rooms);
            updateToggleButton(data.showing_lower_price);
        } else {
            showError(data.message || 'Không thể tải phòng trống');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        showError('Lỗi kết nối: ' + error.message);
    }
}

// Render phòng
function renderRooms(roomsByType) {
    const container = document.getElementById('rooms-container');
    
    if (!roomsByType) {
        showError('Dữ liệu phòng không hợp lệ');
        return;
    }
    
    if (!Array.isArray(roomsByType)) {
        roomsByType = Object.values(roomsByType);
    }
    
    if (roomsByType.length === 0) {
        container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Không có phòng trống.</div>';
        return;
    }

    // ✅ LƯU VÀO GLOBAL
    window.availableRoomsData = {};
    roomsByType.forEach(typeGroup => {
        if (typeGroup.rooms) {
            typeGroup.rooms.forEach(room => {
                window.availableRoomsData[room.id] = room;
            });
        }
    });

    let html = '';
    
    roomsByType.forEach((typeGroup) => {
        if (!typeGroup.rooms || typeGroup.rooms.length === 0) return;
        
        html += '<div class="room-type-section">';
        html += '<div class="room-type-title"><i class="fas fa-door-open"></i> ' + (typeGroup.type_name || 'Không xác định') + '</div>';
        html += '<div class="rooms-slider">';

        typeGroup.rooms.forEach((room) => {
            if (!room || !room.id || !bookingData) return;
            
            const voucherPerRoom = room.voucher_per_room || 0;
            const newTotalAfterVoucher = room.price_total_after_voucher || 0;
            const currentTotalAfterVoucher = {{ $currentRoomTotalAfterVoucher }};
            const realDiff = newTotalAfterVoucher - currentTotalAfterVoucher;
            
            const diffBadge = realDiff < 0 
                ? '<span class="badge bg-success"><i class="fas fa-arrow-down"></i> Hoàn ' + formatNumber(Math.abs(realDiff)) + 'đ</span>'
                : realDiff > 0
                ? '<span class="badge bg-info"><i class="fas fa-arrow-up"></i> Nâng cấp miễn phí</span>'
                : '<span class="badge bg-secondary">Không đổi</span>';

            const downgradeClass = room.is_downgrade ? 'downgrade' : '';

            html += '<div class="room-card ' + downgradeClass + '" id="room-' + room.id + '" onclick="selectRoom(' + room.id + ')">';
            html += '<img src="' + (room.image || '/images/room-placeholder.jpg') + '" style="height:150px;width:100%;object-fit:cover;" class="rounded mb-2" onerror="this.src=\'/images/room-placeholder.jpg\'">';
            html += '<strong>#' + room.code + ' - ' + room.name + '</strong>';
            html += '<div class="text-muted small"><i class="fas fa-users"></i> Sức chứa: ' + room.capacity + ' người</div>';
            html += '<div class="mt-3">';
            html += '<div class="d-flex justify-content-between"><span class="small text-muted">Giá gốc/đêm</span><span class="fw-bold">' + formatNumber(room.price_per_night) + 'đ</span></div>';
            
            if (room.extra_charge > 0) {
                html += '<div class="d-flex justify-content-between mt-1"><span class="small text-muted">+ Phụ thu/đêm</span><span class="text-warning">' + formatNumber(room.extra_charge) + 'đ</span></div>';
            }
            
            if (room.weekend_surcharge > 0) {
                html += '<div class="d-flex justify-content-between mt-1"><span class="small text-muted">+ Weekend</span><span class="text-danger">' + formatNumber(room.weekend_surcharge) + 'đ</span></div>';
            }
            
            if (voucherPerRoom > 0) {
                html += '<div class="d-flex justify-content-between mt-1"><span class="small text-muted">- Voucher</span><span class="text-success">' + formatNumber(voucherPerRoom) + 'đ</span></div>';
            }
            
            html += '<div class="d-flex justify-content-between mt-2 pt-2 border-top"><span class="small fw-bold">Tổng thực tế</span><span class="fw-bold text-primary">' + formatNumber(newTotalAfterVoucher) + 'đ</span></div>';
            html += '<div class="mt-2">' + diffBadge + '</div>';
            html += '</div></div>';
        });

        html += '</div></div>';
    });

    container.innerHTML = html;
    console.log('✅ Rooms rendered');
}

// Chọn phòng
function selectRoom(roomId) {
    try {
        const roomData = window.availableRoomsData[roomId];
        
        if (!roomData) {
            console.error('❌ Room data not found for ID:', roomId);
            alert('Không tìm thấy thông tin phòng!');
            return;
        }

        document.querySelectorAll('.room-card').forEach(e => e.classList.remove('selected'));
        document.getElementById('room-' + roomId)?.classList.add('selected');

        document.getElementById('new_room_id').value = roomId;
        document.getElementById('confirm-btn').disabled = false;

        // ✅ Chênh lệch TRƯỚC voucher (từ API)
        const priceDiffBeforeVoucher = roomData.price_difference || 0;
        const newTotalAfterVoucher = roomData.price_total_after_voucher || 0;
        const voucherPerRoom = roomData.voucher_per_room || 0;

        console.log('💰 Selection:', {
            'Price diff (before voucher)': priceDiffBeforeVoucher,
            'New total (after voucher)': newTotalAfterVoucher,
            'Voucher': voucherPerRoom
        });

        // Hiển thị phòng mới
        document.getElementById('new-room-summary').style.display = 'block';
        document.getElementById('new-room-name').textContent = '#' + roomData.code + ' - ' + roomData.name;

        let detailHTML = '<div class="small">';
        detailHTML += '<div>Giá gốc: ' + formatNumber(roomData.price_per_night) + 'đ/đêm</div>';
        
        if (roomData.extra_charge > 0) {
            detailHTML += '<div class="text-warning">+ Phụ thu: ' + formatNumber(roomData.extra_charge) + 'đ/đêm</div>';
        }
        
        if (roomData.weekend_surcharge > 0) {
            detailHTML += '<div class="text-danger">+ Cuối tuần: ' + formatNumber(roomData.weekend_surcharge) + 'đ (' + roomData.weekend_nights + ' đêm)</div>';
        }
        
        if (voucherPerRoom > 0) {
            detailHTML += '<div class="text-success">- Voucher: ' + formatNumber(voucherPerRoom) + 'đ</div>';
        }
        
        detailHTML += '</div>';

        document.getElementById('new-room-total').innerHTML = '<strong class="text-primary">' + formatNumber(newTotalAfterVoucher) + 'đ</strong> <small>(tổng thực tế)</small>';
        document.getElementById('new-room-extra').innerHTML = detailHTML;

        // ✅ Booking mới = Booking cũ + Chênh lệch (TRƯỚC voucher)
        const bookingCurrent = {{ $booking->tong_tien }};
        const bookingNew = bookingCurrent + priceDiffBeforeVoucher;

        const txt = document.getElementById('booking-new-total-txt');
        const note = document.getElementById('change-note');
        
        if (priceDiffBeforeVoucher > 0) {
            // Nâng cấp - miễn phí
            txt.textContent = formatNumber(bookingCurrent) + 'đ';
            txt.className = 'fw-bold text-success';
            note.className = 'alert alert-success mt-2 small';
            note.innerHTML = '<i class="fas fa-gift"></i> <strong>NÂNG CẤP MIỄN PHÍ</strong><br>Không tính thêm tiền';
            note.style.display = 'block';
            
        } else if (priceDiffBeforeVoucher < 0) {
            // Hạ cấp - hoàn tiền
            txt.textContent = formatNumber(bookingNew) + 'đ';
            txt.className = 'fw-bold text-warning';
            note.className = 'alert alert-warning mt-2 small';
            note.innerHTML = '<i class="fas fa-undo"></i> <strong>HẠ CẤP</strong><br>Hoàn lại: ' + formatNumber(Math.abs(priceDiffBeforeVoucher)) + 'đ';
            note.style.display = 'block';
            
        } else {
            // Ngang bằng
            txt.textContent = formatNumber(bookingCurrent) + 'đ';
            txt.className = 'fw-bold text-primary';
            note.className = 'alert alert-info mt-2 small';
            note.innerHTML = '<i class="fas fa-equals"></i> <strong>PHÒNG NGANG BẰNG</strong><br>Không đổi giá';
            note.style.display = 'block';
        }

        console.log('✅ Booking calculation:', {
            current: bookingCurrent,
            diff: priceDiffBeforeVoucher,
            new: bookingNew
        });

    } catch (err) {
        console.error('❌ selectRoom error', err);
        alert('Lỗi khi chọn phòng: ' + err.message);
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
    document.getElementById('rooms-container').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <strong>Lỗi:</strong> ' + message + '</div>';
}

// Confirm trước khi submit
document.getElementById('submit-change').addEventListener('submit', function(e) {
    const roomId = document.getElementById('new_room_id').value;
    if (!roomId) {
        e.preventDefault();
        alert('Vui lòng chọn phòng muốn đổi!');
        return false;
    }
    
    const roomData = window.availableRoomsData[roomId];
    if (!roomData) {
        e.preventDefault();
        alert('Không tìm thấy thông tin phòng!');
        return false;
    }
    
    const confirmMsg = 'Xác nhận đổi sang phòng #' + roomData.code + ' - ' + roomData.name + '?\n\n' +
                      (roomData.price_difference >= 0 ? '✓ Nâng cấp miễn phí' : '✓ Hạ cấp - Hoàn tiền cho khách');
    
    if (!confirm(confirmMsg)) {
        e.preventDefault();
        return false;
    }
});

// Load on ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Page loaded');
    loadAvailableRooms();
});
</script>

@endsection