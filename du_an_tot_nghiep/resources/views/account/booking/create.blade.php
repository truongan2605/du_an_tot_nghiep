@extends('layouts.app')

@section('title', 'Booking - ' . ($phong->name ?? $phong->ma_phong))

@section('content')
    @php
        $roomCapacity = 0;
        foreach ($phong->bedTypes as $bt) {
            $qty = (int) ($bt->pivot->quantity ?? 0);
            $cap = (int) ($bt->capacity ?? 1);
            $roomCapacity += $qty * $cap;
        }
    @endphp

    <main>
        <section class="py-0">
            <div class="container">
                <div class="card bg-light overflow-hidden px-sm-5">
                    <div class="row align-items-center g-4">
                        <div class="col-sm-9">
                            <div class="card-body">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb breadcrumb-dots mb-0">
                                        <li class="breadcrumb-item"><a href="{{ route('home') }}"><i
                                                    class="bi bi-house me-1"></i> Home</a></li>
                                        <li class="breadcrumb-item"><a href="{{ route('rooms.show', $phong->id) }}">Room
                                                detail</a></li>
                                        <li class="breadcrumb-item active">Booking</li>
                                    </ol>
                                </nav>
                                <h1 class="m-0 h2 card-title">Review your Booking</h1>
                            </div>
                        </div>

                        <div class="col-sm-3 text-end d-none d-sm-block">
                            <img src="{{ $phong->firstImageUrl() }}" class="mb-n4"
                                alt="{{ $phong->name ?? $phong->ma_phong }}">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <div class="row g-4 g-lg-5">
                    <div class="col-xl-8">
                        <div class="vstack gap-5">

                            <div class="card shadow">
                                <div class="card-header p-4 border-bottom">
                                    <h3 class="mb-0"><i class="fa-solid fa-hotel me-2"></i>Room Information</h3>
                                </div>

                                <div class="card-body p-4">
                                    <form action="{{ route('account.booking.store') }}" method="POST" id="bookingForm">
                                        @csrf
                                        <input type="hidden" name="phong_id" value="{{ $phong->id }}">

                                        <div class="row g-4">
                                            <div class="col-lg-6">
                                                <div class="d-flex">
                                                    <i class="bi bi-calendar fs-3 me-2 mt-2"></i>
                                                    <div
                                                        class="form-control-border form-control-transparent form-fs-md w-100">
                                                        <label class="form-label">Check in - Check out</label>
                                                        <input id="date_range" type="text" class="form-control flatpickr"
                                                            placeholder="Select date range" readonly>
                                                        <input type="hidden" name="ngay_nhan_phong" id="ngay_nhan_phong"
                                                            value="{{ old('ngay_nhan_phong', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                                                        <input type="hidden" name="ngay_tra_phong" id="ngay_tra_phong"
                                                            value="{{ old('ngay_tra_phong', \Carbon\Carbon::tomorrow()->format('Y-m-d')) }}">
                                                        <small class="text-muted">Check-in time: 2:00 pm — Check-out time:
                                                            12:00 am</small>
                                                        @error('ngay_nhan_phong')
                                                            <div class="text-danger small">{{ $message }}</div>
                                                        @enderror
                                                        @error('ngay_tra_phong')
                                                            <div class="text-danger small">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Guests & Beds -->
                                            <div class="col-lg-6">
                                                <div class="bg-light py-3 px-4 rounded-3">
                                                    <h6 class="fw-light small mb-1">Guests & Beds</h6>

                                                    <div class="row g-2 mb-2">
                                                        <div class="col-6">
                                                            <label class="form-label">Adults</label>
                                                            <input type="number" name="adults" id="adults"
                                                                class="form-control" min="1"
                                                                max="{{ max(1, $roomCapacity) }}"
                                                                value="{{ old('adults', min(2, max(1, $roomCapacity))) }}">
                                                            <small id="adults_help" class="text-muted d-block">Max adults
                                                                (current capacity): <strong
                                                                    id="room_capacity_display">{{ $roomCapacity }}</strong></small>
                                                        </div>

                                                        <div class="col-6">
                                                            <label class="form-label">Children</label>
                                                            <input type="number" name="children" id="children"
                                                                class="form-control" min="0" max="2"
                                                                value="{{ old('children', 0) }}">
                                                            <small id="children_help" class="text-muted d-block">Maximum 2
                                                                children per room.</small>
                                                        </div>

                                                    </div>

                                                    <div id="children_ages_container" class="mb-2">
                                                        {{-- JS will render child age inputs here --}}
                                                    </div>

                                                    <div class="mt-3">
                                                        <strong>Room beds (available):</strong>
                                                        <ul class="list-unstyled mb-2">
                                                            @forelse ($phong->bedTypes as $bt)
                                                                <li class="mb-1">
                                                                    <div
                                                                        class="d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <strong>{{ $bt->name }}</strong>
                                                                            <div class="small text-muted">
                                                                                {{ $bt->description ?? '' }} — capacity:
                                                                                {{ $bt->capacity }} person(s) / bed</div>
                                                                            <div class="small text-muted">Extra price per
                                                                                bed:
                                                                                {{ number_format($bt->price, 0, ',', '.') }}
                                                                                đ/night</div>
                                                                        </div>
                                                                        <div class="text-end">
                                                                            <label class="small mb-1">Select
                                                                                quantity</label>
                                                                            <select name="extra_beds[{{ $bt->id }}]"
                                                                                class="form-select form-select-sm extra-bed-select"
                                                                                data-bed-id="{{ $bt->id }}"
                                                                                data-available="{{ $bt->pivot->quantity }}">
                                                                                <option value="0">0</option>
                                                                                @for ($i = 1; $i <= $bt->pivot->quantity; $i++)
                                                                                    <option value="{{ $i }}">
                                                                                        {{ $i }}</option>
                                                                                @endfor
                                                                            </select>
                                                                            <div class="small text-muted">Available:
                                                                                {{ $bt->pivot->quantity }}</div>
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                            @empty
                                                                <li><em>No extra beds available for this room.</em></li>
                                                            @endforelse
                                                        </ul>
                                                    </div>
                                                    <input type="hidden" name="so_khach" id="so_khach"
                                                        value="{{ old('so_khach', $phong->suc_chua ?? 1) }}">
                                                    <div class="small text-muted">Room capacity (without extra beds):
                                                        {{ $phong->suc_chua ?? '-' }} persons</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card border mt-4">
                                            <div class="card-header border-bottom d-md-flex justify-content-md-between">
                                                <h5 class="card-title mb-0">
                                                    {{ $phong->ten ?? ($phong->loaiPhong->ten ?? 'Room') }}</h5>
                                            </div>

                                            <div class="card-body">
                                                <h6>Amenities</h6>
                                                @if ($phong->tienNghis && $phong->tienNghis->count())
                                                    <ul class="list-unstyled">
                                                        @foreach ($phong->tienNghis as $tn)
                                                            <li>
                                                                <i
                                                                    class="{{ $tn->icon ?? 'fa-solid fa-check' }} text-success me-2"></i>
                                                                {{ $tn->ten }}
                                                                @if ($tn->mo_ta)
                                                                    <div class="small text-muted">
                                                                        {{ \Illuminate\Support\Str::limit($tn->mo_ta, 150) }}
                                                                    </div>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="mb-0"><em>No amenities listed for this room.</em></p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="card shadow mt-4">
                                            <div class="card-header border-bottom p-4">
                                                <h4 class="card-title mb-0"><i class="bi bi-people-fill me-2"></i>Guest
                                                    Details</h4>
                                            </div>

                                            <div class="card-body p-4">
                                                @php $u = $user ?? auth()->user(); @endphp

                                                <div class="mb-3">
                                                    <label class="form-label">Full name</label>
                                                    <input type="text" name="name"
                                                        class="form-control form-control-lg"
                                                        value="{{ old('name', $u->name ?? '') }}" required>
                                                    @error('name')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Address</label>
                                                    <input type="text" name="address"
                                                        class="form-control form-control-lg"
                                                        value="{{ old('address', $u->address ?? '') }}" required>
                                                    @error('address')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" class="form-control"
                                                            value="{{ $u->email ?? '' }}" readonly>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Phone</label>
                                                        <input type="text" name="phone" class="form-control"
                                                            value="{{ old('phone', $u->so_dien_thoai ?? '') }}">
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <button type="submit" class="btn btn-lg btn-primary">Confirm</button>
                                                    <a href="{{ route('rooms.show', $phong->id) }}"
                                                        class="btn btn-secondary ms-2">Cancel</a>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>

                    <aside class="col-xl-4">
                        <div class="row g-4">
                            <!-- Price summary START -->
                            <div class="col-md-6 col-xl-12">
                                <div class="card shadow rounded-2">
                                    <div class="card-header border-bottom">
                                        <h5 class="card-title mb-0">Price Summary</h5>
                                    </div>

                                    <div class="card-body">
                                        <ul class="list-group list-group-borderless">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Room base / night</span>
                                                <span class="fs-6"
                                                    id="price_base_display">{{ number_format($phong->tong_gia ?? ($phong->gia_mac_dinh ?? 0), 0, ',', '.') }}
                                                    đ</span>
                                            </li>

                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Beds extra / night</span>
                                                <span class="fs-6" id="price_beds_display">-</span>
                                            </li>

                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Adults extra / night</span>
                                                <span class="fs-6" id="price_adults_display">-</span>
                                            </li>

                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Children extra / night</span>
                                                <span class="fs-6" id="price_children_display">-</span>
                                            </li>

                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Final price / night</span>
                                                <span class="fs-5" id="final_per_night_display">-</span>
                                            </li>

                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Nights</span>
                                                <span class="fs-5" id="nights_count_display">-</span>
                                            </li>

                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Total</span>
                                                <span class="fs-5" id="total_snapshot_display">-</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="card-footer border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="h5 mb-0">Payable Now</span>
                                            <span class="h5 mb-0" id="payable_now_display">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Price summary END -->

                        </div>
                    </aside>

                </div>
            </div>
        </section>

    </main>
