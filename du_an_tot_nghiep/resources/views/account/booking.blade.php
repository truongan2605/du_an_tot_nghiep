@extends('layouts.app')

@section('title', 'My Wishlist')

@section('content')
    <main>

    <!-- Page banner START -->
        <section class="py-0">
            <div class="container">
                <!-- Card START -->
                <div class="card bg-light overflow-hidden px-sm-5">
                    <div class="row align-items-center g-4">

                        <!-- Content -->
                        <div class="col-sm-9">
                            <div class="card-body">
                                <!-- Breadcrumb -->
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb breadcrumb-dots mb-0">
                                        <li class="breadcrumb-item"><a href="index.html"><i class="bi bi-house me-1"></i>
                                                Home</a></li>
                                        <li class="breadcrumb-item">Hotel detail</li>
                                        <li class="breadcrumb-item active">Booking</li>
                                    </ol>
                                </nav>
                                <!-- Title -->
                                <h1 class="m-0 h2 card-title">Review your Booking</h1>
                            </div>
                        </div>

                        <!-- Image -->
                        <div class="col-sm-3 text-end d-none d-sm-block">
                            <img src="assets/images/element/17.svg" class="mb-n4" alt="">
                        </div>
                    </div>
                </div>
                <!-- Card END -->
            </div>
        </section>
        <!-- Page banner END -->

        <!-- Page content START -->
        <section>
            <div class="container">
                <div class="row g-4 g-lg-5">

                    <!-- Left side content START -->
                    <div class="col-xl-8">
                        <div class="vstack gap-5">
                            <!-- Hotel information START -->
                            <div class="card shadow">
                                <!-- Card header -->
                                <div class="card-header p-4 border-bottom">
                                    <!-- Title -->
                                    <h3 class="mb-0"><i class="fa-solid fa-hotel me-2"></i>Hotel Information</h3>
                                </div>

                                <!-- Card body START -->
                                <div class="card-body p-4">
                                    <!-- Card list START -->
                                    <div class="card mb-4">
                                        <div class="row align-items-center">
                                            <!-- Image -->
                                            <div class="col-sm-6 col-md-3">
                                                <img src="assets/images/category/hotel/4by3/02.jpg" class="card-img"
                                                    alt="">
                                            </div>

                                            <!-- Card Body -->
                                            <div class="col-sm-6 col-md-9">
                                                <div class="card-body pt-3 pt-sm-0 p-0">
                                                    <!-- Title -->
                                                    <h5 class="card-title"><a href="#">Pride moon Village Resort &
                                                            Spa</a></h5>
                                                    <p class="small mb-2"><i class="bi bi-geo-alt me-2"></i>5855 W Century
                                                        Blvd, Los Angeles - 90045</p>

                                                    <!-- Rating star -->
                                                    <ul class="list-inline mb-0">
                                                        <li class="list-inline-item me-0 small"><i
                                                                class="fa-solid fa-star text-warning"></i></li>
                                                        <li class="list-inline-item me-0 small"><i
                                                                class="fa-solid fa-star text-warning"></i></li>
                                                        <li class="list-inline-item me-0 small"><i
                                                                class="fa-solid fa-star text-warning"></i></li>
                                                        <li class="list-inline-item me-0 small"><i
                                                                class="fa-solid fa-star text-warning"></i></li>
                                                        <li class="list-inline-item me-0 small"><i
                                                                class="fa-solid fa-star-half-alt text-warning"></i></li>
                                                        <li class="list-inline-item ms-2 h6 small fw-bold mb-0">4.5/5.0</li>
                                                    </ul>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <!-- Card list END -->

                                    <!-- Information START -->
                                    <div class="row g-4">
                                        <!-- Item -->
                                        <div class="col-lg-4">
                                            <div class="bg-light py-3 px-4 rounded-3">
                                                <h6 class="fw-light small mb-1">Check-in</h6>
                                                <h5 class="mb-1">4 March 2022</h5>
                                                <small><i class="bi bi-alarm me-1"></i>12:30 pm</small>
                                            </div>
                                        </div>

                                        <!-- Item -->
                                        <div class="col-lg-4">
                                            <div class="bg-light py-3 px-4 rounded-3">
                                                <h6 class="fw-light small mb-1">Check out</h6>
                                                <h5 class="mb-1">8 March 2022</h5>
                                                <small><i class="bi bi-alarm me-1"></i>4:30 pm</small>
                                            </div>
                                        </div>

                                        <!-- Item -->
                                        <div class="col-lg-4">
                                            <div class="bg-light py-3 px-4 rounded-3">
                                                <h6 class="fw-light small mb-1">Rooms & Guests</h6>
                                                <h5 class="mb-1">2 G - 1 R</h5>
                                                <small><i class="bi bi-brightness-high me-1"></i>3 Nights - 4 Days</small>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Information END -->

                                    <!-- Card START -->
                                    <div class="card border mt-4">
                                        <!-- Card header -->
                                        <div class="card-header border-bottom d-md-flex justify-content-md-between">
                                            <h5 class="card-title mb-0">Deluxe Pool View with Breakfast</h5>
                                            <a href="#" class="btn btn-link p-0 mb-0">View Cancellation Policy</a>
                                        </div>

                                        <!-- Card body -->
                                        <div class="card-body">
                                            <h6>Price Included</h6>
                                            <!-- List -->
                                            <ul class="list-group list-group-borderless mb-0">
                                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                                        class="bi bi-patch-check-fill text-success me-2"></i>Free Breakfast
                                                    and Lunch/Dinner.</li>
                                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                                        class="bi bi-patch-check-fill text-success me-2"></i>Great Small
                                                    Breaks.</li>
                                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                                        class="bi bi-patch-check-fill text-success me-2"></i>Free Stay for
                                                    Kids Below the age of 12 years.</li>
                                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                                        class="bi bi-patch-check-fill text-success me-2"></i>On
                                                    Cancellation, You will not get any refund</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <!-- Card END -->
                                </div>
                                <!-- Card body END -->
                            </div>
                            <!-- Hotel information END -->

                            <!-- Guest detail START -->
                            <div class="card shadow">
                                <!-- Card header -->
                                <div class="card-header border-bottom p-4">
                                    <h4 class="card-title mb-0"><i class="bi bi-people-fill me-2"></i>Guest Details</h4>
                                </div>

                                <!-- Card body START -->
                                <div class="card-body p-4">
                                    <!-- Form START -->
                                    <form class="row g-4">
                                        <!-- Title -->
                                        <div class="col-12">
                                            <div class="bg-light rounded-2 px-4 py-3">
                                                <h6 class="mb-0">Main Guest</h6>
                                            </div>
                                        </div>

                                        <!-- Select -->
                                        <div class="col-md-2">
                                            <div class="form-size-lg">
                                                <label class="form-label">Title</label>
                                                <select class="form-select js-choice">
                                                    <option>Mr</option>
                                                    <option>Mrs</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Input -->
                                        <div class="col-md-5">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control form-control-lg"
                                                placeholder="Enter your name">
                                        </div>

                                        <!-- Input -->
                                        <div class="col-md-5">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control form-control-lg"
                                                placeholder="Enter your name">
                                        </div>

                                        <!-- Button -->
                                        <div class="col-12">
                                            <a href="#" class="btn btn-link mb-0 p-0"><i
                                                    class="fa-solid fa-plus me-2"></i>Add New Guest</a>
                                        </div>

                                        <!-- Input -->
                                        <div class="col-md-6">
                                            <label class="form-label">Email id</label>
                                            <input type="email" class="form-control form-control-lg"
                                                placeholder="Enter your email">
                                            <div id="emailHelp" class="form-text">(Booking voucher will be sent to this
                                                email ID)</div>
                                        </div>

                                        <!-- Input -->
                                        <div class="col-md-6">
                                            <label class="form-label">Mobile number</label>
                                            <input type="text" class="form-control form-control-lg"
                                                placeholder="Enter your mobile number">
                                        </div>
                                    </form>
                                    <!-- Form END -->

                                    <!-- Alert START -->
                                    <div class="alert alert-info my-4" role="alert">
                                        <a href="sign-up.html" class="alert-heading h6">Login</a> to prefill all details
                                        and get access to secret deals
                                    </div>
                                    <!-- Alert END -->

                                    <!-- Special request START -->
                                    <div class="card border mt-4">
                                        <!-- Card header -->
                                        <div class="card-header border-bottom">
                                            <h5 class="card-title mb-0">Special request</h5>
                                        </div>

                                        <!-- Card body START -->
                                        <div class="card-body">
                                            <form class="hstack flex-wrap gap-3">
                                                <!-- Checkbox -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="hotelType1">
                                                    <label class="form-check-label" for="hotelType1">Smoking room</label>
                                                </div>
                                                <!-- Checkbox -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="hotelType2">
                                                    <label class="form-check-label" for="hotelType2">Late check-in</label>
                                                </div>
                                                <!-- Checkbox -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="hotelType3">
                                                    <label class="form-check-label" for="hotelType3">Early
                                                        check-in</label>
                                                </div>
                                                <!-- Checkbox -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="hotelType4">
                                                    <label class="form-check-label" for="hotelType4">Room on a high
                                                        floor</label>
                                                </div>
                                                <!-- Checkbox -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="hotelType5">
                                                    <label class="form-check-label" for="hotelType5">Large bed</label>
                                                </div>
                                                <!-- Checkbox -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="hotelType6">
                                                    <label class="form-check-label" for="hotelType6">Airport
                                                        transfer</label>
                                                </div>
                                                <!-- Checkbox -->
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="hotelType8">
                                                    <label class="form-check-label" for="hotelType8">Twin beds</label>
                                                </div>
                                            </form>
                                        </div>
                                        <!-- Card body END -->
                                    </div>
                                    <!-- Special request END -->
                                </div>
                                <!-- Card body END -->
                            </div>
                            <!-- Guest detail END -->

                            <!-- Payment Options START -->
                            <div class="card shadow">
                                <!-- Card header -->
                                <div class="card-header border-bottom p-4">
                                    <!-- Title -->
                                    <h4 class="card-title mb-0"><i class="bi bi-wallet-fill me-2"></i>Payment Options</h4>
                                </div>

                                <!-- Card body START -->
                                <div class="card-body p-4 pb-0">
                                    <!-- Action box START -->
                                    <div class="bg-primary bg-opacity-10 rounded-3 mb-4 p-3">
                                        <div class="d-md-flex justify-content-md-between align-items-center">
                                            <!-- Image and title -->
                                            <div class="d-sm-flex align-items-center mb-2 mb-md-0">
                                                <!-- Image -->
                                                <img src="assets/images/element/16.svg" class="h-50px" alt="">
                                                <!-- Title -->
                                                <div class="ms-sm-3 mt-2 mt-sm-0">
                                                    <h5 class="card-title mb-0">Get Additional Discount</h5>
                                                    <p class="mb-0">Login to access saved payments and discounts!</p>
                                                </div>
                                            </div>

                                            <!-- Button -->
                                            <a href="sign-in.html" class="btn btn-primary mb-0">Login now</a>
                                        </div>
                                    </div>
                                    <!-- Action box END -->

                                    <!-- Accordion START -->
                                    <div class="accordion accordion-icon accordion-bg-light" id="accordioncircle">
                                        <!-- Credit or debit card START -->
                                        <div class="accordion-item mb-3">
                                            <h6 class="accordion-header" id="heading-1">
                                                <button class="accordion-button rounded collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapse-1"
                                                    aria-expanded="true" aria-controls="collapse-1">
                                                    <i class="bi bi-credit-card text-primary me-2"></i> <span
                                                        class="me-5">Credit or Debit Card</span>
                                                </button>
                                            </h6>
                                            <div id="collapse-1" class="accordion-collapse collapse show"
                                                aria-labelledby="heading-1" data-bs-parent="#accordioncircle">
                                                <!-- Accordion body -->
                                                <div class="accordion-body">

                                                    <!-- Card list -->
                                                    <div class="d-sm-flex justify-content-sm-between my-3">
                                                        <h6 class="mb-2 mb-sm-0">We Accept:</h6>
                                                        <ul class="list-inline my-0">
                                                            <li class="list-inline-item"> <a href="#"><img
                                                                        src="assets/images/element/visa.svg"
                                                                        class="h-30px" alt=""></a></li>
                                                            <li class="list-inline-item"> <a href="#"><img
                                                                        src="assets/images/element/mastercard.svg"
                                                                        class="h-30px" alt=""></a></li>
                                                            <li class="list-inline-item"> <a href="#"><img
                                                                        src="assets/images/element/expresscard.svg"
                                                                        class="h-30px" alt=""></a></li>
                                                        </ul>
                                                    </div>

                                                    <!-- Form START -->
                                                    <form class="row g-3">
                                                        <!-- Card number -->
                                                        <div class="col-12">
                                                            <label class="form-label"><span class="h6 fw-normal">Card
                                                                    Number *</span></label>
                                                            <div class="position-relative">
                                                                <input type="text" class="form-control" maxlength="14"
                                                                    placeholder="XXXX XXXX XXXX XXXX">
                                                                <img src="assets/images/element/visa.svg"
                                                                    class="w-30px position-absolute top-50 end-0 translate-middle-y me-2 d-none d-sm-block"
                                                                    alt="">
                                                            </div>
                                                        </div>
                                                        <!-- Expiration Date -->
                                                        <div class="col-md-6">
                                                            <label class="form-label"><span
                                                                    class="h6 fw-normal">Expiration date *</span></label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control" maxlength="2"
                                                                    placeholder="Month">
                                                                <input type="text" class="form-control" maxlength="4"
                                                                    placeholder="Year">
                                                            </div>
                                                        </div>
                                                        <!--Cvv code  -->
                                                        <div class="col-md-6">
                                                            <label class="form-label"><span class="h6 fw-normal">CVV / CVC
                                                                    *</span></label>
                                                            <input type="text" class="form-control" maxlength="3"
                                                                placeholder="xxx">
                                                        </div>
                                                        <!-- Card name -->
                                                        <div class="col-12">
                                                            <label class="form-label"><span class="h6 fw-normal">Name on
                                                                    Card *</span></label>
                                                            <input type="text" class="form-control"
                                                                aria-label="name of card holder"
                                                                placeholder="Enter card holder name">
                                                        </div>

                                                        <!-- Alert box START -->
                                                        <div class="col-12">
                                                            <div class="alert alert-success alert-dismissible fade show my-3"
                                                                role="alert">

                                                                <!-- Title -->
                                                                <div class="d-sm-flex align-items-center mb-3">
                                                                    <img src="assets/images/element/12.svg"
                                                                        class="w-40px me-3 mb-2 mb-sm-0" alt="">
                                                                    <h5 class="alert-heading mb-0">$50,000 Covid Cover &
                                                                        More</h5>
                                                                </div>

                                                                <!-- Content -->
                                                                <p class="mb-2">Aww yeah, you successfully read this
                                                                    important alert message. This example text is going to
                                                                    run a bit longer so that you can see how spacing within
                                                                    an alert works with this kind of content.</p>

                                                                <!-- Button and price -->
                                                                <div class="d-sm-flex align-items-center">
                                                                    <a href="#"
                                                                        class="btn btn-sm btn-success mb-2 mb-sm-0 me-3"><i
                                                                            class="fa-regular fa-plus me-2"></i>Add</a>
                                                                    <h6 class="mb-0">$69 per person</h6>
                                                                </div>

                                                                <!-- Close button -->
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="alert" aria-label="Close"></button>
                                                            </div>
                                                        </div>
                                                        <!-- Alert box END -->

                                                        <!-- Buttons -->
                                                        <div class="col-12">
                                                            <div
                                                                class="d-sm-flex justify-content-sm-between align-items-center">
                                                                <h4>$1800 <span class="small fs-6">Due now</span></h4>
                                                                <button class="btn btn-primary mb-0">Pay Now</button>
                                                            </div>
                                                        </div>

                                                    </form>
                                                    <!-- Form END -->
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Credit or debit card END -->

                                        <!-- Net banking START -->
                                        <div class="accordion-item mb-3">
                                            <h6 class="accordion-header" id="heading-2">
                                                <button class="accordion-button collapsed rounded" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapse-2"
                                                    aria-expanded="false" aria-controls="collapse-2">
                                                    <i class="bi bi-globe2 text-primary me-2"></i> <span
                                                        class="me-5">Pay with Net Banking</span>
                                                </button>
                                            </h6>
                                            <div id="collapse-2" class="accordion-collapse collapse"
                                                aria-labelledby="heading-2" data-bs-parent="#accordioncircle">
                                                <!-- Accordion body -->
                                                <div class="accordion-body">

                                                    <!-- Form START -->
                                                    <form class="row g-3 mt-1">

                                                        <!-- Popular bank -->
                                                        <ul class="list-inline mb-0">

                                                            <li class="list-inline-item">
                                                                <h6 class="mb-0">Popular Bank:</h6>
                                                            </li>
                                                            <!-- Rent -->
                                                            <li class="list-inline-item">
                                                                <input type="radio" class="btn-check" name="options"
                                                                    id="option1">
                                                                <label class="btn btn-light btn-primary-soft-check"
                                                                    for="option1">
                                                                    <img src="assets/images/element/13.svg"
                                                                        class="h-20px me-2" alt="">Bank of America
                                                                </label>
                                                            </li>
                                                            <!-- Sale -->
                                                            <li class="list-inline-item">
                                                                <input type="radio" class="btn-check" name="options"
                                                                    id="option2">
                                                                <label class="btn btn-light btn-primary-soft-check"
                                                                    for="option2">
                                                                    <img src="assets/images/element/15.svg"
                                                                        class="h-20px me-2" alt="">Bank of Japan
                                                                </label>
                                                            </li>
                                                            <!-- Buy -->
                                                            <li class="list-inline-item">
                                                                <input type="radio" class="btn-check" name="options"
                                                                    id="option3">
                                                                <label class="btn btn-light btn-primary-soft-check"
                                                                    for="option3">
                                                                    <img src="assets/images/element/14.svg"
                                                                        class="h-20px me-2" alt="">VIVIV Bank
                                                                </label>
                                                            </li>
                                                        </ul>

                                                        <p class="mb-1">In order to complete your transaction, we will
                                                            transfer you over to Booking secure servers.</p>
                                                        <p class="my-0">Select your bank from the drop-down list and
                                                            click proceed to continue with your payment.</p>
                                                        <!-- Select bank -->
                                                        <div class="col-md-6">
                                                            <select class="form-select form-select-sm js-choice border-0">
                                                                <option value="">Please choose one</option>
                                                                <option>Bank of America</option>
                                                                <option>Bank of India</option>
                                                                <option>Bank of London</option>
                                                            </select>
                                                        </div>

                                                        <!-- Button -->
                                                        <div class="d-grid">
                                                            <button class="btn btn-success mb-0">Pay $1800</button>
                                                        </div>

                                                    </form>
                                                    <!-- Form END -->
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Net banking END -->

                                        <!-- Paypal START -->
                                        <div class="accordion-item mb-3">
                                            <h6 class="accordion-header" id="heading-3">
                                                <button class="accordion-button collapsed rounded" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapse-3"
                                                    aria-expanded="false" aria-controls="collapse-3">
                                                    <i class="bi bi-paypal text-primary me-2"></i><span class="me-5">Pay
                                                        with Paypal</span>
                                                </button>
                                            </h6>
                                            <div id="collapse-3" class="accordion-collapse collapse"
                                                aria-labelledby="heading-3" data-bs-parent="#accordioncircle">
                                                <!-- Accordion body -->
                                                <div class="accordion-body">
                                                    <div class="card card-body border align-items-center text-center mt-4">
                                                        <!-- Image -->
                                                        <img src="assets/images/element/paypal.svg" class="h-70px mb-3"
                                                            alt="">
                                                        <p class="mb-3"><strong>Tips:</strong> Simply click on the
                                                            payment button below to proceed to the PayPal payment page.</p>
                                                        <a href="#" class="btn btn-sm btn-outline-primary mb-0">Pay
                                                            with paypal</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Paypal END -->
                                    </div>
                                    <!-- Accordion END -->
                                </div>
                                <!-- Card body END -->

                                <div class="card-footer p-4 pt-0">
                                    <!-- Condition link -->
                                    <p class="mb-0">By processing, You accept Booking <a href="#">Terms of
                                            Services</a> and <a href="#">Policy</a></p>
                                </div>
                            </div>
                            <!-- Payment Options END -->
                        </div>
                    </div>
                    <!-- Left side content END -->

                    <!-- Right side content START -->
                    <aside class="col-xl-4">
                        <div class="row g-4">

                            <!-- Price summary START -->
                            <div class="col-md-6 col-xl-12">
                                <div class="card shadow rounded-2">
                                    <!-- card header -->
                                    <div class="card-header border-bottom">
                                        <h5 class="card-title mb-0">Price Summary</h5>
                                    </div>

                                    <!-- Card body -->
                                    <div class="card-body">
                                        <ul class="list-group list-group-borderless">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Room Charges</span>
                                                <span class="fs-5">$28,660</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Total Discount<span
                                                        class="badge text-bg-danger smaller mb-0 ms-2">10%
                                                        off</span></span>
                                                <span class="fs-5 text-success">-$2,560</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Price after discount</span>
                                                <span class="fs-5">$1852</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Taxes % Fees</span>
                                                <span class="fs-5">$350</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Card footer -->
                                    <div class="card-footer border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="h5 mb-0">Payable Now</span>
                                            <span class="h5 mb-0">$22,500</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Price summary END -->

                            <!-- Offer and discount START -->
                            <div class="col-md-6 col-xl-12">
                                <div class="card shadow">
                                    <!-- Card header -->
                                    <div class="card-header border-bottom">
                                        <div class="cardt-title">
                                            <h5 class="mb-0">Offer &amp; Discount</h5>
                                        </div>
                                    </div>
                                    <!-- Card body -->
                                    <div class="card-body">

                                        <!-- Radio -->
                                        <div class="bg-light rounded-2 p-3">
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="discountOptions"
                                                    id="discount1" value="option1" checked>
                                                <label class="form-check-label h5 mb-0" for="discount1">GSTBOOK</label>
                                                <p class="mb-1 small">Congratulations! You have saved $230 with GSTBOOK.
                                                </p>
                                                <h6 class="mb-0 text-success">-$230</h6>
                                            </div>
                                        </div>

                                        <!-- Input group -->
                                        <div class="input-group mt-3">
                                            <input class="form-control form-control" placeholder="Coupon code">
                                            <button type="button" class="btn btn-primary">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Offer and discount END -->

                            <!-- Information START -->
                            <div class="col-md-6 col-xl-12">
                                <div class="card shadow">
                                    <!-- Card header -->
                                    <div class="card-header border-bottom">
                                        <h5 class="card-title mb-0">Why Sign up or Log in</h5>
                                    </div>

                                    <!-- Card body -->
                                    <div class="card-body">
                                        <!-- List -->
                                        <ul class="list-group list-group-borderless">
                                            <li class="list-group-item d-flex mb-0"><i
                                                    class="fa-solid fa-check text-success me-2"></i>
                                                <span class="h6 fw-normal">Get Access to Secret Deal</span>
                                            </li>

                                            <li class="list-group-item d-flex mb-0"><i
                                                    class="fa-solid fa-check text-success me-2"></i>
                                                <span class="h6 fw-normal">Book Faster</span>
                                            </li>

                                            <li class="list-group-item d-flex mb-0"><i
                                                    class="fa-solid fa-check text-success me-2"></i>
                                                <span class="h6 fw-normal">Manage Your Booking</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <!-- Information END -->

                        </div>
                    </aside>
                    <!-- Right side content END -->
                </div> <!-- Row END -->
            </div>
        </section>
        <!-- =======================
    Page content START -->

    </main>
    <!-- **************** MAIN CONTENT END **************** -->
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.wishlist-toggle').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const phongId = this.dataset.phong;
                    fetch("{{ url('account/wishlist/toggle') }}/" + phongId, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    }).then(r => r.json()).then(data => {
                        if (data.status === 'removed') {
                            window.location.reload();
                        } else if (data.status === 'added') {
                            window.location.reload();
                        }
                    });
                });
            });
        });
    </script>
@endpush
