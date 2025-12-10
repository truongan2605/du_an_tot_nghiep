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
    .price-green { color: #198754; font-weight:700; }
    .price-red { color: #dc3545; font-weight:700; }
    .small-muted { font-size: 0.85rem; color:#6c757d; }
    .summary-box { background:#fff; border-radius:10px; padding:16px; border:1px solid #e9ecef; }
</style>

@php
    $booking = $item->datPhong;
    $soDem = (int) ($item->so_dem ?? 1);

    // Giá cũ
    $oldPricePerNight = (float) ($item->gia_tren_dem ?? 0);
    $oldTotal = $oldPricePerNight * $soDem;

    // Voucher
    $voucherAmount = 0;
    if (!empty($booking->voucher_giam_phan_tram)) {
        $voucherAmount = round($oldTotal * $booking->voucher_giam_phan_tram / 100);
    } elseif (!empty($booking->voucher_giam_tien)) {
        $voucherAmount = min($booking->voucher_giam_tien, $oldTotal);
    }

    $payableOld = max(0, $oldTotal - $voucherAmount);

    $bookingOriginalTotal = (float) ($booking->tong_tien ?? $oldTotal);
@endphp

<div class="container-fluid mt-4">
    <div class="row">

        {{-- ================================================================= --}}
        {{-- LEFT: LIST PHÒNG --}}
        {{-- ================================================================= --}}
        <div class="col-md-8">

            <h4 class="mb-3">
                Chọn phòng mới cho 
                <strong>#{{ $item->phong->ma_phong }}</strong> — {{ $item->phong->name }}
            </h4>

            <div class="row g-3">

                @forelse($availableRooms as $room)
                    @php
                        $newPricePerNight = (float) ($room->tong_gia ?? $room->gia_cuoi_cung ?? 0);
                        $newTotal = $newPricePerNight * $soDem;

                        $finalOld = $payableOld;
                        $finalNew = max(0, $newTotal - $voucherAmount);

                        $diff = $finalNew - $finalOld;
                    @endphp

                    <div class="col-md-4">
                        <div class="room-card" id="room-{{ $room->id }}" onclick="selectRoom({{ $room->id }})">
                            
                            <div>
                                <img src="{{ $room->firstImageUrl() }}" class="img-fluid rounded mb-2"
                                     style="height:140px; width:100%; object-fit:cover;">
                                <strong class="d-block">
                                    {{ '#'.$room->ma_phong }} - {{ $room->name }}
                                </strong>
                                <div class="small-muted">Sức chứa: {{ $room->suc_chua }} người</div>
                            </div>

                            <div class="mt-3">

                                <div class="d-flex justify-content-between">
                                    <span class="small-muted">Giá/đêm</span>
                                    <span class="price-green">
                                        {{ number_format($newPricePerNight) }}đ
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between mt-1">
                                    <span class="small-muted">Tổng ({{ $soDem }} đêm)</span>
                                    <span>{{ number_format($newTotal) }}đ</span>
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
                    </div>

                @empty
                    <div class="col-12">
                        <div class="alert alert-warning">
                            Không tìm thấy phòng trống để đổi trong khoảng ngày này.
                        </div>
                    </div>
                @endforelse

            </div>
        </div>

        {{-- ================================================================= --}}
        {{-- RIGHT: SUMMARY --}}
        {{-- ================================================================= --}}
        <div class="col-md-4">

            <div class="summary-box">

                <h5 class="mb-3">Tóm tắt giá</h5>

                {{-- Phòng cũ --}}
                <div class="small-muted">Phòng hiện tại</div>
                <div class="fw-semibold">{{ $item->phong->name }} — {{ $soDem }} đêm</div>
                <div class="mb-2">
                    {{ number_format($oldTotal) }}đ 
                    <small class="text-muted">({{ number_format($oldPricePerNight) }}đ/đêm)</small>
                </div>

                <hr>

                {{-- Phòng mới --}}
                <div id="new-room-summary" style="display:none;">
                    <div class="small-muted">Phòng mới</div>
                    <div id="new-room-name" class="fw-semibold mb-1"></div>
                    <div id="new-room-total" class="mb-2"></div>
                </div>

                {{-- Voucher --}}
                <div class="small-muted">Voucher đã áp dụng</div>
                <div id="voucher-applied" class="mb-2 fw-semibold">
                    {{ number_format($voucherAmount) }}đ
                </div>

                {{-- Cũ & mới --}}
                <div class="d-flex justify-content-between mt-2">
                    <span class="small-muted">Phòng cũ (tổng)</span>
                    <span>{{ number_format($oldTotal) }}đ</span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <span class="small-muted">Phòng mới (tổng)</span>
                    <span id="room-new-total-txt">-</span>
                </div>

                {{-- Payable --}}
                <div class="d-flex justify-content-between mt-2">
                    <span class="small-muted">Phải trả hiện tại (sau voucher)</span>
                    <span id="payable-old-txt">{{ number_format($payableOld) }}đ</span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <span class="small-muted">Phải trả nếu đổi</span>
                    <span id="payable-new-txt">-</span>
                </div>

                {{-- Chênh lệch --}}
                <div class="d-flex justify-content-between mt-2">
                    <span class="small-muted">Chênh lệch</span>
                    <span id="diff-txt">-</span>
                </div>

                <hr>

                {{-- Tổng booking --}}
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Tổng booking hiện tại</span>
                    <span id="booking-original-total-txt">
                        {{ number_format($bookingOriginalTotal) }}đ
                    </span>
                </div>

                <div class="d-flex justify-content-between mt-1">
                    <span class="fw-bold">Tổng booking nếu đổi</span>
                    <span id="booking-new-total-txt">-</span>
                </div>

                <div id="refund-info" class="text-success mt-1 fw-semibold" style="font-size: 0.9rem;"></div>

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

{{-- ================================================================= --}}
{{-- SCRIPT CHÍNH — KHỚP 100% CONTROLLER --}}
{{-- ================================================================= --}}
<script>
function selectRoom(roomId) {

    document.querySelectorAll('.room-card').forEach(e => e.classList.remove('selected'));
    document.getElementById('room-' + roomId).classList.add('selected');

    document.getElementById('new_room_id').value = roomId;
    document.getElementById('confirm-btn').disabled = false;

    const url = "{{ route('admin.change-room.calculate', $item->id) }}?room_id=" + roomId;

    fetch(url)
        .then(res => res.json())
        .then(data => {

            // Hiển thị phòng mới
            document.getElementById('new-room-summary').style.display = 'block';
            document.getElementById('new-room-name').textContent =
                data.room_name + " — {{ $soDem }} đêm";
            document.getElementById('new-room-total').textContent =
                data.new_total_format;
            document.getElementById('room-new-total-txt').textContent =
                data.new_total_format;

            // Payable new
            document.getElementById('payable-new-txt').textContent =
                data.payable_new_format;

            // Chênh lệch
            document.getElementById('diff-txt').textContent =
                data.total_diff_format;

            // Tổng booking mới = tổng booking cũ + chênh lệch
            let bookingCurrent = {{ $bookingOriginalTotal }};
            let bookingNew = bookingCurrent + data.total_diff;

            document.getElementById('booking-new-total-txt').textContent =
                new Intl.NumberFormat().format(bookingNew) + "đ";

            // Nếu rẻ hơn → hiển thị đang được hoàn
            if (data.total_diff < 0) {
                document.getElementById('booking-new-total-txt').
                textContent 
            }

            // Voucher
            document.getElementById('voucher-applied').textContent =
                data.voucher_amount_format;

        })
        .catch(err => {
            console.error(err);
            alert("Lỗi tính giá, vui lòng thử lại.");
        });
}

</script>

@endsection