@endsection

@push('scripts')
    <script>
        (function() {
            // Elements
            const dateRangeInput = document.getElementById('date_range');
            const fromInput = document.getElementById('ngay_nhan_phong');
            const toInput = document.getElementById('ngay_tra_phong');

            const adultsInput = document.getElementById('adults');
            const childrenInput = document.getElementById('children');
            const childrenAgesContainer = document.getElementById('children_ages_container');
            const extraBedSelects = document.querySelectorAll('.extra-bed-select');

            const nightsDisplay = document.getElementById('nights_count_display');
            const priceBaseDisplay = document.getElementById('price_base_display');
            const priceBedsDisplay = document.getElementById('price_beds_display');
            const priceAdultsDisplay = document.getElementById('price_adults_display');
            const priceChildrenDisplay = document.getElementById('price_children_display');
            const finalPerNightDisplay = document.getElementById('final_per_night_display');
            const totalDisplay = document.getElementById('total_snapshot_display');
            const payableDisplay = document.getElementById('payable_now_display');


            const adultsHelp = document.getElementById('adults_help');
            const childrenHelp = document.getElementById('children_help');
            const roomCapacityDisplay = document.getElementById('room_capacity_display');

            function clampInputToMaxMin(inputEl) {
                const min = Number(inputEl.getAttribute('min') || 0);
                const max = Number(inputEl.getAttribute('max') || Infinity);
                let val = Number(inputEl.value || 0);

                if (isNaN(val)) val = min;
                if (val < min) val = min;
                if (val > max) val = max;

                if (Number(inputEl.value) !== val) {
                    inputEl.value = val;
                    // small flash message
                    const sm = document.createElement('div');
                    sm.className = 'small text-danger mt-1';
                    sm.innerText = `Value adjusted to allowed range (${min} - ${max}).`;
                    // remove any previous helper message under this input
                    const parent = inputEl.parentElement;
                    const prev = parent.querySelector('.value-adjust-msg');
                    if (prev) prev.remove();
                    sm.classList.add('value-adjust-msg');
                    parent.appendChild(sm);
                    setTimeout(() => {
                        sm.remove();
                    }, 2500);
                }
            }

            // Attach input listeners
            adultsInput.addEventListener('input', function() {
                clampInputToMaxMin(adultsInput);
                updateSummary();
            });
            childrenInput.addEventListener('input', function() {
                clampInputToMaxMin(childrenInput);
                // re-render children ages UI if count changed
                renderChildrenAges();
                updateSummary();
            });

            // When extra beds change (which affects effectiveCapacity), update adults.max and UI
            extraBedSelects.forEach(sel => sel.addEventListener('change', function() {
                const bedExtras = computeBedExtra();
                const effectiveCapacity = roomCapacity + bedExtras.extraCapacity;
                // update displayed capacity
                if (roomCapacityDisplay) roomCapacityDisplay.innerText = effectiveCapacity;
                // update adults max attr
                adultsInput.setAttribute('max', Math.max(1, effectiveCapacity));
                // clamp current adults value if too big
                clampInputToMaxMin(adultsInput);
                updateSummary();
            }));

            // ensure initial clamp (in case default values > max)
            clampInputToMaxMin(adultsInput);
            clampInputToMaxMin(childrenInput);

            // data from server
            const pricePerNight = Number({!! json_encode((float) ($phong->tong_gia ?? ($phong->gia_mac_dinh ?? 0))) !!});
            const bedTypes = {!! json_encode(
                $phong->bedTypes->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'name' => $b->name,
                        'capacity' => (int) $b->capacity,
                        'price' => (float) $b->price,
                        'available' => (int) $b->pivot->quantity,
                    ];
                }),
            ) !!};

            // compute room capacity (sum pivot.quantity * capacity)
            let roomCapacity = 0;
            bedTypes.forEach(b => {
                roomCapacity += (b.available || 0) * (b.capacity || 1);
            });

            // show roomCapacity somewhere if you want (for debugging)
            // e.g. console.log('roomCapacity', roomCapacity);

            const ADULT_PRICE = {{ \App\Http\Controllers\Client\BookingController::ADULT_PRICE }};
            const CHILD_PRICE = {{ \App\Http\Controllers\Client\BookingController::CHILD_PRICE }};
            const CHILD_FREE_AGE = {{ \App\Http\Controllers\Client\BookingController::CHILD_FREE_AGE }};

            function fmtVnd(num) {
                return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + ' đ';
            }

            // date range / flatpickr init
            function setHiddenDates(arr) {
                if (!arr || arr.length === 0) return;
                const from = arr[0];
                const to = arr[1] || arr[0];

                function fmt(d) {
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${y}-${m}-${day}`;
                }
                fromInput.value = fmt(from);
                toInput.value = fmt(to);
                updateSummary();
            }

            if (typeof flatpickr !== 'undefined') {
                flatpickr(dateRangeInput, {
                    mode: "range",
                    minDate: "today",
                    dateFormat: "Y-m-d",
                    defaultDate: [
                        fromInput.value || new Date().toISOString().slice(0, 10),
                        toInput.value || (() => {
                            let d = new Date();
                            d.setDate(d.getDate() + 1);
                            return d.toISOString().slice(0, 10);
                        })()
                    ],
                    onChange: function(selectedDates) {
                        if (selectedDates.length) setHiddenDates(selectedDates);
                    }
                });
                setHiddenDates([new Date(fromInput.value), new Date(toInput.value)]);
            }

            // children ages UI
            function renderChildrenAges() {
                const count = Number(childrenInput.value || 0);
                childrenAgesContainer.innerHTML = '';
                for (let i = 0; i < count; i++) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-2';
                    wrapper.innerHTML = `
                <label class="form-label">Child ${i+1} age</label>
                <input type="number" name="children_ages[]" class="form-control child-age-input" min="0" max="120" value="0">
            `;
                    childrenAgesContainer.appendChild(wrapper);
                }
                document.querySelectorAll('.child-age-input').forEach(el => el.addEventListener('change',
                    updateSummary));
                updateSummary();
            }

            function computeBedExtra() {
                let extraCapacity = 0;
                let bedExtraPricePerNight = 0;
                extraBedSelects.forEach(sel => {
                    const id = Number(sel.dataset.bedId);
                    const qty = Number(sel.value || 0);
                    if (qty <= 0) return;
                    const bt = bedTypes.find(b => b.id === id);
                    if (!bt) return;
                    extraCapacity += qty * bt.capacity;
                    bedExtraPricePerNight += qty * bt.price;
                });
                return {
                    extraCapacity,
                    bedExtraPricePerNight
                };
            }

            function computePersonCharges() {
                const adults = Number(adultsInput.value || 0);
                const ages = Array.from(document.querySelectorAll('.child-age-input')).map(x => Number(x.value || 0));
                let computedAdults = adults;
                let chargeableChildren = 0;
                ages.forEach(a => {
                    if (a >= 13) computedAdults++;
                    else if (a >= 7) chargeableChildren++;
                });
                const adultsChargePerNight = computedAdults * ADULT_PRICE;
                const childrenChargePerNight = chargeableChildren * CHILD_PRICE;
                return {
                    computedAdults,
                    chargeableChildren,
                    adultsChargePerNight,
                    childrenChargePerNight
                };
            }

            function updateSummary() {
                // dates -> nights
                const fromVal = fromInput.value;
                const toVal = toInput.value;
                if (!fromVal || !toVal) {
                    nightsDisplay.innerText = '-';
                    finalPerNightDisplay.innerText = '-';
                    totalDisplay.innerText = '-';
                    payableDisplay.innerText = '-';
                    return;
                }
                const from = new Date(fromVal + 'T00:00:00');
                const to = new Date(toVal + 'T00:00:00');
                const diffMs = to - from;
                const nights = Math.max(0, Math.round(diffMs / (1000 * 60 * 60 * 24)));
                nightsDisplay.innerText = nights;

                // compute extras
                const bedExtras = computeBedExtra();
                const persons = computePersonCharges();

                const base = pricePerNight;
                const bedsPrice = bedExtras.bedExtraPricePerNight;
                const adultsPrice = persons.adultsChargePerNight;
                const childrenPrice = persons.childrenChargePerNight;

                const finalPerNight = base + bedsPrice + adultsPrice + childrenPrice;
                const total = finalPerNight * nights;

                // update displays
                priceBedsDisplay.innerText = bedsPrice > 0 ? fmtVnd(bedsPrice) : '0 đ';
                priceAdultsDisplay.innerText = adultsPrice > 0 ? fmtVnd(adultsPrice) : '0 đ';
                priceChildrenDisplay.innerText = childrenPrice > 0 ? fmtVnd(childrenPrice) : '0 đ';
                finalPerNightDisplay.innerText = fmtVnd(finalPerNight);
                totalDisplay.innerText = fmtVnd(total);
                payableDisplay.innerText = fmtVnd(total);

                // validation messages & submit enable/disable
                validateGuestLimits(persons.computedAdults);
            }

            // validation: adults (computedAdults) <= effectiveCapacity; children <= 2
            function validateGuestLimits(computedAdults) {
                const childrenCount = Number(childrenInput.value || 0);
                const bedExtras = computeBedExtra();
                const effectiveCapacity = roomCapacity + bedExtras.extraCapacity;

                const form = document.getElementById('bookingForm');

                // clear previous errors
                let existing = document.getElementById('guest_limit_error');
                if (existing) existing.remove();

                let ok = true;
                if (computedAdults > effectiveCapacity) {
                    ok = false;
                    const err = document.createElement('div');
                    err.id = 'guest_limit_error';
                    err.className = 'alert alert-danger mt-3';
                    err.innerText =
                        `Number of adults (including children aged 13+) exceeds effective room capacity. Please reduce adults or add extra beds.`;
                    form.querySelector('.card-body').prepend(err);
                } else if (childrenCount > 2) {
                    ok = false;
                    const err = document.createElement('div');
                    err.id = 'guest_limit_error';
                    err.className = 'alert alert-danger mt-3';
                    err.innerText = `Maximum 2 children allowed per room.`;
                    form.querySelector('.card-body').prepend(err);
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = !ok;
            }

            adultsInput.addEventListener('change', updateSummary);
            childrenInput.addEventListener('change', function() {
                renderChildrenAges();
            });
            extraBedSelects.forEach(sel => sel.addEventListener('change', updateSummary));
            renderChildrenAges();
            updateSummary();

        })();
    </script>
@endpush
