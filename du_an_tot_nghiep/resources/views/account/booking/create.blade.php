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
        $baseCapacity = (int) ($phong->suc_chua ?? ($phong->loaiPhong->suc_chua ?? ($roomCapacity ?: 1)));
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
                                alt="{{ $phong->name ?? $phong->ma_phong }}" style="max-width:100px;">
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

                                        {{-- server-side messages --}}
                                        <div id="server_message_container" class="mb-3">
                                            @if (session('success'))
                                                <div id="server_success" data-message="{{ e(session('success')) }}"
                                                    data-datphong="{{ session('dat_phong_id') ?? '' }}"></div>
                                            @endif

                                            @if ($errors->any())
                                                {{-- ưu tiên hiển thị lỗi cụ thể trước --}}
                                                <div id="server_error" data-message="{{ e($errors->first()) }}"></div>
                                            @endif
                                        </div>

                                        <input type="hidden" name="phong_id" value="{{ $phong->id }}">
                                        <input type="hidden" name="spec_signature_hash"
                                            value="{{ $phong->spec_signature_hash ?? $phong->specSignatureHash() }}">

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
                                                            12:00 pm</small>

                                                        <div id="availability_message" class="small mt-2"></div>

                                                        @error('ngay_nhan_phong')
                                                            <div class="text-danger small">{{ $message }}</div>
                                                        @enderror

                                                        @error('ngay_tra_phong')
                                                            <div class="text-danger small">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Guests  -->
                                            <div class="col-lg-6">
                                                <div class="bg-light py-3 px-4 rounded-3">
                                                    <h6 class="fw-light small mb-1">Guests</h6>

                                                    <div class="row g-2 mb-2">
                                                        <div class="col-6">
                                                            <label class="form-label">Adults</label>
                                                            <input type="number" name="adults" id="adults"
                                                                class="form-control" min="1"
                                                                max="{{ max(1, $roomCapacity + 2) }}"
                                                                value="{{ old('adults', min(2, max(1, $roomCapacity))) }}">
                                                            <small id="adults_help" class="text-muted d-block">Maximum
                                                                number of people
                                                                : <strong
                                                                    id="room_capacity_display">{{ $roomCapacity + 2 }}</strong>
                                                            </small>
                                                        </div>

                                                        <div class="col-6">
                                                            <label class="form-label">Children</label>
                                                            <input type="number" name="children" id="children"
                                                                class="form-control" min="0" max="2"
                                                                value="{{ old('children', 0) }}">
                                                            <small id="children_help" class="text-muted d-block">Maximum 2
                                                                children per room.</small>
                                                        </div>

                                                        <div class="col-6">
                                                            <label class="form-label">Rooms</label>
                                                            <input type="number" name="rooms_count" id="rooms_count"
                                                                class="form-control" min="1"
                                                                max="{{ $availableRoomsDefault ?? 1 }}"
                                                                value="{{ old('rooms_count', 1) }}">
                                                            <small class="text-muted d-block">Available for selected dates:
                                                                <strong
                                                                    id="available_rooms_display">{{ $availableRoomsDefault ?? 0 }}</strong>
                                                                room(s)</small>
                                                        </div>

                                                    </div>

                                                    <div id="children_ages_container" class="mb-2">
                                                        {{-- JS will render child age inputs here Do not delete this div --}}
                                                    </div>

                                                    <div class="mt-3">
                                                        <strong>Room beds:</strong>
                                                        <ul class="list-unstyled mb-2">
                                                            @forelse ($phong->bedTypes as $bt)
                                                                <li class="mb-1">
                                                                    <div
                                                                        class="d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <strong>{{ $bt->name }}</strong>
                                                                            <div class="small text">
                                                                                {{ $bt->description ?? '' }}
                                                                            </div>
                                                                            <div class="small text">Quantity:
                                                                                {{ $bt->pivot->quantity }}</div>
                                                                            <div class="small text">Price/bed:
                                                                                {{ number_format($bt->price, 0, ',', '.') }}
                                                                                đ/night</div>
                                                                        </div>

                                                                    </div>
                                                                </li>
                                                            @empty
                                                                <li><em>No beds configured for this room.</em></li>
                                                            @endforelse
                                                        </ul>
                                                    </div>


                                                    <input type="hidden" name="so_khach" id="so_khach"
                                                        value="{{ old('so_khach', $phong->suc_chua ?? 1) }}">
                                                    <div class="small text">Room for:
                                                        {{ $phong->suc_chua ?? ($roomCapacity ?? '-') }} persons</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card border mt-4">
                                            <div class="card-header border-bottom d-md-flex justify-content-md-between">
                                                <h5 class="card-title mb-0">
                                                    {{ $phong->name ?? ($phong->loaiPhong->ten ?? 'Room') }}</h5>
                                            </div>

                                            <div class="card-body">
                                                <h6>Services</h6>
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
                                                    <hr class="my-3" />
                                                    <h6 class="mb-2">Additional services</h6>
                                                    @if (isset($availableAddons) && $availableAddons->count())
                                                        <ul class="list-unstyled">
                                                            @foreach ($availableAddons as $addon)
                                                                <li class="mb-2">
                                                                    <label class="d-flex align-items-center">
                                                                        <input type="checkbox" name="addons[]"
                                                                            value="{{ $addon->id }}"
                                                                            data-price="{{ $addon->gia }}"
                                                                            class="me-2 addon-checkbox"
                                                                            {{ in_array($addon->id, old('addons', [])) ? 'checked' : '' }}>

                                                                        <span>
                                                                            <strong>{{ $addon->ten }}</strong>
                                                                            <div class="small text-muted">
                                                                                {{ \Illuminate\Support\Str::limit($addon->mo_ta ?? '', 100) }}
                                                                            </div>
                                                                            <div class="small text">+
                                                                                {{ number_format($addon->gia ?? 0, 0, ',', '.') }}
                                                                                đ / night</div>
                                                                        </span>
                                                                    </label>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="mb-0"><em>No additional payable services
                                                                available.</em></p>
                                                    @endif
                                                @else
                                                    <p class="mb-0"><em>No services listed for this room.</em></p>
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
                                                            value="{{ old('phone', $u->so_dien_thoai ?? '') }}" required>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Notes</label>
                                                    <textarea name="ghi_chu" class="form-control" rows="3" placeholder="Special notes for the hotel">{{ old('ghi_chu') }}</textarea>
                                                </div>

                                                <div class="mb-3">

                                                    <input type="hidden" name="phong_id" value="{{ $phong->id }}">
                                                    <input type="hidden" name="tong_tien" id="hidden_tong_tien"
                                                        value="{{ $phong->tong_gia ?? ($phong->tong_tien ?? ($phong->gia_mac_dinh ?? 0)) }}">
                                                    <input type="hidden" name="ngay_nhan"
                                                        value="{{ $ngay_nhan ?? now()->format('Y-m-d') }}">
                                                    <input type="hidden" name="ngay_tra"
                                                        value="{{ $ngay_tra ?? now()->addDay()->format('Y-m-d') }}">



                                                    <div class="mt-3" id="vnpay_button_wrapper" style="display:none;">
                                                        <button type="button" class="btn btn-lg btn-success"
                                                            id="pay_vnpay_btn">
                                                            Pay with VNPAY
                                                        </button>
                                                    </div>

                                                    <div class="mt-3">
                                                        <label for="phuong_thuc" class="form-label">Payment method</label>
                                                        <select name="phuong_thuc" id="phuong_thuc" class="form-select"
                                                            required>
                                                            <option value="">Select method</option>
                                                            <option value="tien_mat"
                                                                {{ old('phuong_thuc') == 'tien_mat' ? 'selected' : '' }}>
                                                                Pay at the hotel (Cash)
                                                            </option>
                                                            <option value="vnpay"
                                                                {{ old('phuong_thuc') == 'vnpay' ? 'selected' : '' }}>
                                                                VNPAY QR
                                                            </option>
                                                            <option value="chuyen_khoan"
                                                                {{ old('phuong_thuc') == 'chuyen_khoan' ? 'selected' : '' }}>
                                                                Bank transfer
                                                            </option>
                                                        </select>
                                                    </div>

                                                </div>

                                                <input type="hidden" name="final_per_night" id="final_per_night_input"
                                                    value="">
                                                <input type="hidden" name="snapshot_total" id="snapshot_total_input"
                                                    value="">

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

                                            {{-- Beds extra removed --}}

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
                                                <span class="fs-6" id="final_per_night_display">-</span>
                                            </li>

                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Nights</span>
                                                <span class="fs-5" id="nights_count_display">-</span>
                                            </li>

                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span class="h6 fw-light mb-0">Additional services</span>
                                                <span class="fs-6" id="price_addons_display">-</span>
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
            const initialChildrenAges = {!! json_encode(old('children_ages', [])) !!};
            const initialChildrenCount = Number({{ old('children', 0) }});
            const initialSelectedAddons = {!! json_encode(old('addons', [])) !!};

            // Elements
            const dateRangeInput = document.getElementById('date_range');
            const fromInput = document.getElementById('ngay_nhan_phong');
            const toInput = document.getElementById('ngay_tra_phong');

            const adultsInput = document.getElementById('adults');
            const childrenInput = document.getElementById('children');
            const childrenAgesContainer = document.getElementById('children_ages_container');
            const roomsInput = document.getElementById('rooms_count');

            const nightsDisplay = document.getElementById('nights_count_display');
            const priceBaseDisplay = document.getElementById('price_base_display');
            const priceAdultsDisplay = document.getElementById('price_adults_display');
            const priceChildrenDisplay = document.getElementById('price_children_display');
            const finalPerNightDisplay = document.getElementById('final_per_night_display');
            const totalDisplay = document.getElementById('total_snapshot_display');
            const payableDisplay = document.getElementById('payable_now_display');

            const availDisplayEl = document.getElementById('available_rooms_display');
            const availabilityMessageEl = document.getElementById('availability_message');

            const pricePerNight = Number({!! json_encode((float) ($phong->tong_gia ?? ($phong->gia_mac_dinh ?? 0))) !!});
            const baseCapacity = Number({{ $baseCapacity }});

            const ADULT_PRICE = {{ \App\Http\Controllers\Client\BookingController::ADULT_PRICE }};
            const CHILD_PRICE = {{ \App\Http\Controllers\Client\BookingController::CHILD_PRICE }};
            const CHILD_FREE_AGE = {{ \App\Http\Controllers\Client\BookingController::CHILD_FREE_AGE }};

            // track latest availability result for use in validation
            let currentAvailableRooms = Number(availDisplayEl ? (availDisplayEl.innerText || 0) : 0);

            function fmtVnd(num) {
                return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + ' đ';
            }

            function computeAddonsPerNight() {
                let sum = 0;
                document.querySelectorAll('input[name="addons[]"]:checked').forEach(chk => {
                    const p = Number(chk.dataset.price || 0);
                    if (!isNaN(p)) sum += p;
                });
                const rooms = Number(roomsInput ? (roomsInput.value || 1) : 1);
                return sum * Math.max(1, rooms);
            }

            // clamp helper
            function clampNumberInput(el, min, max) {
                if (!el) return;
                let v = Number(el.value || 0);
                if (isNaN(v)) v = min;
                if (v < min) el.value = min;
                else if (v > max) el.value = max;
            }

            // date-range / flatpickr init
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
                updateRoomsAvailability();
            }

            if (typeof flatpickr !== 'undefined') {
                flatpickr(dateRangeInput, {
                    mode: "range",
                    minDate: "today",
                    dateFormat: "Y-m-d",
                    defaultDate: [fromInput.value || new Date().toISOString().slice(0, 10),
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

            // availability AJAX
            async function updateRoomsAvailability() {
                try {
                    const from = fromInput.value;
                    const to = toInput.value;
                    if (!from || !to) return;
                    const loaiId = {{ $phong->loai_phong_id }};
                    const phongId = {{ $phong->id }};
                    const params = new URLSearchParams({
                        loai_phong_id: String(loaiId),
                        phong_id: String(phongId),
                        from: from,
                        to: to
                    }).toString();
                    const url = '{{ route('booking.availability') }}' + '?' + params;
                    const res = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) {
                        console.error('Availability check error: status', res.status);
                        return;
                    }
                    const data = await res.json();
                    const avail = Number(data.available || 0);
                    currentAvailableRooms = avail;

                    // update UI
                    if (availDisplayEl) availDisplayEl.innerText = avail;
                    if (roomsInput) {
                        // set max for rooms input; keep value within range (if avail==0 we keep value but disable submit via validation)
                        roomsInput.max = avail;
                        if (Number(roomsInput.value || 0) > avail) roomsInput.value = Math.max(1, avail);
                    }

                    // When no rooms available, show friendly suggestion (try to find next available start date)
                    if (avail === 0) {
                        showNoAvailabilityMessage(from, to);
                    } else {
                        clearAvailabilityMessage();
                    }

                    updateSummary();
                } catch (err) {
                    console.error('Availability check error', err);
                }
            }

            function showNoAvailabilityMessage(fromStr, toStr) {
                if (!availabilityMessageEl) return;
                availabilityMessageEl.className = 'small mt-2 text-danger';
                availabilityMessageEl.innerText =
                    'There are currently no rooms available for the selected date range. Looking for the nearest available date...';
                // try to find next available start (scan up to 30 days)
                findNextAvailable(fromStr, 30).then(nextStart => {
                    if (!nextStart) {
                        availabilityMessageEl.innerText =
                            'There are currently no rooms available for the next 30 days. Please choose a different period.';
                    } else {
                        availabilityMessageEl.innerHTML =
                            `There are currently no rooms available for the selected date range. Please select a date starting from <strong>${nextStart}</strong> onwards.`;
                    }
                }).catch(() => {
                    availabilityMessageEl.innerText =
                        'There are currently no rooms available for the selected date range.';
                });
                // disable submit
                toggleSubmit(false);
            }

            function clearAvailabilityMessage() {
                if (!availabilityMessageEl) return;
                availabilityMessageEl.innerText = '';
                toggleSubmit(true);
            }

            async function findNextAvailable(fromStr, maxDays = 30) {
                try {
                    const loaiId = {{ $phong->loai_phong_id }};
                    const phongId = {{ $phong->id }};
                    const startDate = new Date(toInput.value + 'T00:00:00');
                    for (let i = 0; i < maxDays; i++) {
                        const cand = new Date(startDate);
                        cand.setDate(startDate.getDate() + i);
                        const candTo = new Date(cand);
                        candTo.setDate(cand.getDate() + 1);
                        const candFromStr = cand.toISOString().slice(0, 10);
                        const candToStr = candTo.toISOString().slice(0, 10);
                        const params = new URLSearchParams({
                            loai_phong_id: String(loaiId),
                            phong_id: String(phongId),
                            from: candFromStr,
                            to: candToStr
                        }).toString();
                        const url = '{{ route('booking.availability') }}' + '?' + params;
                        try {
                            const r = await fetch(url, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });
                            if (!r.ok) continue;
                            const d = await r.json();
                            const a = Number(d.available || 0);
                            if (a > 0) {
                                return candFromStr;
                            }
                        } catch (e) {
                            // ignore network errors for individual checks
                            continue;
                        }
                    }
                    return null;
                } catch (e) {
                    return null;
                }
            }

            function toggleSubmit(enabled) {
                const form = document.getElementById('bookingForm');
                if (!form) return;
                const submitBtn = form.querySelector('button[type="submit"]');
                if (!submitBtn) return;
                submitBtn.disabled = !enabled;
            }

            // render children ages
            function renderChildrenAges() {
                const count = Number(childrenInput.value || initialChildrenCount || 0);
                childrenAgesContainer.innerHTML = '';
                for (let i = 0; i < count; i++) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-2 child-age-wrapper';
                    const initialVal = (Array.isArray(initialChildrenAges) && typeof initialChildrenAges[i] !==
                        'undefined') ? Number(initialChildrenAges[i]) : 0;
                    wrapper.innerHTML = `
                <label class="form-label">Child ${i+1} age</label>
                <input type="number" name="children_ages[]" class="form-control child-age-input" min="0" max="12" value="${initialVal}" />
                <div class="small text-danger mt-1 age-error" style="display:none;"></div>
            `;
                    childrenAgesContainer.appendChild(wrapper);
                }

                document.querySelectorAll('.child-age-input').forEach((el) => {
                    el.addEventListener('input', function() {
                        const min = 0,
                            max = 12;
                        let v = Number(this.value);
                        if (isNaN(v)) v = min;
                        if (v < min) {
                            this.value = min;
                            showAgeError(this, `Minimum age is ${min}.`);
                        } else if (v > max) {
                            this.value = max;
                            showAgeError(this, `The maximum age for children is ${max}.`);
                        } else hideAgeError(this);
                        updateSummary();
                    });
                    el.addEventListener('blur', function() {
                        const min = 0,
                            max = 12;
                        let v = Number(this.value);
                        if (isNaN(v) || v < min) this.value = min;
                        if (v > max) this.value = max;
                    });
                });

                function showAgeError(inputEl, msg) {
                    const wr = inputEl.closest('.child-age-wrapper');
                    if (!wr) return;
                    const err = wr.querySelector('.age-error');
                    if (err) {
                        err.innerText = msg;
                        err.style.display = 'block';
                        setTimeout(() => err.style.display = 'none', 2500);
                    }
                }

                function hideAgeError(inputEl) {
                    const wr = inputEl.closest('.child-age-wrapper');
                    if (!wr) return;
                    const err = wr.querySelector('.age-error');
                    if (err) err.style.display = 'none';
                }

                updateSummary();
            }

            function updateInputLimitsByRooms() {
                const rooms = Number(roomsInput ? (roomsInput.value || 1) : 1);
                const adultsMax = (baseCapacity + 2) * Math.max(1, rooms);
                const childrenMax = Math.min(12, 2 * Math.max(1, rooms));

                if (adultsInput) {
                    adultsInput.max = adultsMax;
                    clampNumberInput(adultsInput, Number(adultsInput.min || 1), adultsMax);
                    const roomCapDisplay = document.getElementById('room_capacity_display');
                    if (roomCapDisplay) roomCapDisplay.innerText = adultsMax;
                }
                if (childrenInput) {
                    childrenInput.max = childrenMax;
                    clampNumberInput(childrenInput, Number(childrenInput.min || 0), childrenMax);
                }
            }

            function computePersonCharges() {
                const adults = Number(adultsInput.value || 0);
                const ages = Array.from(document.querySelectorAll('.child-age-input')).map(x => {
                    let a = Number(x.value || 0);
                    if (isNaN(a)) a = 0;
                    if (a < 0) a = 0;
                    if (a > 12) a = 12;
                    return a;
                });
                let computedAdults = adults;
                let chargeableChildren = 0;
                ages.forEach(a => {
                    if (a >= 13) computedAdults++;
                    else if (a >= 7) chargeableChildren++;
                });
                return {
                    computedAdults,
                    chargeableChildren
                };
            }

            function validateGuestLimits(computedAdults, chargeableChildren, countedPersons, totalMaxAllowed) {
                const childrenCount = Number(childrenInput.value || 0);
                const form = document.getElementById('bookingForm');
                let existing = document.getElementById('guest_limit_error');
                if (existing) existing.remove();
                let ok = true;

                // First, handle availability condition: if no rooms available -> show availability message and block submit
                if (currentAvailableRooms <= 0) {
                    ok = false;
                    const err = document.createElement('div');
                    err.id = 'guest_limit_error';
                    err.className = 'alert alert-danger mt-3';
                    // Use the same availabilityMessageEl text if set; otherwise generic
                    if (availabilityMessageEl && availabilityMessageEl.innerText.trim()) {
                        err.innerHTML = availabilityMessageEl.innerHTML;
                    } else {
                        err.innerText =
                            'There are currently no rooms available for the selected date range. Please choose a different range.';
                    }
                    const cardBody = form.querySelector('.card-body');
                    if (cardBody) cardBody.prepend(err);
                } else {
                    // Only show guest-limit messages when rooms are available
                    if (countedPersons > totalMaxAllowed) {
                        ok = false;
                        const err = document.createElement('div');
                        err.id = 'guest_limit_error';
                        err.className = 'alert alert-danger mt-3';
                        err.innerText =
                            `The number of guests exceeds the maximum allowed (${totalMaxAllowed}). Please reduce the number of guests or increase rooms. Note: Children under 7 years are free and not counted.`;
                        const cardBody = form.querySelector('.card-body');
                        if (cardBody) cardBody.prepend(err);
                    } else if (childrenCount > Number(childrenInput.max || 2)) {
                        ok = false;
                        const err = document.createElement('div');
                        err.id = 'guest_limit_error';
                        err.className = 'alert alert-danger mt-3';
                        err.innerText = `Tối đa ${childrenInput.max} trẻ em cho ${roomsInput.value || 1} phòng.`;
                        const cardBody = form.querySelector('.card-body');
                        if (cardBody) cardBody.prepend(err);
                    }
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = !ok;
            }

            function updateSummary() {
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

                let roomsCount = 1;
                if (roomsInput) {
                    roomsCount = Number(roomsInput.value || 1);
                    if (isNaN(roomsCount) || roomsCount < 1) roomsCount = 1;
                    if (roomsInput.max && Number(roomsInput.max) >= 0 && roomsCount > Number(roomsInput.max)) {
                        roomsCount = Number(roomsInput.max);
                        roomsInput.value = roomsCount;
                    }
                }

                updateInputLimitsByRooms();

                const persons = computePersonCharges();
                const computedAdults = persons.computedAdults;
                const chargeableChildren = persons.chargeableChildren;
                const countedPersons = computedAdults + chargeableChildren;

                const totalMaxAllowed = (baseCapacity + 2) * roomsCount;
                const extraCountTotal = Math.max(0, countedPersons - (baseCapacity * roomsCount));

                const adultsBeyondBaseTotal = Math.max(0, computedAdults - (baseCapacity * roomsCount));
                const adultExtraTotal = Math.min(adultsBeyondBaseTotal, extraCountTotal);
                let childrenExtraTotal = Math.max(0, extraCountTotal - adultExtraTotal);
                childrenExtraTotal = Math.min(childrenExtraTotal, chargeableChildren);

                const adultsChargePerNightTotal = adultExtraTotal * ADULT_PRICE;
                const childrenChargePerNightTotal = childrenExtraTotal * CHILD_PRICE;

                const addonsPerNight = computeAddonsPerNight();

                const basePerRoom = pricePerNight;
                const baseTotalPerNight = basePerRoom * roomsCount;

                const finalPerNight = baseTotalPerNight + adultsChargePerNightTotal + childrenChargePerNightTotal +
                    addonsPerNight;
                const total = finalPerNight * nights;

                priceBaseDisplay.innerText = fmtVnd(basePerRoom);
                priceAdultsDisplay.innerText = adultsChargePerNightTotal > 0 ? fmtVnd(adultsChargePerNightTotal) :
                    '0 đ';
                priceChildrenDisplay.innerText = childrenChargePerNightTotal > 0 ? fmtVnd(childrenChargePerNightTotal) :
                    '0 đ';
                const existingAddonsEl = document.getElementById('price_addons_display');
                if (existingAddonsEl) existingAddonsEl.innerText = addonsPerNight > 0 ? fmtVnd(addonsPerNight) : '0 đ';

                finalPerNightDisplay.innerText = fmtVnd(finalPerNight);
                totalDisplay.innerText = fmtVnd(total);
                payableDisplay.innerText = fmtVnd(total);

                validateGuestLimits(computedAdults, chargeableChildren, countedPersons, totalMaxAllowed);

                const finalPerNightInput = document.getElementById('final_per_night_input');
                const snapshotTotalInput = document.getElementById('snapshot_total_input');
                if (finalPerNightInput) finalPerNightInput.value = finalPerNight;
                if (snapshotTotalInput) snapshotTotalInput.value = total;

                const hiddenTotalInput = document.getElementById('hidden_tong_tien');
                if (hiddenTotalInput) hiddenTotalInput.value = total;
            }

            // ==== Server message & submit UX helpers ====

            function showToastInline(msg, isError = false, timeout = 2800) {
                try {
                    const d = document.createElement('div');
                    d.className = 'alert ' + (isError ? 'alert-danger' : 'alert-success') + ' position-fixed';
                    d.style.right = '20px';
                    d.style.bottom = '20px';
                    d.style.zIndex = 1150;
                    d.style.minWidth = '200px';
                    d.textContent = msg;
                    document.body.appendChild(d);
                    setTimeout(() => d.remove(), timeout);
                } catch (e) {
                    console.warn('Toast failed', e);
                }
            }

            (function handleServerMessagesOnLoad() {
                const successEl = document.getElementById('server_success');
                const errorEl = document.getElementById('server_error');
                const container = document.getElementById('server_message_container');

                if (successEl) {
                    const msg = successEl.getAttribute('data-message') || 'Booking created';
                    const datPhongId = successEl.getAttribute('data-datphong') || '';
                    // show inline banner at top of form
                    if (container) {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success';
                        let html = `<strong>Success:</strong> ${msg}`;
                        if (datPhongId) {
                            const url = "{{ route('account.booking.show', ['dat_phong' => '__ID__']) }}".replace(
                                '__ID__', datPhongId);
                            html +=
                            ` <a href="${url}" class="btn btn-sm btn-outline-primary ms-2">View booking</a>`;
                        }
                        alert.innerHTML = html;
                        container.appendChild(alert);
                    }
                    showToastInline(msg, false, 4000);
                }

                if (errorEl) {
                    const msg = errorEl.getAttribute('data-message') || 'Could not create booking';
                    if (container) {
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-danger';
                        alert.textContent = msg;
                        container.appendChild(alert);
                    }
                    showToastInline(msg, true, 5000);
                }
            })();

            (function setupSubmitUx() {
                const form = document.getElementById('bookingForm');
                if (!form) return;
                const submitBtn = form.querySelector('button[type="submit"]');
                if (!submitBtn) return;

                form.addEventListener('submit', function(e) {
                    if (submitBtn.disabled) {
                        e.preventDefault();
                        return;
                    }

                    if (typeof currentAvailableRooms !== 'undefined' && Number(currentAvailableRooms) <=
                        0) {
                        e.preventDefault();
                        if (availabilityMessageEl) {
                            availabilityMessageEl.className = 'small mt-2 text-danger';
                            if (!availabilityMessageEl.innerText.trim()) {
                                availabilityMessageEl.innerText =
                                    'Unable to book: There are currently no rooms available for the selected date range.';
                            }
                        }
                        showToastInline('Unable to book: There are currently no rooms available for the selected date range.',
                            true, 3500);
                        return;
                    }

                    submitBtn.disabled = true;
                    submitBtn.dataset.origHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                });

                ['ngay_nhan_phong', 'ngay_tra_phong', 'rooms_count'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.addEventListener('change', () => {
                        if (submitBtn && submitBtn.disabled) {
                            submitBtn.disabled = false;
                            if (submitBtn.dataset.origHtml) submitBtn.innerHTML = submitBtn.dataset
                                .origHtml;
                        }
                    });
                });
            })();

            // bind events
            document.querySelectorAll('.addon-checkbox').forEach(chk => chk.addEventListener('change', updateSummary));
            if (Array.isArray(initialSelectedAddons) && initialSelectedAddons.length) {
                document.querySelectorAll('.addon-checkbox').forEach(chk => {
                    chk.checked = initialSelectedAddons.includes(String(chk.value)) || initialSelectedAddons
                        .includes(Number(chk.value));
                });
            }
            if (adultsInput) adultsInput.addEventListener('input', updateSummary);
            if (childrenInput) childrenInput.addEventListener('input', function() {
                renderChildrenAges();
            });
            if (roomsInput) {
                roomsInput.addEventListener('input', updateSummary);
                roomsInput.addEventListener('change', updateSummary);
            }

            // init
            updateInputLimitsByRooms();
            renderChildrenAges();
            updateSummary();

            updateRoomsAvailability();
        })();

        document.addEventListener('DOMContentLoaded', function() {
            const selectMethod = document.querySelector('select[name="phuong_thuc"]');
            const vnpayWrapper = document.getElementById('vnpay_button_wrapper');
            const vnpayBtn = document.getElementById('pay_vnpay_btn');

            selectMethod.addEventListener('change', function() {
                vnpayWrapper.style.display = (this.value === 'vnpay') ? 'block' : 'none';
            });

            vnpayBtn.addEventListener('click', async function(e) {
                e.preventDefault();

                // 🔹 Lấy thông tin phòng từ form
                const phongId = document.querySelector('input[name="phong_id"]')
                    .value; // id phòng đang xem
                const ngayNhan = document.querySelector('input[name="ngay_nhan"]').value;
                const ngayTra = document.querySelector('input[name="ngay_tra"]').value;
                const tongTien = document.getElementById('hidden_tong_tien').value;

                try {
                    const response = await fetch("{{ route('payment.initiate') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                        },
                        body: JSON.stringify({
                            phong_id: phongId,
                            ngay_nhan: ngayNhan,
                            ngay_tra: ngayTra,
                            amount: tongTien,
                        }),
                    });

                    const data = await response.json();
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        alert(data.error || 'Không thể khởi tạo thanh toán.');
                        console.error(data);
                    }
                } catch (err) {
                    alert('Lỗi khi tạo thanh toán: ' + err.message);
                }
            });
        });
    </script>
@endpush
