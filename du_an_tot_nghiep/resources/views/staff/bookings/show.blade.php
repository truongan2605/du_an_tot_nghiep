@extends('layouts.admin')

@section('title', 'Chi Tiết Booking:' . $booking->ma_tham_chieu)

@section('content')
    <div class="container-fluid py-5">
        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="display-6 fw-bold text-gradient-primary">
                <i class="bi bi-journal-bookmark-fill me-2"></i>
                Booking :{{ $booking->ma_tham_chieu }}
            </h1>
            <p class="text-muted">Thông tin chi tiết đặt phòng</p>
        </div>

        <!-- Main Card -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header bg-gradient-primary text-white py-4 position-relative overflow-hidden">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-4 me-3" style="color: rgb(38, 81, 168)"></i>
                    <h5 class="mb-0 fw-bold" style="color: black">Thông Tin Booking</h5>
                </div>
                <div class="position-absolute end-0 top-50 translate-middle-y pe-5 opacity-10">
                    <i class="bi bi-calendar-check fs-1" style="color: black"></i>
                </div>
            </div>

            <div class="card-body p-5">
                <div class="row g-5">

                    <div class="col-lg-6">
                        <h6 class="text-primary fw-bold mb-4"><i class="bi bi-person-circle me-2"></i>Khách Hàng</h6>
                        <div class="ps-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-tag-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Mã Booking</small>
                                    <p class="mb-0 fw-bold text-dark">#{{ $booking->ma_tham_chieu }}</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-person-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Họ Tên</small>
                                    <p class="mb-0">
                                        {{ $booking->nguoiDung?->name ?? ($booking->customer_name ?? 'Ẩn danh') }}</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-envelope-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Email</small>
                                    <p class="mb-0">{{ $booking->nguoiDung?->email ?? ($booking->email ?? 'N/A') }}</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-telephone-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Số Điện Thoại</small>
                                    <p class="mb-0">{{ $booking->contact_phone ?? ($booking->phone ?? 'N/A') }}</p>
                                </div>
                            </div>
                              <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-telephone-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Địa chỉ</small>
                                    <p class="mb-0">{{ $booking->contact_address ?? ($booking->address ?? 'N/A') }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-telephone-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Ghi chú</small>
                                    <p class="mb-0">{{ $booking->ghi_chu ?? ($booking->ghi_chu ?? '...') }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-patch-check-fill text-muted me-3 fs-5"></i>
                                <div>
                                    <small class="text-muted">Trạng Thái</small>
                                    <div class="mt-1">
                                        @php
                                            use Illuminate\Support\Str;

                                            $status = $booking->trang_thai;

                                            $statusClasses = [
                                                'dang_su_dung' => 'bg-success',
                                                'dang_cho' => 'bg-warning text-dark',
                                                'dang_cho_xac_nhan' => 'bg-info text-dark',
                                                'da_xac_nhan' => 'bg-success', // đã xác nhận
                                                'da_gan_phong' => 'bg-info text-dark', // đã gán phòng
                                                'da_huy' => 'bg-secondary',
                                                'hoan_thanh' => 'bg-primary',
                                                'dang_o' => 'bg-indigo text-white',
                                            ];

                                            $statusIcons = [
                                                'dang_su_dung' => 'bi-check-circle',
                                                'dang_cho' => 'bi-hourglass-split',
                                                'dang_cho_xac_nhan' => 'bi-clock-history',
                                                'da_xac_nhan' => 'bi-check2-circle',
                                                'da_gan_phong' => 'bi-door-open',
                                                'da_huy' => 'bi-x-circle',
                                                'hoan_thanh' => 'bi-check2-all',
                                                'dang_o' => 'bi-house-door',
                                            ];

                                            $statusLabels = [
                                                'dang_su_dung' => 'Đang sử dụng',
                                                'dang_cho' => 'Đang chờ',
                                                'dang_cho_xac_nhan' => 'Đang chờ xác nhận',
                                                'da_xac_nhan' => 'Đã xác nhận',
                                                'da_gan_phong' => 'Đã gán phòng',
                                                'da_huy' => 'Đã hủy',
                                                'hoan_thanh' => 'Hoàn thành',
                                                'dang_o' => 'Đang ở',
                                            ];

                                            // fallback an toàn: chuyển underscore -> khoảng trắng và viết hoa từ đầu (Str::title xử lý multibyte)
                                            $label =
                                                $statusLabels[$status] ?? Str::title(str_replace('_', ' ', $status));
                                            $class = $statusClasses[$status] ?? 'bg-dark';
                                            $icon = $statusIcons[$status] ?? 'bi-question-circle';
                                        @endphp

                                        <span class="badge rounded-pill px-3 py-2 fs-7 {{ $class }}">
                                            <i class="bi {{ $icon }} me-1"></i>
                                            {{ $label }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-check text-muted me-3 fs-5"></i>
                                <div>
                                    <small class="text-muted">Trạng Thái Check-in</small>
                                    <div class="mt-1">
                                        @if ($booking->checked_in_at)
                                            <span
                                                class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-2 fs-7">
                                                <i class="bi bi-clock-history me-1"></i>
                                                Đã check-in lúc {{ $booking->checked_in_at->format('d/m/Y H:i:s') }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary text-white rounded-pill px-3 py-2 fs-7">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Chưa check-in
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mt-3">
                                <i class="bi bi-box-arrow-right text-muted me-3 fs-5"></i>
                                <div>
                                    <small class="text-muted">Trạng Thái Checkout</small>
                                    <div class="mt-1">
                                        @if ($booking->checkout_at)
                                            <span
                                                class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-2 fs-7">
                                                <i class="bi bi-clock-history me-1"></i>
                                                Đã checkout lúc {{ $booking->checkout_at->format('d/m/Y H:i:s') }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary text-white rounded-pill px-3 py-2 fs-7">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Chưa checkout
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-6">
                        <h6 class="text-primary fw-bold mb-4"><i class="bi bi-calendar3 me-2"></i>Chi Tiết Đặt Phòng</h6>
                        <div class="ps-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-calendar-check text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Nhận Phòng</small>
                                    <p class="mb-0 fw-bold">
                                        {{ $booking->ngay_nhan_phong ? \Carbon\Carbon::parse($booking->ngay_nhan_phong)->format('d/m/Y H:i') : '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-calendar-x text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Trả Phòng</small>
                                    <p class="mb-0 fw-bold">
                                        {{ $booking->ngay_tra_phong ? \Carbon\Carbon::parse($booking->ngay_tra_phong)->format('d/m/Y H:i') : '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-moon-stars-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Số Đêm</small>
                                    <p class="mb-0 fw-bold">
                                        {{ $meta['nights'] ?? ($booking->ngay_nhan_phong && $booking->ngay_tra_phong ? \Carbon\Carbon::parse($booking->ngay_nhan_phong)->diffInDays($booking->ngay_tra_phong) : '-') }}
                                        đêm</p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-currency-exchange text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Tổng Tiền</small>
                                    <p class="mb-0 fs-5 fw-bold text-success">{{ number_format($booking->tong_tien, 0) }} ₫
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <i class="bi bi-credit-card-2-front-fill text-muted me-3"></i>
                                <div>
                                    <small class="text-muted">Phương Thức</small>
                                    <p class="mb-0">{{ $booking->phuong_thuc_thanh_toan ?? 'VN PAY' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <hr class="my-5">

                <h6 class="text-primary fw-bold mb-4"><i class="bi bi-door-open-fill me-2"></i>Phòng Đã Gán</h6>

                @php
                    $availableFoods = \App\Models\VatDung::where('active', 1)
                        ->where('loai', \App\Models\VatDung::LOAI_DO_AN ?? 'do_an')
                        ->orderBy('ten')
                        ->get();

                    $roomSource =
                        $booking->trang_thai === 'hoan_thanh' &&
                        isset($roomLinesFromInvoice) &&
                        $roomLinesFromInvoice->isNotEmpty()
                            ? $roomLinesFromInvoice
                            : $booking->datPhongItems;
                @endphp

                @if ($roomSource instanceof \Illuminate\Support\Collection && $roomSource->isNotEmpty())
                    @foreach ($roomSource as $item)
                        @php
                            // item có thể là model (dat_phong_item) hoặc array (từ roomLinesFromInvoice)
                            $isArrayLine = is_array($item);
                            $phongId = $isArrayLine ? $item['phong_id'] ?? null : $item->phong?->id ?? null;
                            $phongCode = $isArrayLine ? $item['ma_phong'] ?? null : $item->phong?->ma_phong ?? null;
                            $loaiText = $isArrayLine ? $item['loai'] ?? 'N/A' : $item->loaiPhong?->ten ?? 'N/A';
                            $qty = $isArrayLine ? $item['qty'] ?? 1 : $item->so_luong ?? 1;
                            $unitPrice = $isArrayLine ? $item['unit_price'] ?? 0 : $item->gia_tren_dem ?? 0;
                        @endphp

                        <div class="card border-0 shadow-sm mb-3 rounded-3 overflow-hidden">
                            <div class="card-body py-3 px-4">
                                <div class="row align-items-center text-sm">
                                    <div class="col-md-3 d-flex align-items-center">
                                        <strong class="text-primary">#{{ $phongCode ?? 'Chưa gán' }}</strong>

                                        {{-- Dịch vụ gọi thêm chỉ khi booking đang sử dụng --}}
                                        @if ($booking->trang_thai === 'dang_su_dung')
                                            <button type="button" class="btn btn-sm btn-outline-primary ms-3"
                                                data-bs-toggle="modal" data-bs-target="#addFoodModal"
                                                data-phong-id="{{ $phongId }}"
                                                data-phong-code="{{ $phongCode }}">
                                                <i class="bi bi-plus-lg me-1"></i> Dịch vụ gọi thêm
                                            </button>
                                        @endif

                                        {{-- Bản thể: chỉ hiện khi booking dang_su_dung --}}
                                        @if ($booking->trang_thai === 'dang_su_dung' && !empty($phongId))
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary ms-2 btn-open-room-instances"
                                                data-phong-id="{{ $phongId }}"
                                                data-phong-code="{{ $phongCode }}">
                                                <i class="bi bi-gear me-1"></i> Bản thể
                                            </button>
                                        @endif
                                    </div>

                                    <div class="col-md-3">
                                        <i class="bi bi-building me-1"></i> {{ $loaiText }}
                                    </div>

                                    <div class="col-md-2 text-center">
                                        <span class="badge bg-light text-dark border">{{ $qty }} phòng</span>
                                    </div>

                                    <div class="col-md-4 text-end">
                                        <strong class="text-success">{{ number_format($unitPrice, 0) }} ₫</strong>/đêm
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 mb-3 d-block"></i>
                        <p>Chưa có phòng nào được gán.</p>
                    </div>
                @endif

                <!-- Modal: Thêm đồ ăn (gọi thêm) -->
                <div class="modal fade" id="addFoodModal" tabindex="-1" aria-labelledby="addFoodModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <form id="addFoodForm" method="POST" action="{{ route('phong.consumptions.store') }}">
                            @csrf
                            <input type="hidden" name="dat_phong_id" value="{{ $booking->id }}">
                            <input type="hidden" name="phong_id" id="modal_phong_id" value="">
                            <input type="hidden" name="bill_now" value="1">
                            <div class="modal-content" style=" margin-left:230px">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addFoodModalLabel">Thêm dịch vụ cho phòng <span
                                            id="modal_phong_code"></span></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body" style="min-width: 400px;">
                                    <div class="mb-3">
                                        <label class="form-label">Dịch vụ</label>
                                        <select name="vat_dung_id" id="modal_vat_dung_id" class="form-select" required>
                                            <option value="">— Chọn dịch vụ —</option>
                                            @foreach ($availableFoods as $fd)
                                                <option value="{{ $fd->id }}" data-price="{{ $fd->gia ?? 0 }}">
                                                    {{ $fd->ten }} ({{ number_format($fd->gia ?? 0, 0, ',', '.') }}
                                                    đ)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label">Số lượng</label>
                                            <input type="number" name="quantity" id="modal_quantity"
                                                class="form-control" value="1" min="1" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Giá / đơn vị</label>
                                            <input type="number" name="unit_price" id="modal_unit_price"
                                                class="form-control" value="0" min="0" step="0.01"
                                                required>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label">Ghi chú</label>
                                        <textarea name="note" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                    <button type="submit" class="btn btn-primary">Thêm & Tính tiền</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var addFoodModalEl = document.getElementById('addFoodModal');
                        if (addFoodModalEl) {
                            addFoodModalEl.addEventListener('show.bs.modal', function(event) {
                                var button = event.relatedTarget;
                                var phongId = button ? button.getAttribute('data-phong-id') || '' : '';
                                var phongCode = button ? button.getAttribute('data-phong-code') || '' : '';
                                document.getElementById('modal_phong_id').value = phongId;
                                document.getElementById('modal_phong_code').innerText = phongCode || '(chưa gán)';
                                document.getElementById('modal_vat_dung_id').value = '';
                                document.getElementById('modal_quantity').value = 1;
                                document.getElementById('modal_unit_price').value = 0;
                            });
                        }

                        var vatSelect = document.getElementById('modal_vat_dung_id');
                        if (vatSelect) {
                            vatSelect.addEventListener('change', function() {
                                var opt = this.options[this.selectedIndex];
                                var p = opt ? opt.getAttribute('data-price') : 0;
                                document.getElementById('modal_unit_price').value = p ? Number(p) : 0;
                            });
                        }
                    });
                </script>

                {{-- ===================== Hiển thị: Đồ ăn gọi thêm & Vật dụng sự cố ===================== --}}
                @php
                    $consByRoom = $consumptions ?? collect();
                    $incByRoom = $incidents ?? collect();

                @endphp

                <hr class="my-5">
                <h6 class="text-primary fw-bold mb-4"><i class="bi bi-basket-fill me-2"></i>Đồ Ăn Gọi Thêm & Sự Cố</h6>

                @php $anyShown = false; @endphp

                @foreach ($roomSource as $roomEntry)
                    @php
                        $isArrayLine = is_array($roomEntry);
                        $phId = $isArrayLine ? $roomEntry['phong_id'] ?? null : $roomEntry->phong?->id ?? null;
                        $phCode = $isArrayLine ? $roomEntry['ma_phong'] ?? null : $roomEntry->phong?->ma_phong ?? null;
                        $roomCons = $consByRoom->has($phId) ? $consByRoom->get($phId) : collect();
                        $roomIncidents = $incByRoom->has($phId) ? $incByRoom->get($phId) : collect();
                    @endphp

                    @if ($roomCons->isNotEmpty() || $roomIncidents->isNotEmpty())
                        @php $anyShown = true; @endphp
                        <div class="card border-0 shadow-sm mb-3 rounded-3 overflow-hidden">
                            <div class="card-body py-3 px-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong class="text-primary">#{{ $phCode ?? 'Chưa gán' }}</strong>
                                        <div class="small text-muted">
                                            {{ $isArrayLine ? $roomEntry['loai'] ?? 'N/A' : $roomEntry->loaiPhong?->ten ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="text-end small">
                                        <div>{{ $isArrayLine ? $roomEntry['qty'] ?? 1 : $roomEntry->so_luong ?? 1 }}
                                            phòng</div>
                                        <div class="fw-semibold text-success">
                                            {{ number_format($isArrayLine ? $roomEntry['unit_price'] ?? 0 : $roomEntry->gia_tren_dem ?? 0, 0) }}
                                            ₫/đêm</div>
                                    </div>
                                </div>

                                @if ($roomCons->isNotEmpty())
                                    <div class="mt-3">
                                        <div class="small text-muted mb-2">Đồ ăn / Dịch vụ đã gọi thêm</div>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($roomCons as $c)
                                                <li class="small mb-1">
                                                    <strong>{{ $c->quantity }} ×
                                                        {{ $c->vatDung?->ten ?? '#VD' . $c->vat_dung_id }}</strong>
                                                    — <span
                                                        class="fw-semibold">{{ number_format($c->unit_price * $c->quantity, 0) }}
                                                        ₫</span>
                                                    @if ($c->billed_at)
                                                        <span class="badge bg-success ms-2 small">Đã tính</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark ms-2 small">Chưa
                                                            tính</span>
                                                    @endif
                                                    <div class="text-muted small mt-1">
                                                        @if ($c->consumed_at)
                                                            <span title="Thời gian tiêu thụ">Thời gian đánh dấu
                                                                {{ \Carbon\Carbon::parse($c->consumed_at)->format('d/m H:i') }}</span>
                                                            &nbsp;·&nbsp;
                                                        @endif
                                                        <strong title="Người tạo">Người đánh dấu:
                                                            {{ $c->creator?->name ?? ($c->created_by ? 'UID#' . $c->created_by : '—') }}</strong>
                                                        @if ($c->note)
                                                            &nbsp;·&nbsp; Ghi chú: {{ Str::limit($c->note, 80) }}
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @php
                                    $visibleIncidents = collect();
                                    if ($roomIncidents instanceof \Illuminate\Support\Collection) {
                                        $visibleIncidents = $roomIncidents
                                            ->filter(function ($x) {
                                                if (
                                                    isset($x->type) ||
                                                    isset($x->reported_at) ||
                                                    isset($x->dat_phong_id)
                                                ) {
                                                    return true;
                                                }
                                                if (isset($x->status)) {
                                                    return $x->status !==
                                                        \App\Models\PhongVatDungInstance::STATUS_PRESENT;
                                                }
                                                return false;
                                            })
                                            ->values();
                                    }
                                @endphp

                                @if ($visibleIncidents->isNotEmpty())
                                    <div class="mt-3">
                                        <div class="small text-muted mb-2">Sự cố / Vật dụng hỏng</div>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($visibleIncidents as $ins)
                                                @php
                                                    $incidentPrice =
                                                        $ins->fee ??
                                                        ($ins->price ??
                                                            ($ins->amount ?? ($ins->vatDung?->gia ?? null)));
                                                    $incidentNote =
                                                        $ins->description ?? ($ins->note ?? ($ins->ghi_chu ?? null));
                                                    $reportedAt = $ins->reported_at ?? ($ins->created_at ?? null);
                                                    $marker =
                                                        $ins->reported_by_user?->name ??
                                                        ($ins->reporter?->name ??
                                                            ($ins->creator?->name ??
                                                                ($ins->reported_by ??
                                                                    ($ins->created_by
                                                                        ? 'UID#' . $ins->created_by
                                                                        : null))));
                                                    $isBilled = !empty($ins->billed);
                                                    // mã booking liên quan (nếu có)
                                                    $belongsBookingId = $ins->dat_phong_id ?? null;
                                                    $belongsBookingCode = $bookingMap[$belongsBookingId] ?? null;
                                                    // nếu đã billed: show mã booking nơi nó được billed (nếu có)
                                                    $billedBookingCode = $ins->billed_booking_code ?? null;
                                                    $billedHoaDonId = $ins->billed_hoa_don_id ?? null;
                                                @endphp

                                                <li
                                                    class="small mb-1 @if ($isBilled) text-success @else text-danger @endif">
                                                    <strong>{{ $ins->vatDung?->ten ?? '#VD' . ($ins->vat_dung_id ?? '') }}</strong>

                                                    @if (is_numeric($incidentPrice))
                                                        — Giá: <span
                                                            class="fw-semibold">{{ number_format($incidentPrice, 0) }}
                                                            ₫</span>
                                                    @endif

                                                    {{-- @if ($isBilled)
                                                        <span class="badge bg-success ms-2 small">Đã tính vào hoá đơn
                                                            #{{ $billedBookingCode }}</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark ms-2 small">Chưa
                                                            tính</span>
                                                    @endif --}}

                                                    {{-- Luôn show booking code (nếu có) để dễ truy cứu --}}
                                                    @if ($belongsBookingCode)
                                                        <div class="mt-1"><strong class=" badge bg-success">Thuộc
                                                                booking:
                                                                {{ $belongsBookingCode }}</strong></div>
                                                    @endif

                                                    <div class="text-muted small mt-1">
                                                        @if ($reportedAt)
                                                            <span>Thời gian:
                                                                {{ \Carbon\Carbon::parse($reportedAt)->format('d/m H:i') }}</span>
                                                            &nbsp;·&nbsp;
                                                        @endif
                                                        <strong>Người đánh dấu:</strong> {{ $marker ?? '—' }}
                                                        @if ($incidentNote)
                                                            &nbsp;·&nbsp; Ghi chú: {{ Str::limit($incidentNote, 100) }}
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach

                @if (!$anyShown)
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        <p class="mb-0">Chưa có đồ ăn gọi thêm hoặc sự cố nào được ghi nhận cho booking này.</p>
                    </div>
                @endif
                {{-- ===================== END Đồ ăn & sự cố ===================== --}}

                @if ($booking->giaoDichs->count() > 0)
                    <hr class="my-5">
                    <h6 class="text-primary fw-bold mb-4"><i class="bi bi-receipt me-2"></i>Lịch Sử Giao Dịch</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã GD</th>
                                    <th>Nhà Cung Cấp</th>
                                    <th class="text-end">Số Tiền Cọc</th>
                                    <th class="text-center">Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($booking->giaoDichs as $giaoDich)
                                    <tr>
                                        <td><code>#{{ $giaoDich->id }}</code></td>
                                        <td>{{ $giaoDich->nha_cung_cap ?? 'N/A' }}</td>
                                        <td class="text-end fw-bold text-success">
                                            {{ number_format($giaoDich->so_tien, 0) }} ₫</td>
                                        <td class="text-center">
                                            <span
                                                class="badge rounded-pill px-3 py-2
                                            {{ $giaoDich->trang_thai == 'thanh_cong' ? 'bg-success' : 'bg-danger' }}">
                                                <i
                                                    class="bi {{ $giaoDich->trang_thai == 'thanh_cong' ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>
                                                {{ ucfirst(str_replace('_', ' ', $giaoDich->trang_thai)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif


                <hr class="my-5">

                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                    <a href="{{ route('staff.rooms') }}" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="bi bi-arrow-left me-2"></i>Quay Lại
                    </a>
                    <div class="d-flex gap-2">
                        @if (in_array($booking->trang_thai, ['dang_su_dung']) && \Carbon\Carbon::parse($booking->ngay_tra_phong))
                            <a href="{{ route('staff.bookings.checkout.show', $booking->id) }}"
                                class="btn btn-outline-primary btn-lg px-4">
                                <i class="bi bi-receipt me-2"></i>Xem hoá đơn
                            </a>
                        @else
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Staff incident modal (on booking.show) -->
    <div class="modal fade" id="staffIncidentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="staffIncidentForm" method="POST" action="{{ route('bookings.incidents.store', $booking->id) }}">
                @csrf
                <input type="hidden" name="phong_vat_dung_instance_id" id="si_instance_id" value="">
                <input type="hidden" name="phong_id" id="si_phong_id" value="">
                <input type="hidden" name="vat_dung_id" id="si_vat_dung_id" value="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ghi nhận sự cố / tính tiền</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="min-width:620px">
                        <div class="mb-2">
                            <label class="form-label">Loại</label>
                            <select name="mark_instance_status" id="si_status" class="form-select">
                                <option value="damaged">Hỏng</option>
                                <option value="missing">Mất</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Số tiền</label>
                            <input type="number" name="fee" id="si_fee" step="0.01" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="description" id="si_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-danger">Ghi nhận & Tính tiền</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Room Instances Modal (booking.show) -->
    <div class="modal fade" id="roomInstancesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quản lý bản thể phòng <span id="rim_phong_code"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="rim_body">
                        <!-- JS sẽ render danh sách vào đây -->
                        <div class="text-center text-muted py-4">Đang tải...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const staffIncidentModalEl = document.getElementById('staffIncidentModal');
                window.staffIncidentModal = staffIncidentModalEl ? new bootstrap.Modal(staffIncidentModalEl) : null;

                window.allInstances = @json($instances ?? collect());
                window.incidentsByInstance = @json($incidentsByInstance ?? collect());

                function openStaffIncidentModalFromButton(btn) {
                    document.getElementById('si_instance_id').value = btn.dataset.instanceId || '';
                    document.getElementById('si_vat_dung_id').value = btn.dataset.vatDungId || '';
                    document.getElementById('si_fee').value = btn.dataset.defaultFee || 0;
                    document.getElementById('si_description').value = '';
                    document.getElementById('si_phong_id').value = btn.dataset.phongId || '';

                    if (btn.dataset.mark) {
                        const sel = document.getElementById('si_status');
                        if (sel) sel.value = btn.dataset.mark;
                    }

                    if (window.staffIncidentModal) window.staffIncidentModal.show();
                }

                document.querySelectorAll('.btn-open-incident-modal').forEach(btn => {
                    btn.addEventListener('click', function() {
                        openStaffIncidentModalFromButton(this);
                    });
                });

                // Room instances modal
                const rimModalEl = document.getElementById('roomInstancesModal');
                const rimBsModal = rimModalEl ? new bootstrap.Modal(rimModalEl) : null;

                const statusLabels = {
                    present: 'Nguyên vẹn',
                    damaged: 'Hỏng',
                    missing: 'Mất',
                };

                const statusBadges = {
                    present: 'badge bg-success text-white',
                    damaged: 'badge bg-warning text-dark',
                    missing: 'badge bg-danger text-white',
                };

                function normalizeStatusKey(s) {
                    if (!s) return '';
                    const t = String(s).trim().toLowerCase();
                    if (t === 'present' || t.includes('nguyên') || t.includes('nguyen') || t.includes('nguyên vẹn') || t
                        .includes('nguyen ven')) return 'present';
                    if (t === 'damaged' || t.includes('hỏng') || t.includes('hong')) return 'damaged';
                    if (t === 'missing' || t === 'lost' || t.includes('mất') || t.includes('mat')) return (t ===
                        'lost' ? 'lost' : 'missing');
                    if (t === 'archived' || t.includes('lưu trữ') || t.includes('luu tru')) return 'archived';
                    if (['present', 'damaged', 'missing', 'lost', 'archived'].includes(t)) return t;
                    return t;
                }

                function capitalizeWords(s) {
                    return String(s).split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                }

                document.querySelectorAll('.btn-open-room-instances').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const phongId = this.dataset.phongId || '';
                        const phongCode = this.dataset.phongCode || '';
                        document.getElementById('rim_phong_code').innerText = phongCode ? ('#' +
                            phongCode) : '';

                        const container = document.getElementById('rim_body');
                        let list = [];
                        if (window.allInstances && (window.allInstances[phongId] || window.allInstances[
                                String(phongId)])) {
                            list = window.allInstances[phongId] || window.allInstances[String(phongId)];
                        }

                        if (!Array.isArray(list) || list.length === 0) {
                            container.innerHTML =
                                '<div class="text-center text-muted py-4">Chưa có bản thể (hoặc không có dữ liệu).</div>';
                        } else {
                            let html =
                                '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>#</th><th>Vật dụng</th><th>Serial</th><th>Số lượng</th><th>Trạng thái</th><th>Ghi chú</th><th>Hành động</th></tr></thead><tbody>';
                            list.forEach(inst => {
                                const instId = inst.id;
                                const name = (inst.vat_dung && inst.vat_dung.ten) ? inst
                                    .vat_dung.ten : (inst.vatDung?.ten ?? ('#VD' + (inst
                                        .vat_dung_id ?? '')));
                                const serial = inst.serial ?? '-';
                                const qty = inst.quantity ?? 1;
                                const rawStatus = inst.status ?? (inst.status_text ?? '');
                                const note = inst.note ?? '';
                                const defaultFee = (inst.vat_dung && inst.vat_dung.gia) ? inst
                                    .vat_dung.gia : (inst.vatDung?.gia ?? 0);

                                const stKey = normalizeStatusKey(rawStatus);
                                const displayStatus = statusLabels[stKey] ?? (rawStatus ?
                                    capitalizeWords(rawStatus) : '—');
                                const badgeClass = statusBadges[stKey] ??
                                    'badge bg-secondary text-white';
                                const statusHtml =
                                    `<span class="${badgeClass}">${escapeHtml(displayStatus)}</span>`;

                                const instIncArr = (window.incidentsByInstance && (window
                                    .incidentsByInstance[instId] || window
                                    .incidentsByInstance[String(instId)])) ? (window
                                    .incidentsByInstance[instId] || window
                                    .incidentsByInstance[String(instId)]) : [];
                                const instIncident = instIncArr.length ? instIncArr[0] : null;
                                const instIncidentId = instIncident ? instIncident.id : '';

                                const canMark = (stKey === 'present') && !instIncidentId;

                                html += `<tr>
                                    <td>${instId}</td>
                                    <td>${escapeHtml(name)}</td>
                                    <td>${escapeHtml(serial)}</td>
                                    <td>${qty}</td>
                                    <td>${statusHtml}</td>
                                    <td>${escapeHtml(note)}</td>
                                    <td class="text-nowrap">`;

                                if (instIncidentId) {
                                    html +=
                                        `<button class="btn btn-sm btn-outline-success me-1 btn-revert-incident" data-incident-id="${instIncidentId}" title="Đặt lại Nguyên vẹn"><i class="bi bi-arrow-counterclockwise"></i> Nguyên vẹn</button>`;
                                } else {
                                    html +=
                                        `<button class="btn btn-sm btn-outline-secondary me-1" disabled title="Chưa có sự cố">Nguyên vẹn</button>`;
                                }

                                if (canMark) {
                                    html +=
                                        `<button class="btn btn-sm btn-outline-warning me-1 btn-open-incident-modal" data-instance-id="${instId}" data-vat-dung-id="${inst.vat_dung_id ?? (inst.vatDung?.id ?? '')}" data-default-fee="${defaultFee}" data-phong-id="${phongId}" data-mark="damaged"><i class="bi bi-exclamation-triangle"></i> Hỏng</button>`;
                                    html +=
                                        `<button class="btn btn-sm btn-outline-danger me-1 btn-open-incident-modal" data-instance-id="${instId}" data-vat-dung-id="${inst.vat_dung_id ?? (inst.vatDung?.id ?? '')}" data-default-fee="${defaultFee}" data-phong-id="${phongId}" data-mark="missing"><i class="bi bi-x-circle"></i> Mất</button>`;
                                } else {
                                    const title = instIncidentId ?
                                        'Đã có sự cố cho bản thể này trong booking' :
                                        'Bản thể không ở trạng thái Nguyên vẹn';
                                    html +=
                                        `<button class="btn btn-sm btn-outline-secondary me-1" disabled title="${escapeHtml(title)}"><i class="bi bi-exclamation-triangle"></i> Hỏng</button>`;
                                    html +=
                                        `<button class="btn btn-sm btn-outline-secondary me-1" disabled title="${escapeHtml(title)}"><i class="bi bi-x-circle"></i> Mất</button>`;
                                }

                                @if (auth()->check() && ((auth()->user()->is_admin ?? false) || (auth()->user()->role ?? '') === 'admin'))
                                    html +=
                                        ` <a class="btn btn-sm btn-outline-secondary" href="{{ url('/admin/phong') }}/${phongId}/vat-dung-instances" target="_blank"><i class="bi bi-pencil-square"></i> Quản lý</a>`;
                                @endif

                                html += `</td></tr>`;
                            });
                            html += '</tbody></table></div>';
                            container.innerHTML = html;

                            container.querySelectorAll('.btn-open-incident-modal').forEach(b => {
                                b.addEventListener('click', function() {
                                    if (rimBsModal) rimBsModal.hide();
                                    openStaffIncidentModalFromButton(this);
                                });
                            });

                            if (window.staffIncidentModal) {
                                const staffEl = document.getElementById('staffIncidentModal');
                                staffEl.addEventListener('hidden.bs.modal', function() {
                                    if (rimBsModal) {
                                        setTimeout(() => rimBsModal.show(), 150);
                                    }
                                }, {
                                    once: false
                                });
                            }

                            container.querySelectorAll('.btn-revert-incident').forEach(b => {
                                b.addEventListener('click', function() {
                                    const incidentId = this.dataset.incidentId;
                                    if (!incidentId) return;
                                    if (!confirm(
                                            'Xác nhận đặt lại bản thể về "Nguyên vẹn" và xóa sự cố liên quan?'
                                        )) return;

                                    const urlTemplate =
                                        "{{ route('bookings.incidents.destroy', ['booking' => $booking->id, 'incident' => '__ID__']) }}";
                                    const url = urlTemplate.replace('__ID__',
                                        incidentId);

                                    const f = document.createElement('form');
                                    f.method = 'POST';
                                    f.action = url;
                                    f.style.display = 'none';
                                    const token = document.createElement('input');
                                    token.name = '_token';
                                    token.value = "{{ csrf_token() }}";
                                    f.appendChild(token);
                                    const method = document.createElement('input');
                                    method.name = '_method';
                                    method.value = 'DELETE';
                                    f.appendChild(method);
                                    document.body.appendChild(f);
                                    f.submit();
                                });
                            });
                        }

                        if (rimBsModal) rimBsModal.show();
                    });
                });

                function escapeHtml(str) {
                    if (str === null || str === undefined) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }
            });
        </script>
    @endpush

    <style>
        .text-gradient-primary {
            -webkit-background-clip: text;
            background-clip: text;
        }

        .bg-indigo {
            background-color: #5f3dc4 !important;
        }
    </style>
@endsection
