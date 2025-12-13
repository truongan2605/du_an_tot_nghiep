@extends('layouts.app')

@section('title', 'Danh sách phòng')

@section('content')
    @php
        // Flag do RoomController truyền sang (xem code đã chỉnh trước đó)
        $weekendSearch = isset($hasWeekend) && $hasWeekend;

        // Giữ tham số tìm kiếm để đi sang trang chi tiết và các link "xem thêm"
        // NOTE: date_range sẽ được submit theo chuẩn Y-m-d to Y-m-d (JS phía dưới)
        $searchParams = http_build_query(request()->only([
            'date_range',
            'adults',
            'children',
            'rooms_count',
        ]));
    @endphp

    <!-- =======================
            Banner START -->
    <section class="position-relative overflow-hidden rounded-4 mt-4 mx-auto"
        style="height: 360px; width: 90%; max-width: 1200px;">
        <!-- Background Image -->
        <img src="{{ asset('template/stackbros/assets/images/bg/07.jpg') }}" alt="banner"
            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover rounded-4"
            style="filter: brightness(70%); object-position: center;">

        <!-- Overlay -->
        <div class="position-absolute top-0 start-0 w-100 h-100 rounded-4" style="background: rgba(0, 0, 0, 0.4);"></div>

        <!-- Text -->
        <div
            class="position-relative z-2 text-center text-white d-flex flex-column justify-content-center align-items-center h-100">
            <h1 class="display-5 fw-bold mb-3" style="color: #ddd">Danh sách phòng khả dụng</h1>
            <p class="lead mb-0" style="max-width: 600px;">
                Khám phá các phòng đang hoạt động trong hệ thống của chúng tôi
            </p>
        </div>
    </section>
    <!-- =======================
            Banner END -->

    <section class="pt-4 pb-5">
        <div class="container">
            <div class="row g-4">
                {{-- ==== SIDEBAR LỌC ==== --}}
                <aside class="col-lg-3">
                    <form method="GET" action="{{ route('list-room.index') }}" class="card border-0 shadow-sm">
                        <div class="filter-box sidebar-filter">
                            {{-- Room Type --}}
                            <h6 class="fw-bold mb-3">Loại phòng</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <input type="radio" name="loai_phong_id" id="type_all" value=""
                                        {{ request('loai_phong_id') == '' ? 'checked' : '' }}>
                                    <label for="type_all">Tất cả loại phòng</label>
                                </li>

                                @foreach ($loaiPhongs as $loai)
                                    <li>
                                        <input type="radio" name="loai_phong_id" id="type_{{ $loai->id }}"
                                            value="{{ $loai->id }}"
                                            {{ request('loai_phong_id') == $loai->id ? 'checked' : '' }}>
                                        <label for="type_{{ $loai->id }}">{{ $loai->ten }}</label>
                                    </li>
                                @endforeach
                            </ul>

                            {{-- Check in-out --}}
                            <h6>Nhận phòng - Trả phòng</h6>

                            {{-- UI input (hiển thị) --}}
                            <input type="text"
                                   id="date_range_ui"
                                   class="form-control flatpickr mb-3"
                                   data-mode="range"
                                   placeholder="Chọn ngày"
                                   value="{{ request('date_range') }}">

                            {{-- Hidden input (submit lên server theo chuẩn Y-m-d to Y-m-d) --}}
                            <input type="hidden"
                                   name="date_range"
                                   id="date_range"
                                   value="{{ request('date_range') }}">

                            {{-- Guests (giống giao diện home) --}}
                            <h6>Khách</h6>
                            <div class="mb-3 position-relative">
                                <div id="guestSelectorSidebar" class="guest-selector-box w-100">
                                    <i class="bi bi-people me-2"></i>
                                    <span id="guestSummarySidebar">
                                        {{ request('adults', 1) }} Người lớn,
                                        {{ request('children', 0) }} Trẻ em
                                    </span>
                                    <i class="bi bi-chevron-down ms-auto"></i>
                                </div>
                                <small class="text-muted small d-block mt-1">Mỗi phòng tối đa 2 trẻ em.</small>

                                <div id="guestPopupSidebar" class="guest-popup shadow">
                                    <div class="guest-row">
                                        <div class="guest-info">
                                            <i class="bi bi-person icon"></i>
                                            <div>
                                                <div class="fw-bold">Người lớn</div>
                                                <small class="text-muted">Từ 13 tuổi</small>
                                            </div>
                                        </div>
                                        <div class="guest-control">
                                            <button type="button" class="btn-minus" data-target="adultsSidebar">−</button>
                                            <span id="adultsCountSidebar">{{ request('adults', 1) }}</span>
                                            <button type="button" class="btn-plus" data-target="adultsSidebar">+</button>
                                        </div>
                                    </div>

                                    <div class="guest-row">
                                        <div class="guest-info">
                                            <i class="bi bi-emoji-smile icon"></i>
                                            <div>
                                                <div class="fw-bold">Trẻ em</div>
                                                <small class="text-muted">Dưới 13 tuổi</small>
                                            </div>
                                        </div>
                                        <div class="guest-control">
                                            <button type="button" class="btn-minus" data-target="childrenSidebar">−</button>
                                            <span id="childrenCountSidebar">{{ request('children', 0) }}</span>
                                            <button type="button" class="btn-plus" data-target="childrenSidebar">+</button>
                                        </div>
                                    </div>

                                    <button type="button" id="guestPopupSidebarDone"
                                            class="btn btn-primary w-100 mt-3 rounded-pill">
                                        Xong
                                    </button>
                                </div>

                                {{-- Hidden fields --}}
                                <input type="hidden" name="adults" id="adultsInputSidebar"
                                       value="{{ request('adults', 1) }}">
                                <input type="hidden" name="children" id="childrenInputSidebar"
                                       value="{{ request('children', 0) }}">
                            </div>

                            <div class="mb-2">
                                <label class="form-label small mb-1">Số phòng</label>
                                <input type="number" name="rooms_count" id="filter_rooms_count"
                                       class="form-control form-control-sm" min="1"
                                       value="{{ request('rooms_count', 1) }}">
                            </div>

                            {{-- Giá --}}
                            <h6>Giá (VNĐ)</h6>
                            @if ($weekendSearch)
                                <small class="text-muted d-block mb-2">
                                    Khoảng giá đã bao gồm phụ thu cuối tuần <strong>+10%</strong>.
                                </small>
                            @else
                                <small class="text-muted d-block mb-2">
                                    Khoảng giá cho ngày thường (không phụ thu cuối tuần).
                                </small>
                            @endif

                            <div class="mb-3">
                                <div id="price-slider"></div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span id="min-price"
                                        class="small fw-semibold">{{ number_format($giaMin, 0, ',', '.') }}đ</span>
                                    <span id="max-price"
                                        class="small fw-semibold">{{ number_format($giaMax, 0, ',', '.') }}đ</span>
                                </div>
                                <input type="hidden" id="gia_min" name="gia_min"
                                    value="{{ (int) request('gia_min', $giaMin) }}">
                                <input type="hidden" id="gia_max" name="gia_max"
                                    value="{{ (int) request('gia_max', $giaMax) }}">
                            </div>

                            <div class="filter-divider"></div>

                            {{-- Rating Star --}}
                            <h6 class="fw-bold mb-3">Đánh giá sao</h6>
                            <ul class="list-unstyled mb-3">
                                @for ($i = 5; $i >= 1; $i--)
                                    <li class="mb-1">
                                        <input type="radio" name="diem" id="star{{ $i }}"
                                            value="{{ $i }}" {{ request('diem') == $i ? 'checked' : '' }}>
                                        <label for="star{{ $i }}">
                                            @for ($j = 1; $j <= $i; $j++)
                                                <i class="bi bi-star-fill text-warning"></i>
                                            @endfor
                                            @for ($j = $i + 1; $j <= 5; $j++)
                                                <i class="bi bi-star text-muted"></i>
                                            @endfor
                                        </label>
                                    </li>
                                @endfor
                                <li>
                                    <input type="radio" name="diem" id="all_rating" value=""
                                        {{ request('diem') == '' ? 'checked' : '' }}>
                                    <label for="all_rating">Tất cả đánh giá</label>
                                </li>
                            </ul>

                            {{-- Amenities --}}
                            <h6>Dịch vụ</h6>
                            <ul class="list-unstyled">
                                @php
                                    $selectedTienNghi = (array) request('tien_nghi', []);
                                @endphp

                                @foreach ($tienNghis as $tienNghi)
                                    <li>
                                        <input type="checkbox" name="tien_nghi[]" id="amenity{{ $tienNghi->id }}"
                                            value="{{ $tienNghi->id }}"
                                            {{ in_array($tienNghi->id, $selectedTienNghi) ? 'checked' : '' }}>
                                        <label for="amenity{{ $tienNghi->id }}">{{ $tienNghi->ten }}</label>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-3">
                                <button type="submit" class="filter-submit-btn">Tìm kiếm</button>
                            </div>
                        </div>

                    </form>
                </aside>

                {{-- ==== DANH SÁCH PHÒNG (THEO LOẠI PHÒNG) ==== --}}
                <div class="col-lg-9">
                    <div class="row g-4">
                        @forelse($phongs as $phong)
                            @php
                                $loaiPhong = $phong->loaiPhong;

                                // Giá hiển thị: nếu đang tìm trong khoảng có cuối tuần → cộng 10%
                                $displayPrice = (float) $phong->gia_cuoi_cung;
                                if ($weekendSearch) {
                                    $displayPrice = ceil($displayPrice * 1.1);
                                }

                                // Sức chứa cơ bản của loại phòng (tùy vào cấu trúc DB, dùng field nào có)
                                $baseCapacity =
                                    $phong->suc_chua_toi_da
                                        ?? $phong->so_nguoi_toi_da
                                        ?? $phong->so_nguoi
                                        ?? ($loaiPhong->so_nguoi_toi_da ?? $loaiPhong->so_nguoi ?? 0);

                                // Logic dự án: mỗi loại phòng tối đa thêm 2 người
                                $maxCapacityAdults = $baseCapacity ? $baseCapacity + 2 : null;
                            @endphp

                            <div class="col-12">
                                <div
                                    class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 room-card position-relative hover-shadow transition-all">

                                    {{-- ========== ẢNH PHÒNG / CAROUSEL ========== --}}
                                    <div class="row g-0 align-items-center">
                                        <div class="col-md-5 position-relative">

                                            @if ($phong->images->count() > 1)
                                                <div id="carouselRoom{{ $phong->id }}" class="carousel slide"
                                                    data-bs-ride="carousel" data-bs-interval="3000">
                                                    <div class="carousel-inner room-carousel-inner rounded-start">
                                                        @foreach ($phong->images as $key => $img)
                                                            <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                                                                <img src="{{ asset('storage/' . $img->image_path) }}"
                                                                    class="d-block w-100 h-100 object-fit-cover rounded-start-4"
                                                                    alt="Image {{ $key + 1 }}">
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <button class="carousel-control-prev" type="button"
                                                        data-bs-target="#carouselRoom{{ $phong->id }}"
                                                        data-bs-slide="prev">
                                                        <span
                                                            class="carousel-control-prev-icon bg-dark rounded-circle p-2"></span>
                                                    </button>
                                                    <button class="carousel-control-next" type="button"
                                                        data-bs-target="#carouselRoom{{ $phong->id }}"
                                                        data-bs-slide="next">
                                                        <span
                                                            class="carousel-control-next-icon bg-dark rounded-circle p-2"></span>
                                                    </button>
                                                </div>
                                            @else
                                                <img src="{{ asset('storage/' . ($phong->images->first()->image_path ?? 'template/stackbros/assets/images/default-room.jpg')) }}"
                                                    class="w-100 h-100 object-fit-cover rounded-start-4"
                                                    alt="{{ $phong->name }}">
                                            @endif

                                            {{-- Badge Giảm giá nếu có --}}
                                            @if (isset($phong->khuyen_mai) && $phong->khuyen_mai > 0)
                                                <span
                                                    class="badge bg-danger position-absolute top-0 start-0 m-3 px-3 py-2 fs-6 shadow-sm">
                                                    -{{ $phong->khuyen_mai }}%
                                                </span>
                                            @endif
                                        </div>

                                        {{-- ========== THÔNG TIN LOẠI PHÒNG ========== --}}
                                        <div class="col-md-7">
                                            <div class="card-body py-4 px-4">
                                                {{-- Đánh giá sao --}}
                                                <div class="d-flex align-items-center mb-2">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <i
                                                            class="bi bi-star{{ $i <= ($phong->so_sao ?? 4) ? '-fill text-warning' : '' }} me-1"></i>
                                                    @endfor
                                                </div>

                                                {{-- Tên loại phòng --}}
                                                <h5 class="fw-bold mb-1">
                                                    {{ $loaiPhong->ten ?? ($phong->name ?? $phong->ma_phong) }}
                                                </h5>

                                                {{-- Mô tả hoặc vị trí --}}
                                                <p class="text-muted mb-2">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    {{ $phong->mo_ta ?? 'Mô tả đang cập nhật' }}
                                                </p>

                                                {{-- Số phòng trống / tổng số phòng --}}
                                                <p class="text-muted mb-2">
                                                    @if (request('date_range') && !is_null($phong->so_phong_trong))
                                                        Còn {{ $phong->so_phong_trong }} /
                                                        {{ $phong->so_luong_phong_cung_loai ?? 0 }} phòng trống cho ngày đã
                                                        chọn
                                                    @else
                                                        Có {{ $phong->so_luong_phong_cung_loai ?? 0 }} phòng trong hệ thống
                                                    @endif
                                                </p>

                                                {{-- Sức chứa tối đa --}}
                                                @if ($maxCapacityAdults)
                                                    <p class="text-muted mb-2">
                                                        <i class="bi bi-people me-1"></i>
                                                        Sức chứa tối đa: {{ $maxCapacityAdults }} người
                                                        (người lớn + trẻ em từ 7 tuổi trở lên)
                                                        và thêm tối đa 2 trẻ em dưới 7 tuổi.
                                                    </p>
                                                @endif

                                                {{-- Tiện nghi --}}
                                                <div class="small text-muted mb-2">
                                                    @if ($phong->tienNghis && $phong->tienNghis->count())
                                                        @foreach ($phong->tienNghis->take(3) as $tiennghi)
                                                            <span class="me-2">
                                                                <i class="bi bi-check-circle text-success me-1"></i>
                                                                {{ $tiennghi->ten }}
                                                            </span>
                                                        @endforeach

                                                        @if ($phong->tienNghis->count() > 3)
                                                            <a href="{{ route('rooms.show', $phong->id) }}@if($searchParams)?{{ $searchParams }}@endif"
                                                               class="text-decoration-none">
                                                                Xem thêm+
                                                            </a>
                                                        @endif
                                                    @else
                                                        <span>Chưa có dịch vụ</span>
                                                    @endif
                                                </div>

                                                {{-- Giá & Nút chọn phòng --}}
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h4 class="fw-bold text-primary mb-0">
                                                            {{ number_format($phong->gia_cuoi_cung, 0, ',', '.') }} VNĐ
                                                            <span class="small fw-normal text-muted">/Đêm</span>
                                                        </h4>

                                                        @if ($weekendSearch)
                                                            <div class="small text-muted">
                                                                Đã bao gồm phụ thu cuối tuần +10%.
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="d-flex gap-2">
                                                        {{-- Compare Button --}}
                                                        <button type="button"
                                                                class="btn btn-outline-primary rounded-pill px-3 py-2 compare-btn"
                                                                data-room-id="{{ $phong->id }}"
                                                                data-room-name="{{ $loaiPhong->ten ?? $phong->name }}"
                                                                data-room-price="{{ $phong->gia_cuoi_cung }}"
                                                                data-room-image="{{ asset('storage/' . ($phong->images->first()->image_path ?? 'template/stackbros/assets/images/default-room.jpg')) }}"
                                                                title="So sánh phòng">
                                                            <i class="bi bi-plus-circle me-1"></i>
                                                            <span class="compare-text">So sánh</span>
                                                        </button>

                                                        {{-- Chọn phòng (giữ lại tham số tìm kiếm) --}}
                                                        <a href="{{ route('rooms.show', $phong->id) }}@if($searchParams)?{{ $searchParams }}@endif"
                                                           class="btn btn-dark rounded-pill px-4 py-2">
                                                            Chọn phòng
                                                        </a>

                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <p class="mt-3 mb-0">
                                    <i class="fa-regular fa-eye-slash"></i>
                                    Không tìm thấy phòng phù hợp.
                                </p>
                            </div>
                        @endforelse

                    </div>

                    {{-- Phân trang (giữ query) --}}
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $phongs->appends(request()->query())->links() }}
                    </div>

                </div>
            </div>
        </div>
    </section>

    {{-- Sticky Compare Bar --}}
    <div id="compareBar" class="compare-bar" style="display: none;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-check2-square fs-4"></i>
                    <div>
                        <h6 class="mb-0">So sánh phòng</h6>
                        <small class="text-muted"><span id="compareCount">0</span> phòng đã chọn (Tối đa 4)</small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-light btn-sm" id="clearCompare">
                        <i class="bi bi-trash me-1"></i> Xóa tất cả
                    </button>
                    <a href="{{ route('rooms.compare') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-right-circle me-1"></i> So sánh ngay
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css" rel="stylesheet">

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // =========================
                // Flatpickr (date_range)
                // - UI: hiển thị d/m
                // - Submit: hidden date_range theo "Y-m-d to Y-m-d"
                // =========================
                (function initDateRange() {
                    const ui = document.getElementById('date_range_ui');
                    const hidden = document.getElementById('date_range');
                    if (!ui || !hidden || typeof flatpickr === 'undefined') return;

                    // Nếu template auto-init rồi thì destroy để tránh trùng config
                    if (ui._flatpickr) ui._flatpickr.destroy();

                    // Chuẩn hoá value hiện tại (có thể đang là "06 Jan to 07 Jan")
                    // - Nếu đã là Y-m-d thì dùng trực tiếp
                    // - Nếu không đúng, để rỗng (người dùng chọn lại)
                    const isYmd = (s) => /^\d{4}-\d{2}-\d{2}$/.test((s || '').trim());
                    const parts = (hidden.value || '').split(' to ').map(s => s.trim());
                    const defaultDate = (parts.length === 2 && isYmd(parts[0]) && isYmd(parts[1])) ? parts : null;

                    const fp = flatpickr(ui, {
                        mode: "range",
                        minDate: "today",
                        dateFormat: "Y-m-d",
                        defaultDate: defaultDate,
                        onChange: function(selectedDates, dateStr) {
                            // dateStr sẽ là "YYYY-MM-DD to YYYY-MM-DD"
                            hidden.value = dateStr || '';
                        },
                        // hiển thị đẹp (nhưng không ảnh hưởng submit)
                        altInput: true,
                        altFormat: "d M",
                        altInputClass: ui.className,
                    });

                    // Nếu defaultDate hợp lệ thì sync hidden ngay
                    if (defaultDate) {
                        hidden.value = defaultDate[0] + ' to ' + defaultDate[1];
                    } else {
                        // Nếu hidden đang format cũ thì clear để tránh submit sai
                        if (hidden.value && !isYmd(parts[0] || '') ) {
                            hidden.value = '';
                        }
                    }
                })();

                // =========================
                // Price slider
                // =========================
                var priceSlider = document.getElementById('price-slider');
                var minInput = document.getElementById('gia_min');
                var maxInput = document.getElementById('gia_max');
                var minLabel = document.getElementById('min-price');
                var maxLabel = document.getElementById('max-price');

                if (priceSlider && minInput && maxInput && minLabel && maxLabel) {
                    var minVal = parseInt(minInput.value || '{{ $giaMin }}', 10);
                    var maxVal = parseInt(maxInput.value || '{{ $giaMax }}', 10);

                    noUiSlider.create(priceSlider, {
                        start: [minVal, maxVal],
                        connect: true,
                        range: {
                            'min': {{ (int) $giaMin }},
                            'max': {{ (int) $giaMax }}
                        },
                        step: 50000,
                        format: {
                            to: value => Math.round(value),
                            from: value => Math.round(value)
                        }
                    });

                    priceSlider.noUiSlider.on('update', function(values) {
                        var vMin = values[0];
                        var vMax = values[1];

                        minInput.value = vMin;
                        maxInput.value = vMax;

                        minLabel.textContent = new Intl.NumberFormat('vi-VN').format(vMin) + 'đ';
                        maxLabel.textContent = new Intl.NumberFormat('vi-VN').format(vMax) + 'đ';
                    });
                }

                // =========================
                // Guest popup logic (sidebar)
                // =========================
                const popup = document.getElementById("guestPopupSidebar");
                const btn = document.getElementById("guestSelectorSidebar");

                const adults = document.getElementById("adultsCountSidebar");
                const children = document.getElementById("childrenCountSidebar");

                const inputAdults = document.getElementById("adultsInputSidebar");
                const inputChildren = document.getElementById("childrenInputSidebar");

                const summary = document.getElementById("guestSummarySidebar");
                const roomsInput = document.getElementById("filter_rooms_count");

                function getMaxChildren() {
                    let rooms = parseInt(roomsInput ? roomsInput.value : 1, 10);
                    if (isNaN(rooms) || rooms < 1) rooms = 1;
                    return rooms * 2;
                }

                if (roomsInput) {
                    roomsInput.addEventListener('input', function () {
                        const maxChildren = getMaxChildren();
                        let currentChildren = parseInt(children.textContent || '0', 10);
                        if (currentChildren > maxChildren) {
                            children.textContent = maxChildren;
                        }
                    });
                }

                if (popup && btn && adults && children && inputAdults && inputChildren && summary) {
                    btn.addEventListener("click", () => {
                        popup.style.display = popup.style.display === "block" ? "none" : "block";
                    });

                    const updateText = () => {
                        summary.textContent =
                            `${adults.textContent} Người lớn, ${children.textContent} Trẻ em`;
                    };

                    popup.querySelectorAll(".btn-plus").forEach(button => {
                        button.addEventListener("click", () => {
                            const target = button.dataset.target;

                            if (target === 'adultsSidebar') {
                                adults.textContent = parseInt(adults.textContent || '0', 10) + 1;
                            } else if (target === 'childrenSidebar') {
                                const current = parseInt(children.textContent || '0', 10);
                                const maxChildren = getMaxChildren();
                                if (current >= maxChildren) {
                                    alert("Mỗi phòng tối đa 2 trẻ em.");
                                    return;
                                }
                                children.textContent = current + 1;
                            }
                        });
                    });

                    popup.querySelectorAll(".btn-minus").forEach(button => {
                        button.addEventListener("click", () => {
                            const target = button.dataset.target;

                            if (target === 'adultsSidebar') {
                                let val = parseInt(adults.textContent || '0', 10);
                                if (val > 1) adults.textContent = val - 1;
                            } else if (target === 'childrenSidebar') {
                                let val = parseInt(children.textContent || '0', 10);
                                if (val > 0) children.textContent = val - 1;
                            }
                        });
                    });

                    document.getElementById("guestPopupSidebarDone").addEventListener("click", () => {
                        inputAdults.value = adults.textContent;
                        inputChildren.value = children.textContent;

                        updateText();
                        popup.style.display = "none";
                    });

                    document.addEventListener("click", (e) => {
                        if (!popup.contains(e.target) && !btn.contains(e.target)) {
                            popup.style.display = "none";
                        }
                    });
                }
            });
        </script>

        {{-- Compare Rooms JavaScript --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const MAX_COMPARE = 4;
                const STORAGE_KEY = 'compareRooms';

                function getComparedRooms() {
                    const stored = localStorage.getItem(STORAGE_KEY);
                    return stored ? JSON.parse(stored) : [];
                }

                function saveComparedRooms(rooms) {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(rooms));
                }

                function updateCompareBar() {
                    const rooms = getComparedRooms();
                    const compareBar = document.getElementById('compareBar');
                    const compareCount = document.getElementById('compareCount');

                    if (rooms.length > 0) {
                        compareBar.style.display = 'block';
                        compareCount.textContent = rooms.length;
                    } else {
                        compareBar.style.display = 'none';
                    }

                    document.querySelectorAll('.compare-btn').forEach(btn => {
                        const roomId = parseInt(btn.dataset.roomId);
                        const isCompared = rooms.some(r => r.id === roomId);

                        if (isCompared) {
                            btn.classList.remove('btn-outline-primary');
                            btn.classList.add('btn-success');
                            btn.querySelector('.compare-text').textContent = 'Đã chọn';
                            btn.querySelector('i').classList.remove('bi-plus-circle');
                            btn.querySelector('i').classList.add('bi-check-circle-fill');
                        } else {
                            btn.classList.remove('btn-success');
                            btn.classList.add('btn-outline-primary');
                            btn.querySelector('.compare-text').textContent = 'So sánh';
                            btn.querySelector('i').classList.remove('bi-check-circle-fill');
                            btn.querySelector('i').classList.add('bi-plus-circle');
                        }
                    });
                }

                document.querySelectorAll('.compare-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const roomId = parseInt(this.dataset.roomId);
                        let rooms = getComparedRooms();

                        const existingIndex = rooms.findIndex(r => r.id === roomId);

                        if (existingIndex !== -1) {
                            rooms.splice(existingIndex, 1);
                            saveComparedRooms(rooms);
                            updateCompareBar();
                        } else {
                            if (rooms.length >= MAX_COMPARE) {
                                alert(`Bạn chỉ có thể so sánh tối đa ${MAX_COMPARE} phòng cùng lúc!`);
                                return;
                            }

                            rooms.push({
                                id: roomId,
                                name: this.dataset.roomName,
                                price: parseFloat(this.dataset.roomPrice),
                                image: this.dataset.roomImage
                            });

                            saveComparedRooms(rooms);
                            updateCompareBar();
                        }
                    });
                });

                document.getElementById('clearCompare').addEventListener('click', function() {
                    if (confirm('Bạn có chắc muốn xóa tất cả phòng đã chọn?')) {
                        localStorage.removeItem(STORAGE_KEY);
                        updateCompareBar();
                    }
                });

                updateCompareBar();
            });
        </script>
    @endpush

