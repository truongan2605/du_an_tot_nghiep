@extends('layouts.app')

@section('title', 'Home - Booking')

@section('content')

    <!-- =======================
                            Main Banner START -->
    <section class="pt-3 pt-lg-5">
        <div class="container">
            <!-- Content and Image START -->
            <div class="row g-4 g-lg-5">
                <!-- Content -->
                <div class="col-lg-6 position-relative mb-4 mb-md-0">
                    <!-- Title -->
                    <h1 class="mb-4 mt-md-5 display-5">Tìm trải nghiệm
                        <span class="position-relative z-index-9">Tuyệt vời nhất.
                            <!-- SVG START -->
                            <span
                                class="position-absolute top-50 start-50 translate-middle z-index-n1 d-none d-md-block mt-4">
                                <svg width="390.5px" height="21.5px" viewBox="0 0 445.5 21.5">
                                    <path class="fill-primary opacity-7"
                                        d="M409.9,2.6c-9.7-0.6-19.5-1-29.2-1.5c-3.2-0.2-6.4-0.2-9.7-0.3c-7-0.2-14-0.4-20.9-0.5 c-3.9-0.1-7.8-0.2-11.7-0.3c-1.1,0-2.3,0-3.4,0c-2.5,0-5.1,0-7.6,0c-11.5,0-23,0-34.5,0c-2.7,0-5.5,0.1-8.2,0.1 c-6.8,0.1-13.6,0.2-20.3,0.3c-7.7,0.1-15.3,0.1-23,0.3c-12.4,0.3-24.8,0.6-37.1,0.9c-7.2,0.2-14.3,0.3-21.5,0.6 c-12.3,0.5-24.7,1-37,1.5c-6.7,0.3-13.5,0.5-20.2,0.9C112.7,5.3,99.9,6,87.1,6.7C80.3,7.1,73.5,7.4,66.7,8 C54,9.1,41.3,10.1,28.5,11.2c-2.7,0.2-5.5,0.5-8.2,0.7c-5.5,0.5-11,1.2-16.4,1.8c-0.3,0-0.7,0.1-1,0.1c-0.7,0.2-1.2,0.5-1.7,1 C0.4,15.6,0,16.6,0,17.6c0,1,0.4,2,1.1,2.7c0.7,0.7,1.8,1.2,2.7,1.1c6.6-0.7,13.2-1.5,19.8-2.1c6.1-0.5,12.3-1,18.4-1.6 c6.7-0.6,13.4-1.1,20.1-1.7c2.7-0.2,5.4-0.5,8.1-0.7c10.4-0.6,20.9-1.1,31.3-1.7c6.5-0.4,13-0.7,19.5-1.1c2.7-0.1,5.4-0.3,8.1-0.4 c10.3-0.4,20.7-0.8,31-1.2c6.3-0.2,12.5-0.5,18.8-0.7c2.1-0.1,4.2-0.2,6.3-0.2c11.2-0.3,22.3-0.5,33.5-0.8 c6.2-0.1,12.5-0.3,18.7-0.4c2.2-0.1,4.4-0.1,6.7-0.1c11.5-0.1,23-0.2,34.6-0.4c7.2-0.1,14.4-0.1,21.6-0.1c12.2,0,24.5,0.1,36.7,0.1 c2.4,0,4.8,0.1,7.2,0.2c6.8,0.2,13.5,0.4,20.3,0.6c5.1,0.2,10.1,0.3,15.2,0.4c3.6,0.1,7.2,0.4,10.8,0.6c10.6,0.6,21.1,1.2,31.7,1.8 c2.7,0.2,5.4,0.4,8,0.6c2.9,0.2,5.8,0.4,8.6,0.7c0.4,0.1,0.9,0.2,1.3,0.3c1.1,0.2,2.2,0.2,3.2-0.4c0.9-0.5,1.6-1.5,1.9-2.5 c0.6-2.2-0.7-4.5-2.9-5.2c-1.9-0.5-3.9-0.7-5.9-0.9c-1.4-0.1-2.7-0.3-4.1-0.4c-2.6-0.3-5.2-0.4-7.9-0.6 C419.7,3.1,414.8,2.9,409.9,2.6z" />
                                </svg>
                            </span>
                            <!-- SVG END -->
                        </span>
                    </h1>
                    <!-- Info -->
                    <p class="mb-4">Chúng tôi mang đến cho bạn không chỉ một lựa chọn lưu trú mà còn là một trải nghiệm tận hưởng sự sang trọng trong tầm giá của bạn.</p>

                    <!-- Buttons -->
                    <div class="hstack gap-4 flex-wrap align-items-center">
                        <!-- Button -->
                        <a href="#" class="btn btn-primary-soft mb-0">Khám phá ngay</a>
                        <!-- Story button -->
                        <a data-glightbox="" data-gallery="office-tour" href="https://www.youtube.com/embed/tXHviS-4ygo"
                            class="d-block">
                            <!-- Avatar -->
                            <div class="avatar avatar-md z-index-1 position-relative me-2">
                                <img class="avatar-img rounded-circle"
                                    src="{{ asset('template/stackbros/assets/images/avatar/12.jpg') }}" alt="avatar">
                                <!-- Video button -->
                                <div
                                    class="btn btn-xs btn-round btn-white shadow-sm position-absolute top-50 start-50 translate-middle z-index-9 mb-0">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="align-middle d-inline-block">
                                <h6 class="fw-normal small mb-0">Theo dõi câu chuyện của chúng tôi</h6>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Image -->
                <div class="col-lg-6 position-relative">

                    <img src="{{ asset('template/stackbros/assets/images/bg/06.jpg') }}" class="rounded" alt="">

                    <!-- Svg decoration -->
                    <figure class="position-absolute end-0 bottom-0">
                        <svg width="163px" height="163px" viewBox="0 0 163 163">
                            <!-- svg content -->
                        </svg>
                    </figure>

                    <!-- Support guid -->
                    <div class="position-absolute top-0 end-0 z-index-1 mt-n4">
                        <div class="bg-blur border border-light rounded-3 text-center shadow-lg p-3">
                            <!-- Title -->
                            <i class="bi bi-headset text-danger fs-3"></i>
                            <h5 class="text-dark mb-1">24 / 7</h5>
                            <h6 class="text-dark fw-light small mb-0">Hướng dẫn hỗ trợ</h6>
                        </div>
                    </div>

                    <!-- Round image group -->
                    <div
                        class="vstack gap-5 align-items-center position-absolute top-0 start-0 d-none d-md-flex mt-4 ms-n3">
                        <img class="icon-lg shadow-lg border border-3 border-white rounded-circle"
                            src="{{ asset('template/stackbros/assets/images/category/hotel/4by3/11.jpg') }}" alt="avatar">
                        <img class="icon-xl shadow-lg border border-3 border-white rounded-circle"
                            src="{{ asset('template/stackbros/assets/images/category/hotel/4by3/12.jpg') }}" alt="avatar">
                    </div>
                </div>
            </div>
            <!-- Content and Image END -->

            <!-- Search START -->
            <form action="{{ route('list-room.index') }}" method="GET"
                class="card shadow rounded-4 position-relative p-4 pb-5 pb-md-4">
                <div class="row g-4 align-items-center">

                    <!-- Loại phòng -->
                    <div class="col-lg-3 col-md-6 d-flex align-items-center">
                        <i class="bi bi-door-open fs-3 me-2 text-muted"></i>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold text-muted mb-1">Room Type</label>
                            <select class="form-select js-choice" name="loai_phong_id" data-search-enabled="true">
                                <option value="">-- All Room Type --</option>
                                @foreach ($loaiPhongs as $loaiPhong)
                                    <option value="{{ $loaiPhong->id }}"
                                        {{ request('loai_phong_id') == $loaiPhong->id ? 'selected' : '' }}>
                                        {{ $loaiPhong->ten_loai_phong ?? $loaiPhong->ten }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Check in -->
                    <div class="col-lg-3 col-md-6 d-flex align-items-center">
                        <i class="bi bi-calendar fs-3 me-2 text-muted"></i>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold text-muted mb-1">Check in - out</label>
                            <input type="text" class="form-control flatpickr" name="date_range" data-mode="range"
                                placeholder="Select date" value="{{ request('date_range') }}">
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="col-lg-5 col-md-12 d-flex align-items-center">
                        <i class="bi bi-cash-stack fs-3 me-3 text-muted"></i>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold text-muted mb-1">Price (VNĐ)</label>
                            <div id="price-slider-home" class="my-1"></div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span id="min-price-home">{{ number_format($giaMin, 0, ',', '.') }}đ</span>
                                <span id="max-price-home">{{ number_format($giaMax, 0, ',', '.') }}đ</span>
                            </div>
                            <input type="hidden" id="gia_min_home" name="gia_min"
                                value="{{ request('gia_min', $giaMin) }}">
                            <input type="hidden" id="gia_max_home" name="gia_max"
                                value="{{ request('gia_max', $giaMax) }}">
                        </div>
                    </div>

                    <!-- Button -->
                    <div class="col-lg-1 col-md-12 d-flex justify-content-center">
                        <button type="submit"
                            class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 50px; height: 50px; background-color: #5E3EFF;">
                            <i class="bi bi-search fs-4"></i>
                        </button>
                    </div>
                </div>
            </form>
            <!-- Search END -->
        </div>
    </section>
    <!-- =======================
                            Main Banner END -->

    <!-- =======================
         Blog Best deal (slider) START -->
    <section class="pb-2 pb-lg-5">
        <div class="container">

            <div class="tiny-slider arrow-round arrow-blur arrow-hover">
                <div class="tiny-slider-inner" data-autoplay="true" data-arrow="true" data-edge="2" data-dots="false"
                    data-items-xl="3" data-items-lg="2" data-items-md="1">

                    @foreach ($blogPosts ?? collect() as $post)
                        <!-- Slider item -->
                        <div>
                            <div class="card border rounded-3 overflow-hidden">
                                <div class="row g-0 align-items-center">
                                    <!-- Image -->
                                    <div class="col-sm-6">
                                        <a href="{{ route('blog.show', $post->slug) }}">
                                            <img src="{{ $post->cover_image ? asset('storage/' . $post->cover_image) : asset('assets/images/blog/feature.jpg') }}"
                                                class="card-img rounded-0" alt="{{ $post->title }}">
                                        </a>
                                    </div>

                                    <!-- Title and content -->
                                    <div class="col-sm-6">
                                        <div class="card-body px-3">
                                            @if ($post->category)
                                                <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}"
                                                    class="badge bg-primary mb-2 text-white">{{ $post->category->name }}</a>
                                            @endif

                                            <h6 class="card-title mb-1">
                                                <a href="{{ route('blog.show', $post->slug) }}" class="stretched-link">
                                                    {{ $post->title }}
                                                </a>
                                            </h6>

                                            <p class="mb-2 text-muted small">
                                                {{ \Illuminate\Support\Str::limit($post->excerpt, 80) }}
                                            </p>

                                            <small class="text-muted">
                                                <i class="bi bi-calendar2-plus me-1"></i>
                                                {{ optional($post->published_at)->format('M d, Y') }}
                                                &nbsp;•&nbsp; By {{ optional($post->author)->name ?? 'Admin' }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

        </div>
    </section>
    <!-- =======================
         Blog Best deal END -->


    <!-- =======================
                            About START -->
    <section class="pb-0 pb-xl-5">
        <div class="container">
            <div class="row g-4 justify-content-between align-items-center">
                <!-- Left side START -->
                <div class="col-lg-5 position-relative">

                    <!-- Image -->
                    <img src="{{ asset('template/stackbros/assets/images/about/01.jpg') }}"
                        class="rounded-3 position-relative" alt="">

                    <!-- Client rating START -->
                    <div class="position-absolute bottom-0 start-0 z-index-1 mb-4 ms-5">
                        <div class="bg-body d-flex d-inline-block rounded-3 position-relative p-3">

                            <!-- Element -->
                            <img src="{{ asset('template/stackbros/assets/images/element/01.svg') }}"
                                class="position-absolute top-0 start-0 translate-middle w-40px" alt="">

                            <!-- Avatar group -->
                            <div class="me-4">
                                <h6 class="fw-light">Client</h6>
                                <!-- Avatar group -->
                                <ul class="avatar-group mb-0">
                                    <li class="avatar avatar-sm">
                                        <img class="avatar-img rounded-circle"
                                            src="{{ asset('template/stackbros/assets/images/avatar/01.jpg') }}"
                                            alt="avatar">
                                    </li>
                                    <li class="avatar avatar-sm">
                                        <img class="avatar-img rounded-circle"
                                            src="{{ asset('template/stackbros/assets/images/avatar/02.jpg') }}"
                                            alt="avatar">
                                    </li>
                                    <li class="avatar avatar-sm">
                                        <img class="avatar-img rounded-circle"
                                            src="{{ asset('template/stackbros/assets/images/avatar/03.jpg') }}"
                                            alt="avatar">
                                    </li>
                                    <li class="avatar avatar-sm">
                                        <img class="avatar-img rounded-circle"
                                            src="{{ asset('template/stackbros/assets/images/avatar/04.jpg') }}"
                                            alt="avatar">
                                    </li>
                                    <li class="avatar avatar-sm">
                                        <div class="avatar-img rounded-circle bg-primary">
                                            <span
                                                class="text-white position-absolute top-50 start-50 translate-middle small">1K+</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>

                            <!-- Rating -->
                            <div>
                                <h6 class="fw-light mb-3">Rating</h6>
                                <h6 class="m-0">4.5<i class="fa-solid fa-star text-warning ms-1"></i></h6>
                            </div>
                        </div>
                    </div>
                    <!-- Client rating END -->
                </div>
                <!-- Left side END -->

                <!-- Right side START -->
                <div class="col-lg-6">
                    <h2 class="mb-3 mb-lg-5">Kỳ nghỉ tuyệt vời nhất bắt đầu từ đây!</h2>
                    <p class="mb-3 mb-lg-5">Đặt phòng khách sạn với chúng tôi và đừng quên nắm bắt ưu đãi khách sạn tuyệt vời để tiết kiệm đáng kể cho kỳ nghỉ của bạn.</p>

                    <!-- Features START -->
                    <div class="row g-4">
                        <!-- Item -->
                        <div class="col-sm-6">
                            <div class="icon-lg bg-success bg-opacity-10 text-success rounded-circle"><i
                                    class="fa-solid fa-utensils"></i></div>
                            <h5 class="mt-2">Đồ ăn chất lượng</h5>
                            <p class="mb-0">Đảm bảo chất lượng đồ ăn đẳng cấp 5 sao từ những đầu bếp hàng đầu</p>
                        </div>
                        <!-- Item -->
                        <div class="col-sm-6">
                            <div class="icon-lg bg-danger bg-opacity-10 text-danger rounded-circle"><i
                                    class="bi bi-stopwatch-fill"></i></div>
                            <h5 class="mt-2">Phục vụ nhanh chóng</h5>
                            <p class="mb-0">Đảm bảo chất lượng phục vụ nhanh chóng 24/7</p>
                        </div>
                        <!-- Item -->
                        <div class="col-sm-6">
                            <div class="icon-lg bg-orange bg-opacity-10 text-orange rounded-circle"><i
                                    class="bi bi-shield-fill-check"></i></div>
                            <h5 class="mt-2">Bảo mật khách hàng</h5>
                            <p class="mb-0">Thông tin khách hàng được bảo mật tuyệt đối</p>
                        </div>
                        <!-- Item -->
                        <div class="col-sm-6">
                            <div class="icon-lg bg-info bg-opacity-10 text-info rounded-circle"><i
                                    class="bi bi-lightning-fill"></i></div>
                            <h5 class="mt-2">Bảo vệ 24/7</h5>
                            <p class="mb-0">Có lực lượng bảo vệ hoạt động 24/7 đảm bảo an toàn khách hàng</p>
                        </div>
                    </div>
                    <!-- Features END -->

                </div>
                <!-- Right side END -->
            </div>
        </div>
    </section>
    <!-- =======================
                            About END -->

    <!-- =======================
                            Featured Hotels START -->
    <section>
        <div class="container mt-5">
            <!-- Title -->
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h2 class="mb-0">Các phòng nổi bật</h2>
                </div>
            </div>

            <div class="row g-4">
                @forelse($phongs as $phong)
                    <div class="col-sm-6 col-xl-3">
                        <!-- Card START -->
                        <div class="card card-img-scale overflow-hidden bg-transparent">
                            <!-- Image and overlay -->
                            <div class="card-img-scale-wrapper rounded-3">
                                <!-- Image: dùng helper trong model -->
                                <img src="{{ $phong->firstImageUrl() }}" class="card-img" alt="hotel image">

                                <!-- Wishlist button -->
                                @php
                                    $isFav = in_array($phong->id, $favoriteIds ?? []);
                                @endphp
                                <button type="button" class="btn btn-sm btn-wishlist position-absolute top-0 end-0 m-2"
                                    data-phong-id="{{ $phong->id }}" aria-pressed="{{ $isFav ? 'true' : 'false' }}"
                                    aria-label="{{ $isFav ? 'Remove from wishlist' : 'Add to wishlist' }}"
                                    title="{{ $isFav ? 'Remove from wishlist' : 'Add to wishlist' }}">
                                    <i
                                        class="{{ $isFav ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart text-white' }}">
                                    </i>
                                </button>

                                <!-- Badge: Hiển thị loại phòng -->
                                <div class="position-absolute bottom-0 start-0 p-3">
                                    <div class="badge text-bg-dark fs-6 rounded-pill stretched-link">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        {{ $phong->loaiPhong->ten_loai ?? ($phong->loaiPhong->ten ?? '—') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Card body -->
                            <div class="card-body px-2">
                                <h5 class="card-title">
                                    <a href="{{ route('rooms.show', $phong->id) }}"
                                        class="stretched-link text-decoration-none">
                                        {{ $phong->name ?? null }}
                                    </a>
                                </h5>

                                <!-- Price and rating -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="text-success mb-0">
                                        {{ number_format($phong->gia_cuoi_cung, 0, '.', ',') }} VND
                                    </h6>
                                    <h6 class="mb-0">4.5<i class="fa-solid fa-star text-warning ms-1"></i></h6>
                                </div>
                            </div>
                        </div>
                        <!-- Card END -->
                    </div>
                @empty
                    <div class="col-12 text-center">There are no rooms available yet.</div>
                @endforelse
            </div>
        </div>
    </section>
    <!-- =======================
                            Featured Hotels END -->

    <!-- =======================
                            Client START -->
    <section class="py-0 py-md-5">
        <div class="container">
            <div class="row g-4 g-lg-7 justify-content-center align-items-center">
                <!-- Image -->
                <div class="col-5 col-sm-3 col-xl-2">
                    <img src="{{ asset('template/stackbros/assets/images/client/01.svg') }}" class="grayscale"
                        alt="">
                </div>
                <!-- Image -->
                <div class="col-5 col-sm-3 col-xl-2">
                    <img src="{{ asset('template/stackbros/assets/images/client/02.svg') }}" class="grayscale"
                        alt="">
                </div>
                <!-- Image -->
                <div class="col-5 col-sm-3 col-xl-2">
                    <img src="{{ asset('template/stackbros/assets/images/client/03.svg') }}" class="grayscale"
                        alt="">
                </div>
                <!-- Image -->
                <div class="col-5 col-sm-3 col-xl-2">
                    <img src="{{ asset('template/stackbros/assets/images/client/04.svg') }}" class="grayscale"
                        alt="">
                </div>
                <!-- Image -->
                <div class="col-5 col-sm-3 col-xl-2">
                    <img src="{{ asset('template/stackbros/assets/images/client/05.svg') }}" class="grayscale"
                        alt="">
                </div>
                <!-- Image -->
                <div class="col-5 col-sm-3 col-xl-2">
                    <img src="{{ asset('template/stackbros/assets/images/client/06.svg') }}" class="grayscale"
                        alt="">
                </div>
            </div>
        </div>
    </section>
    <!-- =======================
                            Client END -->

    <!-- =======================
                            Download app START -->
    <section class="bg-light">
        <div class="container">
            <div class="row g-4">

                <!-- Help -->
                <div class="col-md-6 col-xxl-4">
                    <div class="bg-body d-flex rounded-3 h-100 p-4">
                        <h3><i class="fa-solid fa-hand-holding-heart"></i></h3>
                        <div class="ms-3">
                            <h5>Hỗ trợ 24/7 </h5>
                            <p class="mb-0">Nếu chúng tôi không đáp ứng được kỳ vọng của bạn theo bất kỳ cách nào, hãy cho chúng tôi biết</p>
                        </div>
                    </div>
                </div>

                <!-- Trust -->
                <div class="col-md-6 col-xxl-4">
                    <div class="bg-body d-flex rounded-3 h-100 p-4">
                        <h3><i class="fa-solid fa-hand-holding-usd"></i></h3>
                        <div class="ms-3">
                            <h5>Thanh toán minh bạch</h5>
                            <p class="mb-0">Tất cả các khoản hoàn tiền đều đi kèm với đảm bảo minh bạch</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xxl-4">
                    <div class="bg-body d-flex rounded-3 h-100 p-4">
                        <h3><i class="fa-solid fa-shield"></i></i></h3>
                        <div class="ms-3">
                            <h5>Chính sách bảo mật</h5>
                            <p class="mb-0">Chính sách bảo mật rõ ràng đảm bảo an toàn thông tin khách hàng</p>
                        </div>
                    </div>
                </div>

                <!-- Download app -->

            </div>
        </div>
    </section>
    <!-- =======================
                            Download app END -->


    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/nouislider@15.7.0/dist/nouislider.min.css" rel="stylesheet">

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var priceSlider = document.getElementById('price-slider-home');
                if (!priceSlider) return;

                var minInput = document.getElementById('gia_min_home');
                var maxInput = document.getElementById('gia_max_home');
                var minLabel = document.getElementById('min-price-home');
                var maxLabel = document.getElementById('max-price-home');

                var minVal = parseInt(minInput.value);
                var maxVal = parseInt(maxInput.value);

                noUiSlider.create(priceSlider, {
                    start: [minVal, maxVal],
                    connect: true,
                    range: {
                        'min': {{ $giaMin }},
                        'max': {{ $giaMax }}
                    },
                    step: 50000,
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
        .card-img-scale-wrapper {
            aspect-ratio: 3 / 4;
            overflow: hidden;
        }

        .card-img-scale-wrapper img.card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .btn-wishlist {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.35);
            border: none;
            z-index: 1200;
            pointer-events: auto;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-wishlist i {
            font-size: 14px;
            color: #fff;
        }

        .btn-wishlist i.text-danger {
            color: #dc3545 !important;
        }

        /* Thanh trượt giá trang chủ */
        .noUi-target {
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid #ddd;
            border-radius: 10px;
            height: 8px;
        }

        .noUi-connect {
            background: rgba(110, 110, 110, 0.6) !important;
        }

        .noUi-handle {
            width: 26px !important;
            height: 26px !important;
            border-radius: 50% !important;
            background: #fff !important;
            border: 3px solid #5E3EFF !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .noUi-horizontal .noUi-handle {
            top: -9px;
        }


        /* ========================
        Best deal (blog slider)
        ======================== */

        /* Giữ cho mọi thẻ card cùng chiều cao */
        .tiny-slider .card {
            height: 180px;
            /* hoặc 160–200 tùy bạn muốn cao thấp */
            display: flex;
            align-items: stretch;
        }

        /* Đảm bảo row con luôn full height */
        .tiny-slider .card .row {
            height: 100%;
        }

        /* Cột ảnh bên trái: fix tỉ lệ và không co giãn */
        .tiny-slider .card .col-sm-6:first-child {
            flex: 0 0 45%;
            max-width: 45%;
            height: 100%;
            overflow: hidden;
        }

        .tiny-slider .card .col-sm-6:first-child img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Cột nội dung bên phải */
        .tiny-slider .card .col-sm-6:last-child {
            flex: 0 0 55%;
            max-width: 55%;
            height: 100%;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Giới hạn dòng để nội dung không làm lệch chiều cao */
        .tiny-slider .card-title a {
            font-weight: 600;
            font-size: 15px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            /* 1 dòng tiêu đề */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .tiny-slider .card p {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            /* 2 dòng mô tả */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Meta cuối cùng nhỏ, nhạt */
        .tiny-slider .card small {
            font-size: 12px;
            color: #999;
        }
    </style>
@endpush
