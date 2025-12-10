@extends('layouts.admin')

@section('title', 'Đổi phòng')

@section('content')

<style>
    body .room-card {
        border: 1px solid #e6e6e6;
        border-radius: 10px;
        padding: 12px;
        background: #ffffff;
        transition: transform .12s ease, box-shadow .12s ease;
        cursor: pointer;
        height: 100%;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
    }
    .room-card:hover { transform: translateY(-4px); box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
    .room-card.selected { border-color: #0d6efd; box-shadow: 0 8px 26px rgba(13,110,253,0.12); }

    .summary-box { background:#fff; border-radius:10px; padding:16px; border:1px solid #e9ecef; }
    .small-muted { font-size: 0.85rem; color:#6c757d; }
    .price-green { color: #198754; font-weight:700; }
    .price-red { color: #dc3545; font-weight:700; }
</style>

@php
    $booking = $item->datPhong;
    $soDem = (int) ($item->so_dem ?? 1);

    // Giá cũ của item
    $oldPricePerNight = (float) $item->gia_tren_dem;
    $oldTotal = $oldPricePerNight * $soDem;

    // Voucher lấy NGUYÊN TỪ DB
    $voucher = (float) ($booking->voucher_discount ?? 0);

    $bookingOriginalTotal = (float) $booking->tong_tien;
@endphp

<div class="container-fluid mt-4">
    <div class="row">

        {{-- ===================================== --}}
        {{-- LEFT LIST PHÒNG --}}
        {{-- ===================================== --}}
        <div class="col-md-8">
            <h4 class="mb-3">
                Chọn phòng mới cho 
                <strong>#{{ $item->phong->ma_phong }}</strong> — {{ $item->phong->name }}
            </h4>

            <div class="row g-3">

                @forelse($availableRooms as $room)
                    @php
                        $newTotal = $room->tong_gia * $soDem;
                        $diff = ($newTotal - $oldTotal) - $voucher;
                    @endphp

                    <div class="col-md-4">
                        <div class="room-card" id="room-{{ $room->id }}" onclick="selectRoom({{ $room->id }})">

                            <div>
                                <img src="{{ $room->firstImageUrl() }}" class="img-fluid rounded mb-2"
                                     style="height:140px; object-fit:cover;">
                                <strong class="d-block">
                                    {{ '#'.$room->ma_phong }} - {{ $room->name }}
                                </strong>
                                <div class="small-muted">
                                    Sức chứa: {{ $room->suc_chua }} người
                                </div>
                            </div>

                            <div class="mt-3">

                                <div class="d-flex justify-content-between">
                                    <span class="small-muted">Giá/đêm</span>
                                    <span class="price-green">
                                        {{ number_format($room->tong_gia) }}đ
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between mt-1">
                                    <span class="small-muted">Tổng ({{ $soDem }} đêm)</span>
                                    <span>{{ number_format($newTotal) }}đ</span>
                                </div>

                                <div class="mt-2">
                                    @if($diff < 0)
                                        <span class="badge bg-success">Giảm {{ number_format(abs($diff)) }}đ</span>
                                    @elseif($diff > 0)
                                        <span class="badge bg-danger">Tăng {{ number_format($diff) }}đ</span>
                                    @else
                                        <span class="badge bg-secondary">Không đổi</span>
                                    @endif
                                </div>

                            </div>

                        </div>
                    </div>

                @empty
                    <div class="col-12">
                        <div class="alert alert-warning">Không có phòng trống.</div>
                    </div>
                @endforelse

            </div>
        </div>

        {{-- ===================================== --}}
        {{-- RIGHT SUMMARY --}}
        {{-- ===================================== --}}
        <div class="col-md-4">
            <div class="summary-box">

                <h5 class="mb-3">Tóm tắt giá</h5>

                <div class="small-muted">Phòng hiện tại</div>
                <div class="fw-semibold mb-1">{{ $item->phong->name }} — {{ $soDem }} đêm</div>
                <div>{{ number_format($oldTotal) }}đ</div>

                <hr>

                <div id="new-room-summary" style="display:none;">
                    <div class="small-muted">Phòng mới</div>
                    <div id="new-room-name" class="fw-semibold mb-1"></div>
                    <div id="new-room-total" class="mb-2"></div>
                </div>

                <div class="small-muted">Voucher đã áp dụng</div>
                <div id="voucher-applied" class="fw-bold mb-2">
                    {{ number_format($voucher) }}đ
                </div>

                <div class="d-flex justify-content-between mt-2">
                    <span class="small-muted">Phòng cũ</span>
                    <span>{{ number_format($oldTotal) }}đ</span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <span class="small-muted">Phòng mới</span>
                    <span id="room-new-total-txt">-</span>
                </div>

                <div class="d-flex justify-content-between mt-2">
                    <span class="small-muted">Chênh lệch</span>
                    <span id="diff-txt">-</span>
                </div>

                <hr>

                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Tổng booking hiện tại</span>
                    <span>{{ number_format($bookingOriginalTotal) }}đ</span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <span class="fw-bold">Tổng booking nếu đổi</span>
                    <span id="booking-new-total-txt">-</span>
                </div>

                <form id="submit-change" method="POST"
                      action="{{ route('admin.change-room.apply', $item->id) }}"
                      class="mt-3">
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

{{-- ===================================== --}}
{{-- JS AJAX TÍNH GIÁ --}}
{{-- ===================================== --}}
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
            document.getElementById('new-room-name').textContent = data.room_name + " — {{ $soDem }} đêm";
            document.getElementById('new-room-total').textContent = data.new_total_format;

            document.getElementById('room-new-total-txt').textContent = data.new_total_format;
            document.getElementById('voucher-applied').textContent = data.voucher_amount_format;
            document.getElementById('diff-txt').textContent = data.total_diff_format;

            let newBooking = {{ $bookingOriginalTotal }} + data.total_diff;
            document.getElementById('booking-new-total-txt').textContent =
                new Intl.NumberFormat().format(newBooking) + "đ";
        })
        .catch(() => alert("Lỗi tính giá!"));
}
</script>

@endsection
