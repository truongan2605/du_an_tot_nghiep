@extends('layouts.app')

@section('title', 'Chi tiet phong - Detail Room')

@section('content')
    <!-- Main Title + Info + Gallery START -->
    @php
        $gallery = $phong->images->values(); // all images
        $total = $gallery->count();
        $main = $gallery->get(0); // main big image (may be null)
        $thumbsAll = $gallery->slice(1); // images after main
        $thumbs = $thumbsAll->take(4); // up to 4 thumbs for grid
        $thumbCount = $thumbs->count();
        $remaining = max(0, $total - 1 - $thumbCount); // extra images beyond main + thumbs
    @endphp

    <section class="py-0 pt-sm-5">
        <div class="container position-relative">
            <!-- Title -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-lg-flex justify-content-lg-between mb-1">
                        <div class="mb-2 mb-lg-0">
                            <h1 class="fs-2">
                                {{ $phong->name ?? $phong->ma_phong }}
                                <small class="text-muted"> -
                                    {{ $phong->loaiPhong->ten ?? ($phong->loaiPhong->ten_loai ?? '—') }}</small>
                            </h1>
                            <p class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i> Floor
                                {{ $phong->tang->so_tang ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gallery-layout" style="--main-height:420px;">
                <div class="main-tile">
                    @if ($main)
                        <a href="{{ Storage::url($main->image_path) }}" class="glightbox"
                            data-gallery="room-{{ $phong->id }}">
                            <img src="{{ Storage::url($main->image_path) }}" alt="{{ $phong->name ?? $phong->ma_phong }}"
                                loading="lazy">
                        </a>
                    @else
                        <img src="{{ $phong->firstImageUrl() }}" alt="{{ $phong->name ?? $phong->ma_phong }}"
                            loading="lazy">
                    @endif
                </div>

                @if ($thumbCount > 0)
                    <div class="side-grid">
                        @foreach ($thumbs as $index => $t)
                            @php
                                $url = Storage::url($t->image_path);
                                $isLastVisible = $index === $thumbCount - 1;
                            @endphp

                            <div class="thumb-tile">
                                <a href="{{ $url }}" class="thumb-link glightbox"
                                    data-gallery="room-{{ $phong->id }}">
                                    <img src="{{ $url }}" alt="{{ $phong->name ?? $phong->ma_phong }}"
                                        loading="lazy">
                                </a>

                                @if ($isLastVisible && $remaining > 0)
                                    <a href="{{ $url }}" class="thumb-link overlay-anchor glightbox"
                                        data-gallery="room-{{ $phong->id }}"></a>
                                    <div class="overlay view-all">
                                        <div>
                                            <div class="fw-bold">View all</div>
                                            <div class="small">+{{ $remaining }} Images</div>
                                        </div>
                                    </div>

                                    @foreach ($gallery->slice(1 + $thumbCount) as $more)
                                        <a href="{{ Storage::url($more->image_path) }}" class="d-none glightbox"
                                            data-gallery="room-{{ $phong->id }}"></a>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
    <!-- Main Title + Gallery END -->

    <!-- =======================
            About hotel START -->
    <section class="pt-0">
        <div class="container" data-sticky-container>

            <div class="row g-4 g-xl-5">
                <!-- Content START -->
                <div class="col-xl-7 order-1">
                    <div class="vstack gap-5">

                        <!-- About hotel START -->
                        <div class="card bg-transparent">
                            <!-- Card header -->
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <h3 class="mb-0">About This Hotel</h3>
                            </div>

                            <!-- Card body START -->
                            <div class="card-body pt-4 p-0">
                                <h5 class="fw-light mb-4">Main Highlights</h5>

                                <!-- Highlights Icons -->
                                <div class="hstack gap-3 mb-3">
                                    <div class="icon-lg bg-light h5 rounded-2" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Free wifi">
                                        <i class="fa-solid fa-wifi"></i>
                                    </div>
                                    <div class="icon-lg bg-light h5 rounded-2" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Swimming Pool">
                                        <i class="fa-solid fa-swimming-pool"></i>
                                    </div>
                                    <div class="icon-lg bg-light h5 rounded-2" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Central AC">
                                        <i class="fa-solid fa-snowflake"></i>
                                    </div>
                                    <div class="icon-lg bg-light h5 rounded-2" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Free Service">
                                        <i class="fa-solid fa-concierge-bell"></i>
                                    </div>
                                </div>

                                <p class="mb-3">Demesne far-hearted suppose venture excited see had has. Dependent on so
                                    extremely delivered by. Yet no jokes worse her why. <b>Bed one supposing breakfast day
                                        fulfilled off depending questions.</b></p>
                                <p class="mb-0">Delivered dejection necessary objection do Mr prevailed. Mr feeling does
                                    chiefly cordial in do. Water timed folly right aware if oh truth. Large above be to
                                    means. Dashwood does provide stronger is.</p>

                                <div class="collapse" id="collapseContent">
                                    <p class="my-3">We focus a great deal on the understanding of behavioral psychology
                                        and influence triggers which are crucial for becoming a well rounded Digital
                                        Marketer. We understand that theory is important to build a solid foundation, we
                                        understand that theory alone isn't going to get the job done so that's why this
                                        rickets is packed with practical hands-on examples that you can follow step by step.
                                    </p>
                                    <p class="mb-0">Behavioral psychology and influence triggers which are crucial for
                                        becoming a well rounded Digital Marketer. We understand that theory is important to
                                        build a solid foundation, we understand that theory alone isn't going to get the job
                                        done so that's why this tickets is packed with practical hands-on examples that you
                                        can follow step by step.</p>
                                </div>
                                <a class="p-0 mb-4 mt-2 btn-more d-flex align-items-center collapsed"
                                    data-bs-toggle="collapse" href="#collapseContent" role="button" aria-expanded="false"
                                    aria-controls="collapseContent">
                                    See <span class="see-more ms-1">more</span><span class="see-less ms-1">less</span><i
                                        class="fa-solid fa-angle-down ms-2"></i>
                                </a>

                            </div>
                            <!-- Card body END -->
                        </div>
                        <!-- About hotel START -->

                        <!-- Amenities START -->
                        <div class="card bg-transparent">
                            <!-- Card header -->
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <h3 class="card-title mb-0">Amenities</h3>
                            </div>

                            <!-- Card body START -->
                            <div class="card-body pt-4 p-0">
                                <div class="row g-4">
                                    <!-- Activities -->
                                    <div class="col-sm-6">
                                        <h6><i class="fa-solid fa-biking me-2"></i>Activities</h6>
                                        <!-- List -->
                                        <ul class="list-group list-group-borderless mt-2 mb-0">
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Swimming pool
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Spa
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Kids' play area
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Gym
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Payment Method -->
                                    <div class="col-sm-6">
                                        <h6><i class="fa-solid fa-credit-card me-2"></i>Payment Method</h6>
                                        <!-- List -->
                                        <ul class="list-group list-group-borderless mt-2 mb-0">
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Credit card
                                                (Visa, Master card)
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Cash
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Debit Card
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Services -->
                                    <div class="col-sm-6">
                                        <h6><i class="fa-solid fa-concierge-bell me-2"></i>Services</h6>
                                        <!-- List -->
                                        <ul class="list-group list-group-borderless mt-2 mb-0">
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Dry cleaning
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Room Service
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Special service
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Waiting Area
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Secrete smoking
                                                area
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Concierge
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Laundry
                                                facilities
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Ironing Service
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Lift
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Safety & Security -->
                                    <div class="col-sm-6">
                                        <h6><i class="bi bi-shield-fill-check me-2"></i>Safety & Security</h6>
                                        <!-- List -->
                                        <ul class="list-group list-group-borderless mt-2 mb-4 mb-sm-5">
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Doctor on Call
                                            </li>
                                        </ul>

                                        <h6><i class="fa-solid fa-volume-up me-2"></i>Staff Language</h6>
                                        <!-- List -->
                                        <ul class="list-group list-group-borderless mt-2 mb-0">
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>English
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Spanish
                                            </li>
                                            <li class="list-group-item pb-0">
                                                <i class="fa-solid fa-check-circle text-success me-2"></i>Hindi
                                            </li>
                                        </ul>
                                    </div>

                                </div>
                            </div>
                            <!-- Card body END -->
                        </div>
                        <!-- Amenities END -->

                        <!-- Room START -->
                        <div class="card bg-transparent" id="room-options">
                            <!-- Card header -->
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <div class="d-sm-flex justify-content-sm-between align-items-center">
                                    <h3 class="mb-2 mb-sm-0">Room Options</h3>

                                    <div class="col-sm-4">
                                        <form class="form-control-bg-light">
                                            <select class="form-select form-select-sm js-choice border-0">
                                                <option value="">Select Option</option>
                                                <option>Recently search</option>
                                                <option>Most popular</option>
                                                <option>Top rated</option>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Card body START -->
                            <div class="card-body pt-4 p-0">
                                <div class="vstack gap-4">

                                    <!-- Room item START -->
                                    <div class="card shadow p-3">
                                        <div class="row g-4">
                                            <!-- Card img -->
                                            <div class="col-md-5 position-relative">

                                                <!-- Overlay item -->
                                                <div class="position-absolute top-0 start-0 z-index-1 mt-3 ms-4">
                                                    <div class="badge text-bg-danger">30% Off</div>
                                                </div>

                                                <!-- Slider START -->
                                                <div
                                                    class="tiny-slider arrow-round arrow-xs arrow-dark overflow-hidden rounded-2">
                                                    <div class="tiny-slider-inner" data-autoplay="true" data-arrow="true"
                                                        data-dots="false" data-items="1">
                                                        <!-- Image item -->
                                                        <div><img src="assets/images/category/hotel/4by3/04.jpg"
                                                                alt="Card image"></div>

                                                        <!-- Image item -->
                                                        <div><img src="assets/images/category/hotel/4by3/02.jpg"
                                                                alt="Card image"></div>

                                                        <!-- Image item -->
                                                        <div><img src="assets/images/category/hotel/4by3/03.jpg"
                                                                alt="Card image"></div>

                                                        <!-- Image item -->
                                                        <div><img src="assets/images/category/hotel/4by3/01.jpg"
                                                                alt="Card image"></div>
                                                    </div>
                                                </div>
                                                <!-- Slider END -->

                                                <!-- Button -->
                                                <a href="#"
                                                    class="btn btn-link text-decoration-underline p-0 mb-0 mt-1"
                                                    data-bs-toggle="modal" data-bs-target="#roomDetail"><i
                                                        class="bi bi-eye-fill me-1"></i>View more details</a>
                                            </div>

                                            <!-- Card body -->
                                            <div class="col-md-7">
                                                <div class="card-body d-flex flex-column h-100 p-0">

                                                    <!-- Title -->
                                                    <h5 class="card-title"><a href="#">Luxury Room with Balcony</a>
                                                    </h5>

                                                    <!-- Amenities -->
                                                    <ul class="nav nav-divider mb-2">
                                                        <li class="nav-item">Air Conditioning</li>
                                                        <li class="nav-item">Wifi</li>
                                                        <li class="nav-item">Kitchen</li>
                                                        <li class="nav-item">
                                                            <a href="#" class="mb-0 text-primary">More+</a>
                                                        </li>
                                                    </ul>

                                                    <p class="text-success mb-0">Free Cancellation till 7 Jan 2022</p>

                                                    <!-- Price and Button -->
                                                    <div
                                                        class="d-sm-flex justify-content-sm-between align-items-center mt-auto">
                                                        <!-- Button -->
                                                        <div class="d-flex align-items-center">
                                                            <h5 class="fw-bold mb-0 me-1">$750</h5>
                                                            <span class="mb-0 me-2">/day</span>
                                                            <span class="text-decoration-line-through mb-0">$1000</span>
                                                        </div>
                                                        <!-- Price -->
                                                        <div class="mt-3 mt-sm-0">
                                                            <a href="#" class="btn btn-sm btn-primary mb-0">Select
                                                                Room</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Room item END -->

                                    <!-- Room item START -->
                                    <div class="card shadow p-3">
                                        <div class="row g-4">
                                            <!-- Card img -->
                                            <div class="col-md-5 position-relative">

                                                <!-- Overlay item -->
                                                <div class="position-absolute top-0 start-0 z-index-1 mt-3 ms-4">
                                                    <div class="badge text-bg-danger">15% Off</div>
                                                </div>

                                                <!-- Slider START -->
                                                <div
                                                    class="tiny-slider arrow-round arrow-xs arrow-dark overflow-hidden rounded-2">
                                                    <div class="tiny-slider-inner" data-autoplay="true" data-arrow="true"
                                                        data-dots="false" data-items="1">
                                                        <!-- Image item -->
                                                        <div><img src="assets/images/category/hotel/4by3/03.jpg"
                                                                alt="Card image"></div>

                                                        <!-- Image item -->
                                                        <div><img src="assets/images/category/hotel/4by3/02.jpg"
                                                                alt="Card image"></div>

                                                        <!-- Image item -->
                                                        <div><img src="assets/images/category/hotel/4by3/04.jpg"
                                                                alt="Card image"></div>

                                                        <!-- Image item -->
                                                        <div><img src="assets/images/category/hotel/4by3/01.jpg"
                                                                alt="Card image"></div>
                                                    </div>
                                                </div>
                                                <!-- Slider END -->

                                                <!-- Button -->
                                                <a href="#"
                                                    class="btn btn-link text-decoration-underline p-0 mb-0 mt-1"
                                                    data-bs-toggle="modal" data-bs-target="#roomDetail"><i
                                                        class="bi bi-eye-fill me-1"></i>View more details</a>
                                            </div>

                                            <!-- Card body -->
                                            <div class="col-md-7">
                                                <div class="card-body d-flex flex-column p-0 h-100">

                                                    <!-- Title -->
                                                    <h5 class="card-title"><a href="#">Deluxe Pool View with
                                                            Breakfast</a></h5>

                                                    <!-- Amenities -->
                                                    <ul class="nav nav-divider mb-2">
                                                        <li class="nav-item">Air Conditioning</li>
                                                        <li class="nav-item">Wifi</li>
                                                        <li class="nav-item">Kitchen</li>
                                                        <li class="nav-item">
                                                            <a href="#" class="mb-0 text-primary">More+</a>
                                                        </li>
                                                    </ul>

                                                    <p class="text-danger mb-3">Non Refundable</p>

                                                    <!-- Price and Button -->
                                                    <div
                                                        class="d-sm-flex justify-content-sm-between align-items-center mt-auto">
                                                        <!-- Button -->
                                                        <div class="d-flex align-items-center">
                                                            <h5 class="fw-bold mb-0 me-1">$750</h5>
                                                            <span class="mb-0 me-2">/day</span>
                                                            <span class="text-decoration-line-through mb-0">$1000</span>
                                                        </div>
                                                        <!-- Price -->
                                                        <div class="mt-3 mt-sm-0">
                                                            <a href="#" class="btn btn-sm btn-primary mb-0">Select
                                                                Room</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Room item END -->
                                </div>
                            </div>
                            <!-- Card body END -->
                        </div>
                        <!-- Room END -->

                        <!-- Customer Review START -->
                        <div class="card bg-transparent">
                            <!-- Card header -->
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <h3 class="card-title mb-0">Customer Review</h3>
                            </div>

                            <!-- Card body START -->
                            <div class="card-body pt-4 p-0">
                                <!-- Progress bar and rating START -->
                                <div class="card bg-light p-4 mb-4">
                                    <div class="row g-4 align-items-center">
                                        <!-- Rating info -->
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <!-- Info -->
                                                <h2 class="mb-0">4.5</h2>
                                                <p class="mb-2">Based on 120 Reviews</p>
                                                <!-- Star -->
                                                <ul class="list-inline mb-0">
                                                    <li class="list-inline-item me-0"><i
                                                            class="fa-solid fa-star text-warning"></i></li>
                                                    <li class="list-inline-item me-0"><i
                                                            class="fa-solid fa-star text-warning"></i></li>
                                                    <li class="list-inline-item me-0"><i
                                                            class="fa-solid fa-star text-warning"></i></li>
                                                    <li class="list-inline-item me-0"><i
                                                            class="fa-solid fa-star text-warning"></i></li>
                                                    <li class="list-inline-item me-0"><i
                                                            class="fa-solid fa-star-half-alt text-warning"></i></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Progress-bar START -->
                                        <div class="col-md-8">
                                            <div class="card-body p-0">
                                                <div class="row gx-3 g-2 align-items-center">
                                                    <!-- Progress bar and Rating -->
                                                    <div class="col-9 col-sm-10">
                                                        <!-- Progress item -->
                                                        <div class="progress progress-sm bg-warning bg-opacity-15">
                                                            <div class="progress-bar bg-warning" role="progressbar"
                                                                style="width: 95%" aria-valuenow="95" aria-valuemin="0"
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Percentage -->
                                                    <div class="col-3 col-sm-2 text-end">
                                                        <span class="h6 fw-light mb-0">85%</span>
                                                    </div>

                                                    <!-- Progress bar and Rating -->
                                                    <div class="col-9 col-sm-10">
                                                        <!-- Progress item -->
                                                        <div class="progress progress-sm bg-warning bg-opacity-15">
                                                            <div class="progress-bar bg-warning" role="progressbar"
                                                                style="width: 75%" aria-valuenow="75" aria-valuemin="0"
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Percentage -->
                                                    <div class="col-3 col-sm-2 text-end">
                                                        <span class="h6 fw-light mb-0">75%</span>
                                                    </div>

                                                    <!-- Progress bar and Rating -->
                                                    <div class="col-9 col-sm-10">
                                                        <!-- Progress item -->
                                                        <div class="progress progress-sm bg-warning bg-opacity-15">
                                                            <div class="progress-bar bg-warning" role="progressbar"
                                                                style="width: 60%" aria-valuenow="60" aria-valuemin="0"
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Percentage -->
                                                    <div class="col-3 col-sm-2 text-end">
                                                        <span class="h6 fw-light mb-0">60%</span>
                                                    </div>

                                                    <!-- Progress bar and Rating -->
                                                    <div class="col-9 col-sm-10">
                                                        <!-- Progress item -->
                                                        <div class="progress progress-sm bg-warning bg-opacity-15">
                                                            <div class="progress-bar bg-warning" role="progressbar"
                                                                style="width: 35%" aria-valuenow="35" aria-valuemin="0"
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Percentage -->
                                                    <div class="col-3 col-sm-2 text-end">
                                                        <span class="h6 fw-light mb-0">35%</span>
                                                    </div>

                                                    <!-- Progress bar and Rating -->
                                                    <div class="col-9 col-sm-10">
                                                        <!-- Progress item -->
                                                        <div class="progress progress-sm bg-warning bg-opacity-15">
                                                            <div class="progress-bar bg-warning" role="progressbar"
                                                                style="width: 20%" aria-valuenow="20" aria-valuemin="0"
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Percentage -->
                                                    <div class="col-3 col-sm-2 text-end">
                                                        <span class="h6 fw-light mb-0">15%</span>
                                                    </div>
                                                </div> <!-- Row END -->
                                            </div>
                                        </div>
                                        <!-- Progress-bar END -->

                                    </div>
                                </div>
                                <!-- Progress bar and rating END -->

                                <!-- Leave review START -->
                                <form class="mb-5">
                                    <!-- Rating -->
                                    <div class="form-control-bg-light mb-3">
                                        <select class="form-select js-choice">
                                            <option selected="">★★★★★ (5/5)</option>
                                            <option>★★★★☆ (4/5)</option>
                                            <option>★★★☆☆ (3/5)</option>
                                            <option>★★☆☆☆ (2/5)</option>
                                            <option>★☆☆☆☆ (1/5)</option>
                                        </select>
                                    </div>
                                    <!-- Message -->
                                    <div class="form-control-bg-light mb-3">
                                        <textarea class="form-control" id="exampleFormControlTextarea1" placeholder="Your review" rows="3"></textarea>
                                    </div>
                                    <!-- Button -->
                                    <button type="submit" class="btn btn-lg btn-primary mb-0">Post review <i
                                            class="bi fa-fw bi-arrow-right ms-2"></i></button>
                                </form>
                                <!-- Leave review END -->

                                <!-- Review item START -->
                                <div class="d-md-flex my-4">
                                    <!-- Avatar -->
                                    <div class="avatar avatar-lg me-3 flex-shrink-0">
                                        <img class="avatar-img rounded-circle" src="assets/images/avatar/09.jpg"
                                            alt="avatar">
                                    </div>
                                    <!-- Text -->
                                    <div>
                                        <div class="d-flex justify-content-between mt-1 mt-md-0">
                                            <div>
                                                <h6 class="me-3 mb-0">Jacqueline Miller</h6>
                                                <!-- Info -->
                                                <ul class="nav nav-divider small mb-2">
                                                    <li class="nav-item">Stayed 13 Nov 2022</li>
                                                    <li class="nav-item">4 Reviews written</li>
                                                </ul>
                                            </div>
                                            <!-- Review star -->
                                            <div class="icon-md rounded text-bg-warning fs-6">4.5</div>
                                        </div>

                                        <p class="mb-2">Handsome met debating sir dwelling age material. As style lived
                                            he worse dried. Offered related so visitors we private removed. Moderate do
                                            subjects to distance. </p>

                                        <!-- Images -->
                                        <div class="row g-4">
                                            <div class="col-4 col-sm-3 col-lg-2">
                                                <img src="assets/images/category/hotel/4by3/07.jpg" class="rounded"
                                                    alt="">
                                            </div>
                                            <div class="col-4 col-sm-3 col-lg-2">
                                                <img src="assets/images/category/hotel/4by3/08.jpg" class="rounded"
                                                    alt="">
                                            </div>
                                            <div class="col-4 col-sm-3 col-lg-2">
                                                <img src="assets/images/category/hotel/4by3/05.jpg" class="rounded"
                                                    alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Child review START -->
                                <div class="my-4 ps-2 ps-md-3">
                                    <div class="d-md-flex p-3 bg-light rounded-3">
                                        <img class="avatar avatar-sm rounded-circle me-3"
                                            src="assets/images/avatar/02.jpg" alt="avatar">
                                        <div class="mt-2 mt-md-0">
                                            <h6 class="mb-1">Manager</h6>
                                            <p class="mb-0">But discretion frequently sir she instruments unaffected
                                                admiration everything. </p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Child review END -->

                                <!-- Divider -->
                                <hr>
                                <!-- Review item END -->

                                <!-- Review item START -->
                                <div class="d-md-flex my-4">
                                    <!-- Avatar -->
                                    <div class="avatar avatar-lg me-3 flex-shrink-0">
                                        <img class="avatar-img rounded-circle" src="assets/images/avatar/08.jpg"
                                            alt="avatar">
                                    </div>
                                    <!-- Text -->
                                    <div>
                                        <div class="d-flex justify-content-between mt-1 mt-md-0">
                                            <div>
                                                <h6 class="me-3 mb-0">Dennis Barrett</h6>
                                                <!-- Info -->
                                                <ul class="nav nav-divider small mb-2">
                                                    <li class="nav-item">Stayed 02 Nov 2022</li>
                                                    <li class="nav-item">2 Reviews written</li>
                                                </ul>
                                            </div>
                                            <!-- Review star -->
                                            <div class="icon-md rounded text-bg-warning fs-6">4.0</div>
                                        </div>

                                        <p class="mb-0">Delivered dejection necessary objection do Mr prevailed. Mr
                                            feeling does chiefly cordial in do. Water timed folly right aware if oh truth.
                                            Large above be to means. Dashwood does provide stronger is.</p>
                                    </div>
                                </div>

                                <!-- Divider -->
                                <hr>
                                <!-- Review item END -->

                                <!-- Button -->
                                <div class="text-center">
                                    <a href="#" class="btn btn-primary-soft mb-0">Load more</a>
                                </div>
                            </div>
                            <!-- Card body END -->
                        </div>
                        <!-- Customer Review END -->

                        <!-- Hotel Policies START -->
                        <div class="card bg-transparent">
                            <!-- Card header -->
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <h3 class="mb-0">Hotel Policies</h3>
                            </div>

                            <!-- Card body START -->
                            <div class="card-body pt-4 p-0">
                                <!-- List -->
                                <ul class="list-group list-group-borderless mb-2">
                                    <li class="list-group-item d-flex">
                                        <i class="bi bi-check-circle-fill me-2"></i>This is a family farmhouse, hence we
                                        request you to not indulge.
                                    </li>
                                    <li class="list-group-item d-flex">
                                        <i class="bi bi-check-circle-fill me-2"></i>Drinking and smoking within controlled
                                        limits are permitted at the farmhouse but please do not create a mess or ruckus at
                                        the house.
                                    </li>
                                    <li class="list-group-item d-flex">
                                        <i class="bi bi-check-circle-fill me-2"></i>Drugs and intoxicating illegal products
                                        are banned and not to be brought to the house or consumed.
                                    </li>
                                    <li class="list-group-item d-flex">
                                        <i class="bi bi-x-circle-fill me-2"></i>For any update, the customer shall pay
                                        applicable cancellation/modification charges.
                                    </li>
                                </ul>

                                <!-- List -->
                                <ul class="list-group list-group-borderless mb-2">
                                    <li class="list-group-item h6 fw-light d-flex mb-0">
                                        <i class="bi bi-arrow-right me-2"></i>Check-in: 1:00 pm - 9:00 pm
                                    </li>
                                    <li class="list-group-item h6 fw-light d-flex mb-0">
                                        <i class="bi bi-arrow-right me-2"></i>Check out: 11:00 am
                                    </li>
                                    <li class="list-group-item h6 fw-light d-flex mb-0">
                                        <i class="bi bi-arrow-right me-2"></i>Self-check-in with building staff
                                    </li>
                                    <li class="list-group-item h6 fw-light d-flex mb-0">
                                        <i class="bi bi-arrow-right me-2"></i>No pets
                                    </li>
                                    <li class="list-group-item h6 fw-light d-flex mb-0">
                                        <i class="bi bi-arrow-right me-2"></i>No parties or events
                                    </li>
                                    <li class="list-group-item h6 fw-light d-flex mb-0">
                                        <i class="bi bi-arrow-right me-2"></i>Smoking is allowed
                                    </li>
                                </ul>

                            </div>
                            <!-- Card body END -->
                        </div>
                        <!-- Hotel Policies START -->
                    </div>
                </div>
                <!-- Content END -->

                <!-- Right side content START -->
                <aside class="col-xl-5 order-xl-2">
                    <div data-sticky data-margin-top="100" data-sticky-for="1199">
                        <!-- Book now START -->
                        <div class="card card-body border">

                            <!-- Title -->
                            <div class="d-sm-flex justify-content-sm-between align-items-center mb-3">
                                <div>
                                    <span>Price Start at</span>
                                    <h4 class="card-title mb-0">$3,500</h4>
                                </div>
                                <div>
                                    <h6 class="fw-normal mb-0">1 room per night</h6>
                                    <small>+ $285 taxes & fees</small>
                                </div>
                            </div>

                            <!-- Rating -->
                            <ul class="list-inline mb-2">
                                <li class="list-inline-item me-1 h6 fw-light mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>4.5</li>
                                <li class="list-inline-item me-0 small"><i class="fa-solid fa-star text-warning"></i></li>
                                <li class="list-inline-item me-0 small"><i class="fa-solid fa-star text-warning"></i></li>
                                <li class="list-inline-item me-0 small"><i class="fa-solid fa-star text-warning"></i></li>
                                <li class="list-inline-item me-0 small"><i class="fa-solid fa-star text-warning"></i></li>
                                <li class="list-inline-item me-0 small"><i
                                        class="fa-solid fa-star-half-alt text-warning"></i></li>
                            </ul>

                            <p class="h6 fw-light mb-4"><i class="bi bi-arrow-right me-2"></i>Free breakfast available</p>

                            <!-- Button -->
                            <div class="d-grid">
                                <a href="#room-options" class="btn btn-lg btn-primary-soft mb-0">View 10 Rooms Options</a>
                            </div>
                        </div>
                        <!-- Book now END -->

                        <!-- Best deal START -->
                        <div class="mt-4 d-none d-xl-block">
                            <h4>Today's Best Deal</h4>

                            <div class="card shadow rounded-3 overflow-hidden">
                                <div class="row g-0 align-items-center">
                                    <!-- Image -->
                                    <div class="col-sm-6 col-md-12 col-lg-6">
                                        <img src="assets/images/offer/04.jpg" class="card-img rounded-0" alt="">
                                    </div>

                                    <!-- Title and content -->
                                    <div class="col-sm-6 col-md-12 col-lg-6">
                                        <div class="card-body p-3">
                                            <h6 class="card-title"><a href="offer-detail.html"
                                                    class="stretched-link">Travel Plan</a></h6>
                                            <p class="mb-0">Get up to $10,000 for lifetime limits</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Best deal END -->
                    </div>
                </aside>
                <!-- Right side content END -->
            </div> <!-- Row END -->
        </div>
    </section>
    <!-- =======================
                                        About hotel END -->


    <!-- Map modal START -->
    <div class="modal fade" id="mapmodal" tabindex="-1" aria-labelledby="mapmodalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <!-- Title -->
                <div class="modal-header">
                    <h5 class="modal-title" id="mapmodalLabel">View Our Hotel Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Map -->
                <div class="modal-body p-0">
                    <iframe class="w-100" height="400"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.9663095343008!2d-74.00425878428698!3d40.74076684379132!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259bf5c1654f3%3A0xc80f9cfce5383d5d!2sGoogle!5e0!3m2!1sen!2sin!4v1586000412513!5m2!1sen!2sin"
                        style="border:0;" aria-hidden="false" tabindex="0"></iframe>
                </div>
                <!-- Button -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-primary mb-0"><i
                            class="bi fa-fw bi-pin-map-fill me-2"></i>View In Google Map</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Map modal END -->

    <!-- Room modal START -->
    <div class="modal fade" id="roomDetail" tabindex="-1" aria-labelledby="roomDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-0">

                <!-- Title -->
                <div class="modal-header p-3">
                    <h5 class="modal-title mb-0" id="roomDetailLabel">Room detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal body -->
                <div class="modal-body p-0">

                    <!-- Card START -->
                    <div class="card bg-transparent p-3">
                        <!-- Slider START -->
                        <div class="tiny-slider arrow-round arrow-dark overflow-hidden rounded-2">
                            <div class="tiny-slider-inner rounded-2 overflow-hidden" data-autoplay="true"
                                data-arrow="true" data-dots="false" data-items="1">
                                <!-- Image item -->
                                <div> <img src="assets/images/gallery/16.jpg" class="rounded-2" alt="Card image"></div>

                                <!-- Image item -->
                                <div> <img src="assets/images/gallery/15.jpg" class="rounded-2" alt="Card image"> </div>

                                <!-- Image item -->
                                <div> <img src="assets/images/gallery/13.jpg" class="rounded-2" alt="Card image"> </div>

                                <!-- Image item -->
                                <div> <img src="assets/images/gallery/12.jpg" class="rounded-2" alt="Card image"> </div>
                            </div>
                        </div>
                        <!-- Slider END -->

                        <!-- Card header -->
                        <div class="card-header bg-transparent pb-0">
                            <h3 class="card-title mb-0">Deluxe Pool View</h3>
                        </div>

                        <!-- Card body START -->
                        <div class="card-body">
                            <!-- Content -->
                            <p>Club rooms are well furnished with air conditioner, 32 inch LCD television and a mini bar.
                                They have attached bathroom with showerhead and hair dryer and 24 hours supply of hot and
                                cold running water. Complimentary wireless internet access is available. Additional
                                amenities include bottled water, a safe and a desk.</p>

                            <div class="row">
                                <h5 class="mb-0">Amenities</h5>

                                <!-- List -->
                                <div class="col-md-6">
                                    <!-- List -->
                                    <ul class="list-group list-group-borderless mt-2 mb-0">
                                        <li class="list-group-item d-flex mb-0">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i><span
                                                class="h6 fw-light mb-0">Swimming pool</span>
                                        </li>
                                        <li class="list-group-item d-flex mb-0">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i><span
                                                class="h6 fw-light mb-0">Spa</span>
                                        </li>
                                        <li class="list-group-item d-flex mb-0">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i><span
                                                class="h6 fw-light mb-0">Kids play area.</span>
                                        </li>
                                        <li class="list-group-item d-flex mb-0">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i><span
                                                class="h6 fw-light mb-0">Gym</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- List -->
                                <div class="col-md-6">
                                    <!-- List -->
                                    <ul class="list-group list-group-borderless mt-2 mb-0">
                                        <li class="list-group-item d-flex mb-0">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i><span
                                                class="h6 fw-light mb-0">TV</span>
                                        </li>
                                        <li class="list-group-item d-flex mb-0">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i><span
                                                class="h6 fw-light mb-0">Mirror</span>
                                        </li>
                                        <li class="list-group-item d-flex mb-0">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i><span
                                                class="h6 fw-light mb-0">AC</span>
                                        </li>
                                        <li class="list-group-item d-flex mb-0">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i><span
                                                class="h6 fw-light mb-0">Slippers</span>
                                        </li>
                                    </ul>
                                </div>
                            </div> <!-- Row END -->
                        </div>
                        <!-- Card body END -->
                    </div>
                    <!-- Card END -->
                </div>
            </div>
        </div>
    </div>
    <!-- Room modal END -->

    <!-- Back to top -->
    <div class="back-top"></div>

    </section>
    <!-- =======================
            Download app END -->

    <!-- **************** MAIN CONTENT END **************** -->

@endsection

@push('scripts')
    <script src="{{ asset('template/stackbros/assets/vendor/glightbox/js/glightbox.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Init GLightbox on elements with class .glightbox
            if (typeof GLightbox !== 'undefined') {
                GLightbox({
                    selector: '.glightbox',
                    touchNavigation: true,
                    loop: true,
                    autoplayVideos: false
                });
            }
        });
    </script>
