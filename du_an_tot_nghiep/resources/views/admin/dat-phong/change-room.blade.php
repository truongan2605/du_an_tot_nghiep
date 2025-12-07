@extends('layouts.admin')

@section('title', 'Đổi phòng')

@section('content')
<style>
    .room-card { 
        border: 2px solid #444; 
        border-radius: 12px;
        padding: 16px; 
        background: #111; 
        transition: 0.2s; 
        cursor: pointer;
    }
    .room-card.selected { 
        border-color: #4a90ff;
        background: #1a2333; 
    }
    .price-green { color: #31d67b; font-weight: 700; }
    .price-red { color: #ff4e4e; font-weight: 700; }
</style>

<div class="row mt-3">

    {{-- ===================== --}}
    {{-- PANEL TRÁI: DANH SÁCH PHÒNG --}}
    {{-- ===================== --}}
    <div class="col-md-8">
        <h4 class="text-white mb-3">
            Chọn phòng mới cho #{{ $item->phong->ma_phong }} - {{ $item->phong->name }}
        </h4>

        <div class="row">
            @foreach($availableRooms as $room)
            @php
                $oldPrice = $item->tong_item;
                $newPrice = $room->tong_gia;
                $diff = $newPrice - $oldPrice;

                /** AP DUNG VOUCHER */
                $booking = $item->datPhong;
                $tempTotal = $booking->tong_tien;

                if($booking->voucher_giam_phan_tram){
                    $tempTotal -= ($tempTotal * $booking->voucher_giam_phan_tram) / 100;
                }
                if($booking->voucher_giam_tien){
                    $tempTotal -= $booking->voucher_giam_tien;
                }

            @endphp

            <div class="col-md-4 mb-3">
                <div class="room-card" onclick="selectRoom({{ $room->id }})" id="room-{{ $room->id }}">
                    
                    <img src="{{ $room->firstImageUrl() }}" class="img-fluid rounded mb-2" />

                    <strong class="d-block text-white">
                        #{{ $room->ma_phong }} - {{ $room->name }}
                    </strong>

                    <div class="text-muted">👥 {{ $room->suc_chua }} người</div>

                    <div class="mt-2">
                        <span class="text-white">Giá/đêm:</span>
                        <span class="price-green">{{ number_format($newPrice) }}đ</span>
                    </div>

                    {{-- CHÊNH LỆCH --}}
                    <div class="mt-2">
                        @if($diff < 0)
                            <span class="badge bg-success">
                                -{{ number_format(abs($diff)) }}đ
                            </span>
                        @elseif($diff > 0)
                            <span class="badge bg-danger">
                                +{{ number_format($diff) }}đ
                            </span>
                        @endif
                    </div>

                    <button class="btn btn-outline-light mt-3 w-100">
                        Chọn phòng
                    </button>

                </div>
            </div>
            @endforeach
        </div>
    </div>


    {{-- ===================== --}}
    {{-- PANEL PHẢI: TÍNH GIÁ --}}
    {{-- ===================== --}}
    <div class="col-md-4">
        <div class="card bg-dark text-white p-3" id="price-summary">

            <h5>Chi tiết giá cả</h5>

            {{-- PHÒNG CŨ --}}
            <div class="p-2 my-2" style="background:#1a1a1a;border-radius:10px;">
                <div class="text-muted">Phòng hiện tại</div>
                <strong>{{ $item->phong->name }}</strong> <br>
                <span class="text-muted">#{{ $item->phong->ma_phong }}</span>
                <div class="price-green">{{ number_format($item->tong_item) }}đ/đêm</div>
            </div>

            {{-- PHÒNG MỚI --}}
            <div id="new-room-box" class="p-2 my-2" style="background:#1a1a1a;border-radius:10px;display:none;">
                <div class="text-muted">Phòng mới</div>
                <strong id="new-room-name"></strong>
                <div id="new-room-price" class="price-green"></div>
            </div>

            <hr class="border-secondary">

            <div id="price-details" style="display:none;">

                <div class="d-flex justify-content-between">
                    <span>Chênh lệch mỗi đêm:</span>
                    <span id="diff-per-night"></span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <span>Tổng chênh lệch:</span>
                    <strong id="total-diff"></strong>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <span class="fw-bold">Tổng booking mới:</span>
                    <span id="new-total" class="price-green"></span>
                </div>

                {{-- 🔥 HOÀN TIỀN QUA VOUCHER --}}
                <div id="voucher-box" class="mt-4 p-3" style="background:#0e2c1f;border-radius:10px;display:none;">
                    <strong>🎁 Hoàn tiền qua Voucher</strong>
                    <p class="text-muted mt-1">
                        Bạn đang chọn phòng rẻ hơn! Số tiền chênh lệch sẽ được hoàn vào voucher:
                    </p>
                    <h4 class="price-green" id="voucher-refund"></h4>

                    <p class="text-muted">
                        Voucher có thời hạn 30 ngày và chỉ dùng cho booking tiếp theo.
                    </p>
                </div>

            </div>

        </div>

        <form id="submit-change" method="POST" action="{{ route('admin.change-room.apply', $item->id) }}">
            @csrf
            <input type="hidden" name="new_room_id" id="new_room_id">
            <button class="btn btn-primary w-100 mt-3" disabled id="confirm-btn">
                Xác nhận đổi phòng
            </button>
        </form>

    </div>

</div>

<script>
function selectRoom(roomId) {
    // highlight chọn
    document.querySelectorAll('.room-card').forEach(e => e.classList.remove('selected'));
    document.getElementById('room-' + roomId).classList.add('selected');

    document.getElementById('new_room_id').value = roomId;
    document.getElementById('confirm-btn').disabled = false;

    // gọi API tính giá
    fetch("{{ route('admin.change-room.calculate', $item->id) }}?room_id=" + roomId)
        .then(res => res.json())
        .then(data => updatePriceUI(data));
}

function updatePriceUI(data){
    document.getElementById('new-room-box').style.display = 'block';
    document.getElementById('price-details').style.display = 'block';

    document.getElementById('new-room-name').innerHTML = data.room_name;
    document.getElementById('new-room-price').innerHTML = data.room_price_format;

    document.getElementById('diff-per-night').innerHTML = data.diff_per_night_format;
    document.getElementById('total-diff').innerHTML = data.total_diff_format;

    document.getElementById('new-total').innerHTML = data.new_total_format;

    // voucher refund
    if(data.refund > 0){
        document.getElementById('voucher-refund').innerHTML = data.refund_format;
        document.getElementById('voucher-box').style.display = 'block';
    } else {
        document.getElementById('voucher-box').style.display = 'none';
    }
}
</script>

@endsection
