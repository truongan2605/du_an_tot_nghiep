@extends('layouts.app')

@section('title', 'Danh sách phòng')

@section('content')
    @php
        // Flag do RoomController truyền sang (xem code đã chỉnh trước đó)
        $weekendSearch = isset($hasWeekend) && $hasWeekend;
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
                            <input type="text" class="form-control flatpickr mb-3" data-mode="range"
                                placeholder="Chọn ngày" name="date_range" value="{{ request('date_range') }}">

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
                            <h6>Tiện nghi</h6>
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
                                    $displayPrice = ceil($displayPrice * 1.1); // làm tròn lên cho chắc
                                }
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

                                                {{-- Tên loại phòng (hoặc tên phòng nếu bạn muốn) --}}
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
                                                            <a href="{{ route('rooms.show', $phong->id) }}"
                                                                class="text-decoration-none">Xem thêm+</a>
                                                        @endif
                                                    @else
                                                        <span>Chưa có tiện nghi</span>
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

                                                    {{-- Luôn cho phép chọn phòng, kiểm tra còn phòng ở trang đặt phòng --}}
                                                    <a href="{{ route('rooms.show', $phong->id) }}"
                                                        class="btn btn-dark rounded-pill px-4 py-2">
                                                        Chọn phòng
                                                    </a>
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

                    {{-- Phân trang --}}
                    <div class="mt-4 d-flex justify-content-center">{{ $phongs->links() }}</div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css" rel="stylesheet">

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var priceSlider = document.getElementById('price-slider');
                var minInput = document.getElementById('gia_min');
                var maxInput = document.getElementById('gia_max');
                var minLabel = document.getElementById('min-price');
                var maxLabel = document.getElementById('max-price');

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
            });
        </script>
    @endpush

@endsection

@push('styles')
    <style>
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
    </style>
@endpush
