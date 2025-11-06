@extends('layouts.app')

@section('title', 'Danh sách phòng')

@section('content')
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
            <h1 class="display-5 fw-bold mb-3" style="color: #ddd">List of available rooms</h1>
            <p class="lead mb-0" style="max-width: 600px;">
                Explore active rooms in our system
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
                            <h6 class="fw-bold mb-3">Room Type</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <input type="radio" name="loai_phong_id" id="type_all" value=""
                                        {{ request('loai_phong_id') == '' ? 'checked' : '' }}>
                                    <label for="type_all">All Room Type</label>
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
                            <h6>Check in - out</h6>
                            <input type="text" class="form-control flatpickr mb-3" data-mode="range"
                                placeholder="Chọn ngày" name="date_range">

                            <h6>Price (VNĐ)</h6>
                            <div class="mb-3">
                                <div id="price-slider"></div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span id="min-price"
                                        class="small fw-semibold">{{ number_format($giaMin, 0, ',', '.') }}đ</span>
                                    <span id="max-price"
                                        class="small fw-semibold">{{ number_format($giaMax, 0, ',', '.') }}đ</span>
                                </div>
                                <input type="hidden" id="gia_min" name="gia_min"
                                    value="{{ request('gia_min', $giaMin) }}">
                                <input type="hidden" id="gia_max" name="gia_max"
                                    value="{{ request('gia_max', $giaMax) }}">
                            </div>

                            <div class="filter-divider"></div>

                            {{-- Rating --}}
                            <h6>Rating Star</h6>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button type="button" class="btn btn-light btn-sm border">★</button>
                                <button type="button" class="btn btn-light btn-sm border">★★</button>
                                <button type="button" class="btn btn-light btn-sm border">★★★</button>
                                <button type="button" class="btn btn-light btn-sm border">★★★★</button>
                                <button type="button" class="btn btn-light btn-sm border">★★★★★</button>
                            </div>

                            <div class="filter-divider"></div>

                            {{-- Amenities --}}
                            <h6>Amenities</h6>
                            <ul class="list-unstyled">
                                @foreach ($tienNghis as $tienNghi)
                                    <li>
                                        <input type="checkbox" name="tien_nghi[]" id="amenity{{ $tienNghi->id }}"
                                            value="{{ $tienNghi->id }}">
                                        <label for="amenity{{ $tienNghi->id }}">{{ $tienNghi->ten }}</label>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-3">
                                <button type="submit" class="filter-submit-btn">Search</button>
                            </div>
                        </div>

                    </form>
                </aside>

                {{-- ==== DANH SÁCH PHÒNG ==== --}}
                <div class="col-lg-9">
                    <div class="row g-4">
                        @forelse($phongs as $phong)
                            <div class="col-12">
                                <div
                                    class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 room-card position-relative hover-shadow transition-all">

                                    {{-- ========== ẢNH PHÒNG / CAROUSEL ========== --}}
                                    <div class="row g-0 align-items-center">
                                        <div class="col-md-5 position-relative">
                                            @if ($phong->images->count() > 1)
                                                <div id="carouselRoom{{ $phong->id }}" class="carousel slide"
                                                    data-bs-ride="carousel">
                                                    <div class="carousel-inner">
                                                        @foreach ($phong->images as $key => $img)
                                                            <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                                                                <img src="{{ asset($img->duong_dan) }}"
                                                                    class="w-100 h-100 object-fit-cover"
                                                                    alt="{{ $phong->name }}">
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
                                                <img src="{{ asset($phong->firstImageUrl() ?? 'template/stackbros/assets/images/default-room.jpg') }}"
                                                    class="w-100 h-100 object-fit-cover" alt="{{ $phong->name }}">
                                            @endif

                                            {{-- Badge Giảm giá nếu có --}}
                                            @if (isset($phong->khuyen_mai) && $phong->khuyen_mai > 0)
                                                <span
                                                    class="badge bg-danger position-absolute top-0 start-0 m-3 px-3 py-2 fs-6 shadow-sm">
                                                    -{{ $phong->khuyen_mai }}%
                                                </span>
                                            @endif

                                            {{-- Badge trạng thái phòng --}}
                                            @if ($phong->trang_thai == 'Trống')
                                                <span
                                                    class="badge bg-success position-absolute bottom-0 start-0 m-3 px-3 py-2 shadow-sm">Phòng
                                                    trống</span>
                                            @elseif($phong->trang_thai == 'Đang ở')
                                                <span
                                                    class="badge bg-warning text-dark position-absolute bottom-0 start-0 m-3 px-3 py-2 shadow-sm">Đang
                                                    ở</span>
                                            @endif
                                        </div>

                                        {{-- ========== THÔNG TIN PHÒNG ========== --}}
                                        <div class="col-md-7">
                                            <div class="card-body py-4 px-4">
                                                {{-- Đánh giá sao --}}
                                                <div class="d-flex align-items-center mb-2">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <i
                                                            class="bi bi-star{{ $i <= ($phong->so_sao ?? 4) ? '-fill text-warning' : '' }} me-1"></i>
                                                    @endfor
                                                </div>

                                                {{-- Tên phòng --}}
                                                <h5 class="fw-bold mb-1">{{ $phong->name ?? $phong->ma_phong }}</h5>

                                                {{-- Mô tả hoặc vị trí --}}
                                                <p class="text-muted mb-2">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    {{ $phong->mo_ta ?? 'Địa điểm đang cập nhật' }}
                                                </p>

                                                {{-- Tiện nghi --}}
                                                <div class="small text-muted mb-2">
                                                    @if ($phong->tienNghis && $phong->tienNghis->count())
                                                        @foreach ($phong->tienNghis->take(3) as $tiennghi)
                                                            {{ $tiennghi->ten_tien_nghi }} &bull;
                                                        @endforeach
                                                        @if ($phong->tienNghis->count() > 3)
                                                            <a href="#" class="text-decoration-none">More+</a>
                                                        @endif
                                                    @else
                                                        <span>Chưa có tiện nghi</span>
                                                    @endif
                                                </div>

                                                {{-- Ưu đãi --}}
                                                <ul class="list-unstyled small mb-3 text-success">
                                                    <li><i class="bi bi-check-circle me-2"></i>Miễn phí huỷ phòng</li>
                                                    <li><i class="bi bi-check-circle me-2"></i>Bữa sáng miễn phí</li>
                                                </ul>

                                                {{-- Giá & Nút chọn phòng --}}
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h4 class="fw-bold text-primary mb-0">
                                                            {{ number_format($phong->gia_cuoi_cung, 0, ',', '.') }} VNĐ
                                                            <span class="small fw-normal text-muted">/Night</span>
                                                        </h4>
                                                        {{-- @if ($phong->gia_mac_dinh ?? false)
                                    <small class="text-decoration-line-through text-muted">
                                        {{ number_format($phong->gia_mac_dinh, 0, ',', '.') }} VNĐ
                                    </small>
                                @endif --}}

                                                    </div>
                                                    @if ($phong->trang_thai == 'trong')
                                                        <a href="{{ route('rooms.show', $phong->id) }}"
                                                            class="btn btn-dark rounded-pill px-4 py-2 transition-all">
                                                            Select Room
                                                        </a>
                                                    @else
                                                        <button class="btn btn-secondary rounded-pill px-4 py-2" disabled>
                                                            Full Room
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <img src="{{ asset('template/stackbros/assets/images/no-data.svg') }}" alt="No rooms"
                                    width="180">
                                <p class="mt-3 mb-0">Không tìm thấy phòng nào phù hợp.</p>
                            </div>
                        @endforelse

                    </div>
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

                var minVal = parseInt(minInput.value);
                var maxVal = parseInt(maxInput.value);

                noUiSlider.create(priceSlider, {
                    start: [minVal, maxVal],
                    connect: true,
                    range: {
                        'min': {{ $giaMin }},
                        'max': {{ $giaMax }}
                    },
                    step: 50000, // bước 50.000đ
                    format: {
                        to: value => Math.round(value),
                        from: value => Math.round(value)
                    }
                });

                priceSlider.noUiSlider.on('update', function(values) {
                    minInput.value = values[0];
                    maxInput.value = values[1];
                    minLabel.textContent = new Intl.NumberFormat('vi-VN').format(values[0]) + 'đ';
                    maxLabel.textContent = new Intl.NumberFormat('vi-VN').format(values[1]) + 'đ';
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
            /* tím nhạt, có thể đổi */
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
    </style>
@endpush
