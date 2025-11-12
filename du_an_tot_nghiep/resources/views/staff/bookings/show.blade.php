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
                                            $status = $booking->trang_thai;
                                            $statusClasses = [
                                                'da_gan_phong' => 'bg-success',
                                                'dang_cho' => 'bg-warning text-dark',
                                                'dang_cho_xac_nhan' => 'bg-info',
                                                'da_huy' => 'bg-secondary',
                                                'hoan_thanh' => 'bg-primary',
                                                'dang_o' => 'bg-indigo text-white',
                                            ];
                                            $statusIcons = [
                                                'da_gan_phong' => 'bi-check-circle',
                                                'dang_cho' => 'bi-hourglass-split',
                                                'dang_cho_xac_nhan' => 'bi-clock-history',
                                                'da_huy' => 'bi-x-circle',
                                                'hoan_thanh' => 'bi-check2-all',
                                                'dang_o' => 'bi-house-door',
                                            ];
                                        @endphp

                                        <span
                                            class="badge rounded-pill px-3 py-2 fs-7 {{ $statusClasses[$status] ?? 'bg-dark' }}">
                                            <i class="bi {{ $statusIcons[$status] ?? 'bi-question-circle' }} me-1"></i>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
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
                    // Lấy list đồ ăn active (loại DO_AN) để staff chọn trong modal
                    $availableFoods = \App\Models\VatDung::where('active', 1)
                        ->where('loai', \App\Models\VatDung::LOAI_DO_AN ?? 'do_an')
                        ->orderBy('ten')
                        ->get();
                @endphp

                @forelse ($booking->datPhongItems as $item)
                    <div class="card border-0 shadow-sm mb-3 rounded-3 overflow-hidden">
                        <div class="card-body py-3 px-4">
                            <div class="row align-items-center text-sm">
                                <div class="col-md-3 d-flex align-items-center">
                                    <strong class="text-primary">#{{ $item->phong?->ma_phong ?? 'Chưa gán' }}</strong>
                                    {{-- Nút thêm đồ ăn cho phòng này --}}
                                    <button type="button" class="btn btn-sm btn-outline-primary ms-3"
                                        data-bs-toggle="modal" data-bs-target="#addFoodModal"
                                        data-phong-id="{{ $item->phong?->id }}"
                                        data-phong-code="{{ $item->phong?->ma_phong }}">
                                        <i class="bi bi-plus-lg me-1"></i> Dịch vụ gọi thêm
                                    </button>
                                </div>
                                <div class="col-md-3">
                                    <i class="bi bi-building me-1"></i> {{ $item->loaiPhong?->ten ?? 'N/A' }}
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-light text-dark border">{{ $item->so_luong ?? 1 }} phòng</span>
                                </div>
                                <div class="col-md-4 text-end">
                                    <strong class="text-success">{{ number_format($item->gia_tren_dem, 0) }}
                                        ₫</strong>/đêm
                                </div>
                            </div>
                        </div>
                    </div>
                    
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 mb-3 d-block"></i>
                        <p>Chưa có phòng nào được gán.</p>
                    </div>
                @endforelse

                <!-- Modal: Thêm đồ ăn (gọi thêm) -->
                <div class="modal fade" id="addFoodModal" tabindex="-1" aria-labelledby="addFoodModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form id="addFoodForm" method="POST" action="{{ route('phong.consumptions.store') }}">
                            @csrf
                            <input type="hidden" name="dat_phong_id" value="{{ $booking->id }}">
                            <input type="hidden" name="phong_id" id="modal_phong_id" value="">
                            <input type="hidden" name="bill_now" value="1">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addFoodModalLabel">Thêm dịch vụ cho phòng <span
                                            id="modal_phong_code"></span></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Dịch vụ</label>
                                        <select name="vat_dung_id" id="modal_vat_dung_id" class="form-select" required>
                                            <option value="">— Chọn dịch vụ —</option>
                                            @foreach ($availableFoods as $fd)
                                                <option value="{{ $fd->id }}" data-price="{{ $fd->gia ?? 0 }}">
                                                    {{ $fd->ten }} ({{ number_format($fd->gia ?? 0, 0, ',', '.') }} đ)
                                                </option>
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
                        // Khi modal hiện, lấy dữ liệu phòng từ data-* và gán vào form
                        var addFoodModalEl = document.getElementById('addFoodModal');
                        addFoodModalEl.addEventListener('show.bs.modal', function(event) {
                            var button = event.relatedTarget;
                            var phongId = button.getAttribute('data-phong-id') || '';
                            var phongCode = button.getAttribute('data-phong-code') || '';
                            document.getElementById('modal_phong_id').value = phongId;
                            document.getElementById('modal_phong_code').innerText = phongCode || '(chưa gán)';
                            // reset
                            document.getElementById('modal_vat_dung_id').value = '';
                            document.getElementById('modal_quantity').value = 1;
                            document.getElementById('modal_unit_price').value = 0;
                        });

                        // khi chọn món, tự điền unit_price từ data-price
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
                    // $consumptions và $instances nên được truyền từ controller như collection grouped by phong_id
                    $consByRoom = $consumptions ?? collect();
                    $instByRoom = $instances ?? collect();

                    // helper: get group safely (handles null keys)
                    $getGroup = function($coll, $key) {
                        if (! $coll instanceof \Illuminate\Support\Collection) return collect();
                        if ($coll->has($key)) return $coll->get($key);
                        // try string/empty key
                        if ($key === null && $coll->has('')) return $coll->get('');
                        if ($key === 0 && $coll->has('0')) return $coll->get('0');
                        return collect();
                    };

                    // consumptions not linked to any room (phong_id null)
                    $unassignedCons = collect();
                    if ($consByRoom instanceof \Illuminate\Support\Collection) {
                        // try keys null / '' / 0
                        if ($consByRoom->has(null)) $unassignedCons = $consByRoom->get(null);
                        elseif ($consByRoom->has('')) $unassignedCons = $consByRoom->get('');
                        elseif ($consByRoom->has(0)) $unassignedCons = $consByRoom->get(0);
                    }

                    // instances not linked to specific room (if any)
                    $unassignedInst = collect();
                    if ($instByRoom instanceof \Illuminate\Support\Collection) {
                        if ($instByRoom->has(null)) $unassignedInst = $instByRoom->get(null);
                        elseif ($instByRoom->has('')) $unassignedInst = $instByRoom->get('');
                        elseif ($instByRoom->has(0)) $unassignedInst = $instByRoom->get(0);
                    }
                @endphp

                <hr class="my-5">

                <h6 class="text-primary fw-bold mb-4"><i class="bi bi-basket-fill me-2"></i>Đồ Ăn Gọi Thêm & Sự Cố</h6>

                @php
                    $anyShown = false;
                @endphp

                {{-- Hiển thị theo từng phòng đã gán --}}
                @foreach ($booking->datPhongItems as $item)
                    @php
                        $phId = $item->phong?->id;
                        $roomCons = $getGroup($consByRoom, $phId) ?? collect();
                        $roomInst = $getGroup($instByRoom, $phId) ?? collect();
                    @endphp

                    @if ($roomCons->isNotEmpty() || $roomInst->isNotEmpty())
                        @php $anyShown = true; @endphp
                        <div class="card border-0 shadow-sm mb-3 rounded-3 overflow-hidden">
                            <div class="card-body py-3 px-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong class="text-primary">#{{ $item->phong?->ma_phong ?? 'Chưa gán' }}</strong>
                                        <div class="small text-muted">{{ $item->loaiPhong?->ten ?? 'N/A' }}</div>
                                    </div>
                                    <div class="text-end small">
                                        <div>{{ $item->so_luong ?? 1 }} phòng</div>
                                        <div class="fw-semibold text-success">{{ number_format($item->gia_tren_dem, 0) }} ₫/đêm</div>
                                    </div>
                                </div>

                                {{-- Consumptions --}}
                                @if ($roomCons->isNotEmpty())
                                    <div class="mt-3">
                                        <div class="small text-muted mb-2">Đồ ăn / Dịch vụ đã gọi thêm</div>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($roomCons as $c)
                                                <li class="small mb-1">
                                                    <strong>{{ $c->quantity }} × {{ $c->vatDung?->ten ?? ('#VD'.$c->vat_dung_id) }}</strong>
                                                    — <span class="fw-semibold">{{ number_format(($c->unit_price * $c->quantity), 0) }} ₫</span>
                                                    @if ($c->billed_at)
                                                        <span class="badge bg-success ms-2 small">Đã tính</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark ms-2 small">Chưa tính</span>
                                                    @endif
                                                    <div class="text-muted small mt-1">
                                                        @if($c->consumed_at)
                                                            <span title="Thời gian tiêu thụ">⏱ {{ \Carbon\Carbon::parse($c->consumed_at)->format('d/m H:i') }}</span>
                                                            &nbsp;·&nbsp;
                                                        @endif
                                                        <span title="Người tạo">Người đánh dấu: {{ $c->creator?->name ?? ($c->created_by ? 'UID#'.$c->created_by : '—') }}</span>
                                                        @if($c->note) &nbsp;·&nbsp; Ghi chú: {{ Str::limit($c->note, 80) }} @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                {{-- Instances (sự cố / hỏng hóc) --}}
                                @if ($roomInst->isNotEmpty())
                                    <div class="mt-3">
                                        <div class="small text-muted mb-2">Sự cố / Vật dụng hỏng</div>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($roomInst as $ins)
                                                <li class="small mb-1 text-danger">
                                                    <strong>{{ $ins->vatDung?->ten ?? ('#VD'.$ins->vat_dung_id) }}</strong>
                                                    — Trạng thái: <span class="fw-semibold text-capitalize">{{ $ins->status }}</span>
                                                    <div class="text-muted small mt-1">
                                                        @if($ins->reported_at)
                                                            <span>⏱ {{ \Carbon\Carbon::parse($ins->reported_at)->format('d/m H:i') }}</span> &nbsp;·&nbsp;
                                                        @endif
                                                        @if($ins->note) Ghi chú: {{ Str::limit($ins->note, 80) }} @endif
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

                {{-- Hiển thị items không gắn phòng (nếu có) --}}
                @if ($unassignedCons->isNotEmpty() || $unassignedInst->isNotEmpty())
                    @php $anyShown = true; @endphp
                    <div class="card border-0 shadow-sm mb-3 rounded-3 overflow-hidden">
                        <div class="card-body py-3 px-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong class="text-primary">Không gán phòng</strong>
                                    <div class="small text-muted">Các mục không liên kết trực tiếp với phòng cụ thể</div>
                                </div>
                                <div class="small text-muted">Booking #{{ $booking->id }}</div>
                            </div>

                            @if ($unassignedCons->isNotEmpty())
                                <div class="mt-2">
                                    <div class="small text-muted mb-2">Đồ ăn / Dịch vụ</div>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($unassignedCons as $c)
                                            <li class="small mb-1">
                                                <strong>{{ $c->quantity }} × {{ $c->vatDung?->ten ?? ('#VD'.$c->vat_dung_id) }}</strong>
                                                — <span class="fw-semibold">{{ number_format(($c->unit_price * $c->quantity), 0) }} ₫</span>
                                                @if ($c->billed_at)
                                                    <span class="badge bg-success ms-2 small">Đã tính</span>
                                                @else
                                                    <span class="badge bg-warning text-dark ms-2 small">Chưa tính</span>
                                                @endif
                                                <div class="text-muted small mt-1">
                                                    <span>Ng bởi: {{ $c->creator?->name ?? ($c->created_by ? 'UID#'.$c->created_by : '—') }}</span>
                                                    @if($c->note) &nbsp;·&nbsp; Ghi chú: {{ Str::limit($c->note, 80) }} @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($unassignedInst->isNotEmpty())
                                <div class="mt-2">
                                    <div class="small text-muted mb-2">Sự cố / Vật dụng hỏng</div>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($unassignedInst as $ins)
                                            <li class="small mb-1 text-danger">
                                                <strong>{{ $ins->vatDung?->ten ?? ('#VD'.$ins->vat_dung_id) }}</strong>
                                                — Trạng thái: <span class="fw-semibold text-capitalize">{{ $ins->status }}</span>
                                                <div class="text-muted small mt-1">
                                                    @if($ins->reported_at) ⏱ {{ \Carbon\Carbon::parse($ins->reported_at)->format('d/m H:i') }} @endif
                                                    @if($ins->note) &nbsp;·&nbsp; Ghi chú: {{ Str::limit($ins->note, 80) }} @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if (! $anyShown)
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        <p class="mb-0">Chưa có đồ ăn gọi thêm hoặc sự cố nào được ghi nhận cho booking này.</p>
                    </div>
                @endif

                <hr class="my-5">
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

                    @if (in_array($booking->trang_thai, ['da_gan_phong', 'dang_o']) &&
                            \Carbon\Carbon::parse($booking->ngay_tra_phong)->isToday())
                        <form action="{{ route('staff.checkout.process') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <button type="submit" class="btn btn-warning btn-lg px-5 shadow-sm"
                                onclick="return confirm('⚠️ Xác nhận check-out cho booking #{{ $booking->ma_tham_chieu }}?\n\nKhách sẽ được trả phòng ngay lập tức.')">
                                <i class="bi bi-box-arrow-right me-2"></i>Check-out Ngay
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .text-gradient-primary {
            background: linear-gradient(90deg, #0d6efd, #0a58ca);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .bg-indigo {
            background-color: #5f3dc4 !important;
        }

    </style>
@endsection