@endpush


@push('styles')
    <style>
        .gallery-layout {
            --main-height: 420px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            grid-template-rows: calc(var(--main-height) / 2) calc(var(--main-height) / 2);
            gap: 1rem;
            align-items: stretch;
        }

        .main-tile {
            grid-row: 1 / span 2;
            width: 100%;
            height: var(--main-height);
            overflow: hidden;
            border-radius: 0.6rem;
            background: #f3f3f3;
        }

        .main-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 0.6rem;
        }

        .info-tile {
            grid-column: 2;
            grid-row: 1;
            padding: 1rem;
            display: flex;
            align-items: center;
            background: transparent;
        }

        /* Side grid (right bottom) - thumbs */
        .side-grid {
            grid-column: 2;
            grid-row: 2;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.8rem;
            align-items: stretch;
            margin-bottom: 20px
        }

        .thumb-tile {
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
            background: #eee;
            min-height: 0;
        }

        .thumb-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        /* overlay for "view all" */
        .overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            pointer-events: none;
            background: linear-gradient(0deg, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.15));
            text-align: center;
            border-radius: 0.5rem;
        }

        /* last anchor covers tile so clicks open glightbox */
        .thumb-link,
        .overlay-anchor {
            display: block;
            width: 100%;
            height: 100%;
        }

        /* Responsive: stack on small screens */
        @media (max-width: 991px) {
            .gallery-layout {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }

            .main-tile {
                grid-row: auto;
                height: auto;
                aspect-ratio: 16/9;
            }

            .info-tile {
                grid-column: 1;
                grid-row: auto;
                padding: .75rem;
            }

            .side-grid {
                grid-column: 1;
                grid-row: auto;
                grid-template-columns: repeat(4, 1fr);
                grid-auto-rows: 100px;
                overflow-x: auto;
                gap: 0.6rem;
            }

            .thumb-tile {
                min-width: 110px;
                flex-shrink: 0;
            }
        }
    </style>
@endpush
