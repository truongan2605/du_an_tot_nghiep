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
                    <h1 class="mb-4 mt-md-5 display-5">Find the top
                        <span class="position-relative z-index-9">Hotels nearby.
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
                    <p class="mb-4">We bring you not only a stay option, but an experience in your budget to enjoy the
                        luxury.</p>

                    <!-- Buttons -->
                    <div class="hstack gap-4 flex-wrap align-items-center">
                        <!-- Button -->
                        <a href="#" class="btn btn-primary-soft mb-0">Discover Now</a>
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
                                <h6 class="fw-normal small mb-0">Watch our story</h6>
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
                            <h6 class="text-dark fw-light small mb-0">Guide Supports</h6>
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
            <div class="row">
                <div class="col-xl-10 position-relative mt-n3 mt-xl-n9">
                    <!-- Title -->
                    <h6 class="d-none d-xl-block mb-3">Check Availability</h6>

                    <!-- Booking from START -->
                    <form class="card shadow rounded-3 position-relative p-4 pe-md-5 pb-5 pb-md-4">
                        <div class="row g-4 align-items-center">
                            <!-- Location -->
                            <div class="col-lg-4">
                                <div class="form-control-border form-control-transparent form-fs-md d-flex">
                                    <!-- Icon -->
                                    <i class="bi bi-geo-alt fs-3 me-2 mt-2"></i>
                                    <!-- Select input -->
                                    <div class="flex-grow-1">
                                        <label class="form-label">Location</label>
                                        <select class="form-select js-choice" data-search-enabled="true">
                                            <option value="">Select location</option>
                                            <option>San Jacinto, USA</option>
                                            <option>North Dakota, Canada</option>
                                            <option>West Virginia, Paris</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Check in -->
                            <div class="col-lg-4">
                                <div class="d-flex">
                                    <!-- Icon -->
                                    <i class="bi bi-calendar fs-3 me-2 mt-2"></i>
                                    <!-- Date input -->
                                    <div class="form-control-border form-control-transparent form-fs-md">
                                        <label class="form-label">Check in - out</label>
                                        <input type="text" class="form-control flatpickr" data-mode="range"
                                            placeholder="Select date" value="19 Sep to 28 Sep">
                                    </div>
                                </div>
                            </div>

                            <!-- Guest -->
                            <div class="col-lg-4">
                                <div class="form-control-border form-control-transparent form-fs-md d-flex">
                                    <!-- Icon -->
                                    <i class="bi bi-person fs-3 me-2 mt-2"></i>
                                    <!-- Dropdown input -->
                                    <div class="w-100">
                                        <label class="form-label">Guests & rooms</label>
                                        <div class="dropdown guest-selector me-2">
                                            <input type="text" class="form-guest-selector form-control selection-result"
                                                value="2 Guests 1 Room" data-bs-auto-close="outside"
                                                data-bs-toggle="dropdown">

                                            <!-- dropdown items -->
                                            <ul class="dropdown-menu guest-selector-dropdown">
                                                <!-- Adult -->
                                                <li class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-0">Adults</h6>
                                                        <small>Ages 13 or above</small>
                                                    </div>

                                                    <div class="hstack gap-1 align-items-center">
                                                        <button type="button" class="btn btn-link adult-remove p-0 mb-0"><i
                                                                class="bi bi-dash-circle fs-5 fa-fw"></i></button>
                                                        <h6 class="guest-selector-count mb-0 adults">2</h6>
                                                        <button type="button" class="btn btn-link adult-add p-0 mb-0"><i
                                                                class="bi bi-plus-circle fs-5 fa-fw"></i></button>
                                                    </div>
                                                </li>

                                                <!-- Divider -->
                                                <li class="dropdown-divider"></li>

                                                <!-- Child -->
                                                <li class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-0">Child</h6>
                                                        <small>Ages 13 below</small>
                                                    </div>

                                                    <div class="hstack gap-1 align-items-center">
                                                        <button type="button" class="btn btn-link child-remove p-0 mb-0"><i
                                                                class="bi bi-dash-circle fs-5 fa-fw"></i></button>
                                                        <h6 class="guest-selector-count mb-0 child">0</h6>
                                                        <button type="button" class="btn btn-link child-add p-0 mb-0"><i
                                                                class="bi bi-plus-circle fs-5 fa-fw"></i></button>
                                                    </div>
                                                </li>

                                                <!-- Divider -->
                                                <li class="dropdown-divider"></li>

                                                <!-- Rooms -->
                                                <li class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-0">Rooms</h6>
                                                        <small>Max room 8</small>
                                                    </div>

                                                    <div class="hstack gap-1 align-items-center">
                                                        <button type="button"
                                                            class="btn btn-link room-remove p-0 mb-0"><i
                                                                class="bi bi-dash-circle fs-5 fa-fw"></i></button>
                                                        <h6 class="guest-selector-count mb-0 rooms">1</h6>
                                                        <button type="button" class="btn btn-link room-add p-0 mb-0"><i
                                                                class="bi bi-plus-circle fs-5 fa-fw"></i></button>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Button -->
                        <div class="btn-position-md-middle">
                            <a class="icon-lg btn btn-round btn-primary mb-0" href="#"><i
                                    class="bi bi-search fa-fw"></i></a>
                        </div>
                    </form>
                    <!-- Booking from END -->
                </div>
            </div>
            <!-- Search END -->
        </div>
    </section>
    <!-- =======================
    Main Banner END -->

    <!-- =======================
    Best deal START -->
    <section class="pb-2 pb-lg-5">
        <div class="container">
            <!-- Slider START -->
            <div class="tiny-slider arrow-round arrow-blur arrow-hover">
                <div class="tiny-slider-inner" data-autoplay="true" data-arrow="true" data-edge="2" data-dots="false"
                    data-items-xl="3" data-items-lg="2" data-items-md="1">
                    <!-- Slider item -->
                    <div>
                        <div class="card border rounded-3 overflow-hidden">
                            <div class="row g-0 align-items-center">
                                <!-- Image -->
                                <div class="col-sm-6">
                                    <img src="{{ asset('template/stackbros/assets/images/offer/01.jpg') }}"
                                        class="card-img rounded-0" alt="">
                                </div>

                                <!-- Title and content -->
                                <div class="col-sm-6">
                                    <div class="card-body px-3">
                                        <h6 class="card-title"><a
                                                href="{{ asset('template/stackbros/offer-detail.html') }}"
                                                class="stretched-link">Daily 50 Lucky Winners get a Free Stay</a></h6>
                                        <p class="mb-0">Valid till: 15 Nov</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slider item -->
                    <div>
                        <div class="card border rounded-3 overflow-hidden">
                            <div class="row g-0 align-items-center">
                                <!-- Image -->
                                <div class="col-sm-6">
                                    <img src="{{ asset('template/stackbros/assets/images/offer/04.jpg') }}"
                                        class="card-img rounded-0" alt="">
                                </div>

                                <!-- Title and content -->
                                <div class="col-sm-6">
                                    <div class="card-body px-3">
                                        <h6 class="card-title"><a
                                                href="{{ asset('template/stackbros/offer-detail.html') }}"
                                                class="stretched-link">Up to 60% OFF</a></h6>
                                        <p class="mb-0">On Hotel Bookings Online</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slider item -->
                    <div>
                        <div class="card border rounded-3 overflow-hidden">
                            <div class="row g-0 align-items-center">
                                <!-- Image -->
                                <div class="col-sm-6">
                                    <img src="{{ asset('template/stackbros/assets/images/offer/03.jpg') }}"
                                        class="card-img rounded-0" alt="">
                                </div>

                                <!-- Title and content -->
                                <div class="col-sm-6">
                                    <div class="card-body px-3">
                                        <h6 class="card-title"><a
                                                href="{{ asset('template/stackbros/offer-detail.html') }}"
                                                class="stretched-link">Book & Enjoy</a></h6>
                                        <p class="mb-0">20% Off on the best available room rate</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slider item -->
                    <div>
                        <div class="card border rounded-3 overflow-hidden">
                            <div class="row g-0 align-items-center">
                                <!-- Image -->
                                <div class="col-sm-6">
                                    <img src="{{ asset('template/stackbros/assets/images/offer/02.jpg') }}"
                                        class="card-img rounded-0" alt="">
                                </div>

                                <!-- Title and content -->
                                <div class="col-sm-6">
                                    <div class="card-body px-3">
                                        <h6 class="card-title"><a
                                                href="{{ asset('template/stackbros/offer-detail.html') }}"
                                                class="stretched-link">Hot Summer Nights</a></h6>
                                        <p class="mb-0">Up to 3 nights free!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slider END -->
        </div>
    </section>
    <!-- =======================
    Best deal END -->

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
                    <h2 class="mb-3 mb-lg-5">The Best Holidays Start Here!</h2>
                    <p class="mb-3 mb-lg-5">Book your hotel with us and don't forget to grab an awesome hotel deal to save
                        massive on your stay.</p>

                    <!-- Features START -->
                    <div class="row g-4">
                        <!-- Item -->
                        <div class="col-sm-6">
                            <div class="icon-lg bg-success bg-opacity-10 text-success rounded-circle"><i
                                    class="fa-solid fa-utensils"></i></div>
                            <h5 class="mt-2">Quality Food</h5>
                            <p class="mb-0">Departure defective arranging rapturous did. Conduct denied adding worthy
                                little.</p>
                        </div>
                        <!-- Item -->
                        <div class="col-sm-6">
                            <div class="icon-lg bg-danger bg-opacity-10 text-danger rounded-circle"><i
                                    class="bi bi-stopwatch-fill"></i></div>
                            <h5 class="mt-2">Quick Services</h5>
                            <p class="mb-0">Supposing so be resolving breakfast am or perfectly. </p>
                        </div>
                        <!-- Item -->
                        <div class="col-sm-6">
                            <div class="icon-lg bg-orange bg-opacity-10 text-orange rounded-circle"><i
                                    class="bi bi-shield-fill-check"></i></div>
                            <h5 class="mt-2">High Security</h5>
                            <p class="mb-0">Arranging rapturous did believe him all had supported. </p>
                        </div>
                        <!-- Item -->
                        <div class="col-sm-6">
                            <div class="icon-lg bg-info bg-opacity-10 text-info rounded-circle"><i
                                    class="bi bi-lightning-fill"></i></div>
                            <h5 class="mt-2">24 Hours Alert</h5>
                            <p class="mb-0">Rapturous did believe him all had supported.</p>
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
                    <h2 class="mb-0">Featured Hotels</h2>
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
                                        {{ number_format($phong->gia_mac_dinh, 0, '.', ',') }} VND
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
                            <h5>24x7 Help</h5>
                            <p class="mb-0">If we fall short of your expectation in any way, let us know</p>
                        </div>
                    </div>
                </div>

                <!-- Trust -->
                <div class="col-md-6 col-xxl-4">
                    <div class="bg-body d-flex rounded-3 h-100 p-4">
                        <h3><i class="fa-solid fa-hand-holding-usd"></i></h3>
                        <div class="ms-3">
                            <h5>Payment Trust</h5>
                            <p class="mb-0">All refunds come with no questions asked guarantee</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xxl-4">
                    <div class="bg-body d-flex rounded-3 h-100 p-4">
                        <h3><i class="fa-solid fa-shield"></i></i></h3>
                        <div class="ms-3">
                            <h5>Privacy policy</h5>
                            <p class="mb-0">Clear privacy policy ensures customer information security</p>
                        </div>
                    </div>
                </div>

                <!-- Download app -->

            </div>
        </div>
    </section>
    <!-- =======================
    Download app END -->

<!-- **************** MAIN CONTENT END **************** -->

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
</style>
@endpush