@endsection

@push('styles')
    <style>
        /* Popup chọn khách (dùng chung với home) */
        .guest-selector-box {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #dadce0;
            background: #fff;
            padding: 10px 14px;
            border-radius: 14px;
            cursor: pointer;
            user-select: none;
        }
        .guest-selector-box:hover {
            border-color: #5E3EFF;
        }

        .guest-popup {
            position: absolute;
            top: 110%;
            left: 0;
            width: 100%;
            background: white;
            border-radius: 16px;
            padding: 16px;
            display: none;
            z-index: 50;
        }

        .guest-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .guest-row:last-child {
            border-bottom: none;
        }

        .guest-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .guest-info .icon {
            font-size: 22px;
            color: #5E3EFF;
        }

        .guest-control {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-minus,
        .btn-plus {
            width: 28px;
            height: 28px;
            background: #f2f2f2;
            border: none;
            border-radius: 50%;
            font-size: 18px;
            line-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-minus:hover,
        .btn-plus:hover {
            background: #e0e0e0;
        }

        /* Tùy chỉnh thanh trượt giá */
        .noUi-target,
        .noUi-target * {
            box-shadow: none !important;
        }

        .noUi-target {
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid #ddd;
            border-radius: 10px;
            height: 8px;
        }

        .noUi-connect {
            background: rgba(110, 110, 110, 0.6) !important;
            transition: background 0.3s ease;
        }

        .noUi-handle {
            width: 26px !important;
            height: 26px !important;
            border-radius: 50% !important;
            background: #fff !important;
            border: 3px solid #5E3EFF !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .noUi-handle:hover {
            border-color: #a58dff;
        }

        .sidebar-filter h6 {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .sidebar-filter .list-unstyled li {
            margin-bottom: 6px;
        }

        .filter-box {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .filter-divider {
            border-top: 1px solid #eee;
            margin: 15px 0;
        }

        .filter-submit-btn {
            background: #6759ff;
            color: #fff;
            border: none;
            border-radius: 10px;
            width: 100%;
            padding: 10px 0;
            font-weight: 500;
            transition: 0.25s;
        }

        .filter-submit-btn:hover {
            background: #5845f5;
        }

        .object-fit-cover {
            object-fit: cover;
        }

        section.rounded-4 img {
            transition: transform 0.6s ease;
        }

        section.rounded-4:hover img {
            transform: scale(1.05);
        }

        /* Carousel ảnh phòng */
        .room-carousel-inner {
            height: 250px;
            overflow: hidden;
            border-radius: 14px 0 0 14px;
        }

        .room-carousel-img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .room-carousel-img:hover {
            transform: scale(1.05);
        }

        /* Đảm bảo toàn card không nhảy khi đổi ảnh */
        .room-card {
            min-height: 250px;
        }

        @media (max-width: 768px) {
            .room-carousel-inner {
                height: 200px;
            }
        }

        /* Compare Bar Styles */
        .compare-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .compare-bar h6 {
            margin: 0;
            font-weight: 600;
        }

        .compare-bar .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
        }

        .compare-bar .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: white;
        }

        .compare-bar .btn-light {
            background-color: white;
            color: #667eea;
            font-weight: 600;
        }

        .compare-bar .btn-light:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .compare-bar .d-flex {
                flex-direction: column;
                gap: 0.5rem;
            }

            .compare-bar .gap-2 {
                width: 100%;
            }

            .compare-bar .btn {
                width: 100%;
            }
        }
    </style>
@endpush
