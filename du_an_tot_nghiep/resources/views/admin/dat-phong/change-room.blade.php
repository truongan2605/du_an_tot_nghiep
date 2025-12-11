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
    }

    /* Mỗi loại phòng = 1 hàng scroll ngang */
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
    }
</style>

@php
    $booking = $item->datPhong;
    $soDem   = (int) $item->so_dem;

    // GIÁ PHÒNG CŨ
    $oldRoomPrice = (float)$item->gia_tren_dem * $soDem;

    // PHỤ THU CŨ
    $extraFee = (float)$item->tong_item - $oldRoomPrice;
    if ($extraFee < 0) $extraFee = 0;

    // CHIA VOUCHER THEO SỐ PHÒNG
    $roomCount = $booking->items->count() ?: 1;

    $voucherItem = 0;
    if (!empty($booking->discount_amount) && $booking->discount_amount > 0) {
        $voucherItem = (float)$booking->discount_amount / $roomCount;
    } elseif (!empty($booking->voucher_discount) && $booking->voucher_discount > 0) {
        $voucherItem = (float)$booking->voucher_discount / $roomCount;
    }

    // TỔNG CẦN TRẢ CŨ
    $payableOld = max(0, ($oldRoomPrice + $extraFee) - $voucherItem);

    // GROUP ROOMS BY TYPE
    $grouped = $availableRooms->groupBy('loai_phong_id');
@endphp


<div class="container-fluid mt-4">
    <div class="row">

        {{-- ===================================================== --}}
        {{-- LEFT: DANH SÁCH PHÒNG THEO TỪNG LOẠI --}}
        {{-- ===================================================== --}}
        <div class="col-md-8">

            <h4 class="mb-3">
                Chọn phòng mới cho 
                <strong>#{{ $item->phong->ma_phong }}</strong> — {{ $item->phong->name }}
            </h4>

            {{-- LOOP LOẠI PHÒNG --}}
            @foreach($grouped as $typeId => $roomsOfType)

                <div class="room-type-section">

                    <div class="room-type-title">
                        {{ optional(\App\Models\LoaiPhong::find($typeId))->name ?? 'Không xác định' }}
                    </div>

                    <div class="rooms-slider">

                        @foreach($roomsOfType as $room)

                            @php
                                $newRoomPrice = (float)$room->tong_gia * $soDem;
                                $totalNew = $newRoomPrice + $extraFee;
                                $finalNew = max(0, $totalNew - $voucherItem);
                                $diff = $finalNew - $payableOld;
                            @endphp

                            <div class="room-card" id="room-{{ $room->id }}"
                                 onclick="selectRoom({{ $room->id }})">

                                <img src="{{ $room->firstImageUrl() }}"
                                     style="height:150px;width:100%;object-fit:cover;"
                                     class="rounded mb-2">

                                <strong>#{{ $room->ma_phong }} - {{ $room->name }}</strong>

                                <div class="text-muted small">Sức chứa: {{ $room->suc_chua }}</div>

                                <div class="mt-3">
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-muted">Giá/đêm</span>
                                        <span class="fw-bold">{{ number_format($room->tong_gia) }}đ</span>
                                    </div>

                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="small text-muted">Giá {{ $soDem }} đêm</span>
                                        <span>{{ number_format($newRoomPrice) }}đ</span>
                                    </div>

                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="small text-muted">+ Phụ thu</span>
                                        <span>{{ number_format($extraFee) }}đ</span>
                                    </div>

                                    <div class="mt-2">
                                        @if($diff < 0)
                                            <span class="badge bg-success">Tiết kiệm {{ number_format(abs($diff)) }}đ</span>
                                        @elseif($diff > 0)
                                            <span class="badge bg-danger">Tăng {{ number_format($diff) }}đ</span>
                                        @else
                                            <span class="badge bg-secondary">Không đổi</span>
                                        @endif
                                    </div>

                                </div>
                            </div>

                        @endforeach

                    </div>
                </div>

            @endforeach

        </div>



        {{-- ===================================================== --}}
        {{-- RIGHT: SUMMARY --}}
        {{-- ===================================================== --}}
        <div class="col-md-4">

            <div class="summary-box">

                <h5 class="mb-3">Tóm tắt giá</h5>

                <div class="small text-muted">Phòng hiện tại</div>
                <div class="fw-bold">{{ $item->phong->name }} — {{ $soDem }} đêm</div>

                <div>
                    {{ number_format($oldRoomPrice) }}đ
                    <small class="text-muted">({{ number_format($item->gia_tren_dem) }}/đêm)</small>
                </div>

                <div class="mt-1 text-muted small">+ Phụ thu: {{ number_format($extraFee) }}đ</div>

                <hr>

                <div id="new-room-summary" style="display:none;">
                    <div class="small text-muted">Phòng mới</div>
                    <div id="new-room-name" class="fw-bold"></div>
                    <div id="new-room-total" class="fw-semibold mb-1"></div>
                </div>

                <div class="text-muted small mt-2">Voucher chia cho phòng:</div>
                <div id="voucher-applied" class="fw-bold">
                    {{ number_format($voucherItem) }}đ
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Tổng booking hiện tại</span>
                    <span>{{ number_format($booking->tong_tien) }}đ</span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <span class="fw-bold">Tổng booking nếu đổi</span>
                    <span id="booking-new-total-txt">-</span>
                </div>

                <hr>

                <form id="submit-change" method="POST"
                      action="{{ route('admin.change-room.apply', $item->id) }}">
                    @csrf
                    <input type="hidden" name="new_room_id" id="new_room_id">

                    <button class="btn btn-primary w-100" disabled id="confirm-btn">
                        Xác nhận đổi phòng
                    </button>
                </form>

            </div>

        </div>

    </div>
</div>


{{-- ===================================================== --}}
{{-- JS — TÍNH GIÁ REALTIME --}}
{{-- ===================================================== --}}
<script>
function selectRoom(roomId) {

    document.querySelectorAll('.room-card').forEach(e => e.classList.remove('selected'));
    document.getElementById('room-' + roomId).classList.add('selected');

    document.getElementById('new_room_id').value = roomId;
    document.getElementById('confirm-btn').disabled = false;

    fetch("{{ route('admin.change-room.calculate', $item->id) }}?room_id=" + roomId)
        .then(res => res.json())
        .then(data => {

            document.getElementById('new-room-summary').style.display = 'block';

            document.getElementById('new-room-name').textContent = data.room_name;
            document.getElementById('new-room-total').textContent = data.new_total_format;

            document.getElementById('voucher-applied').textContent = data.voucher_amount_format;

            const bookingOriginal = Number({{ $booking->tong_tien }});
            const diff = Number(data.total_diff ?? 0);

            document.getElementById('booking-new-total-txt').textContent =
                new Intl.NumberFormat().format(bookingOriginal + diff) + 'đ';
        })
        .catch(err => {
            alert("Lỗi tính giá!");
            console.error(err);
        });
}
</script>

@endsection
