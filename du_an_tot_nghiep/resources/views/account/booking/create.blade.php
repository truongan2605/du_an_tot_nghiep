@extends('layouts.app')

@section('title', 'Đặt phòng - ' . ($phong->name ?? $phong->ma_phong))

@section('content')
    @php
        // ====== Giá cơ bản thống nhất: dùng gia_cuoi_cung (fallback về gia_mac_dinh) ======
        $basePrice = (float) ($phong->gia_cuoi_cung ?? ($phong->gia_mac_dinh ?? 0));

        // ====== Sức chứa cơ bản ======
        $roomCapacity = 0;
        foreach ($phong->bedTypes as $bt) {
            $qty = (int) ($bt->pivot->quantity ?? 0);
            $cap = (int) ($bt->capacity ?? 1);
            $roomCapacity += $qty * $cap;
        }
        $baseCapacity = (int) ($phong->suc_chua ?? ($phong->loaiPhong->suc_chua ?? ($roomCapacity ?: 1)));

        // ====== Prefill từ query (từ list-room/detail-room) ======
        // ưu tiên old() -> request() -> fallback
        $qFrom = request('ngay_nhan_phong');
        $qTo = request('ngay_tra_phong');

        $defaultFrom = old('ngay_nhan_phong', $qFrom ?: \Carbon\Carbon::today()->format('Y-m-d'));
        $defaultTo = old('ngay_tra_phong', $qTo ?: \Carbon\Carbon::tomorrow()->format('Y-m-d'));

        $defaultRooms = (int) old('rooms_count', request('rooms_count', 1));
        if ($defaultRooms < 1) {
            $defaultRooms = 1;
        }

        $defaultAdults = (int) old('adults', request('adults', min(2, max(1, $baseCapacity))));
        if ($defaultAdults < 1) {
            $defaultAdults = 1;
        }

        $defaultChildren = (int) old('children', request('children', 0));
        if ($defaultChildren < 0) {
            $defaultChildren = 0;
        }

        // ====== Giữ lại tham số để quay về chi tiết phòng (nếu cần) ======
        $backToDetailParams = http_build_query(request()->only(['date_range', 'adults', 'children', 'rooms_count']));
    @endphp

    <main>
        <!-- Modal Xác Nhận Thanh Toán VNPAY -->
        <div class="modal fade" id="vnpayConfirmModal" tabindex="-1" aria-labelledby="vnpayConfirmModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="vnpayConfirmModalLabel">Xác Nhận Thanh Toán VNPAY</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6 class="fw-bold">Thông Tin Nội Dung Cần Thanh Toán - Tóm Tắt Thanh Toán</h6>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Giá phòng / đêm</span>
                                <span id="modal_price_base"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Phí người lớn thêm / đêm</span>
                                <span id="modal_price_adults"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Phí trẻ em thêm / đêm</span>
                                <span id="modal_price_children"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between" hidden>
                                <span hidden>Dịch vụ bổ sung / đêm</span>
                                <span hidden id="modal_price_addons"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Giá cuối cùng / đêm</span>
                                <span id="modal_final_per_night"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Số đêm</span>
                                <span id="modal_nights_count"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between fw-bold">
                                <span>Tổng tiền</span>
                                <span id="modal_total_snapshot"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between fw-bold text-primary">
                                <span id="modal_deposit_label">Đặt cọc (50%)</span>
                                <span id="modal_payable_now"></span>
                            </li>
                        </ul>
                        <hr>
                        <h6 class="fw-bold">Nội Quy Hoàn Tiền</h6>
                        <p>Hoàn tiền 100% trong vòng 24h khi đặt cọc trước khi check-in.</p>
                        <h6 class="fw-bold">Chính Sách Trong Khách Sạn</h6>
                        <p>Được phép uống rượu và hút thuốc trong phạm vi được kiểm soát tại khu vực phòng nhưng
                            vui lòng không gây bừa bộn hoặc ồn ào trong phòng.</p>
                        <p>Ma túy và các sản phẩm bất hợp pháp gây say bị cấm và không được mang vào nhà hoặc tiêu
                            thụ.</p>
                        <p>Đối với bất kỳ bản cập nhật nào, khách hàng sẽ phải trả phí hủy/sửa đổi áp dụng.</p>
                        <p>Nhận phòng: 14:00</p>
                        <p>Trả phòng: 12:00.</p>
                        <p>Tự làm thủ tục nhận phòng với nhân viên tòa khách sạn.</p>
                        <p>Không được phép mang vật nuôi bên ngoài khách sạn.</p>
                        <p>Được sử dụng thuốc lá.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" id="vnpayProceedBtn">
                            Xác Nhận
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <section class="py-0">
            <div class="container">
                <div class="card bg-light overflow-hidden px-sm-5">
                    <div class="row align-items-center g-4">
                        <div class="col-sm-9">
                            <div class="card-body">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb breadcrumb-dots mb-0">
                                        <li class="breadcrumb-item">
                                            <a href="{{ route('home') }}">
                                                <i class="bi bi-house me-1"></i> Trang chủ
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <a
                                                href="{{ route('rooms.show', $phong->id) }}@if ($backToDetailParams) ?{{ $backToDetailParams }} @endif">
                                                Chi tiết phòng
                                            </a>
                                        </li>
                                        <li class="breadcrumb-item active">Đặt phòng</li>
                                    </ol>
                                </nav>
                                <h1 class="m-0 h2 card-title">Xem lại đặt phòng của bạn</h1>
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
                                    <h3 class="mb-0">
                                        <i class="fa-solid fa-hotel me-2"></i>Thông tin phòng
                                    </h3>
                                </div>

                                <div class="card-body p-4">
                                    <form action="{{ route('account.booking.store') }}" method="POST" id="bookingForm">
                                        @csrf

                                        {{-- Server-side messages --}}
                                        <div id="server_message_container" class="mb-3">
                                            @if (session('success'))
                                                <div id="server_success" data-message="{{ e(session('success')) }}"
                                                    data-datphong="{{ session('dat_phong_id') ?? '' }}"></div>
                                            @endif

                                            @if ($errors->any())
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
                                                        <label class="form-label">Nhận phòng - Trả phòng</label>

                                                        {{-- Input hiển thị (flatpickr sẽ tạo altInput đẹp) --}}
                                                        <input id="date_range" type="text" class="form-control flatpickr"
                                                            placeholder="Chọn khoảng thời gian" readonly>

                                                        {{-- Hidden submit về server (đúng key dự án) --}}
                                                        <input type="hidden" name="ngay_nhan_phong" id="ngay_nhan_phong"
                                                            value="{{ $defaultFrom }}">
                                                        <input type="hidden" name="ngay_tra_phong" id="ngay_tra_phong"
                                                            value="{{ $defaultTo }}">

                                                        <small class="text-muted">
                                                            Giờ nhận phòng: 14:00 – Giờ trả phòng: 12:00
                                                        </small>

                                                        <div id="weekend_notice" class="small mt-1 text-danger"
                                                            style="display:none;"></div>
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

                                            <!-- Guests -->
                                            <div class="col-lg-6">
                                                <div class="bg-light py-3 px-4 rounded-3">
                                                    <h6 class="fw-light small mb-1">Khách</h6>
                                                    <div class="row g-2 mb-2">
                                                        <div class="col-6">
                                                            <label class="form-label">Người lớn</label>
                                                            <input type="number" name="adults" id="adults"
                                                                class="form-control" min="1"
                                                                value="{{ $defaultAdults }}">
                                                            <small id="adults_help" class="text-muted d-block">
                                                                Số người tối đa:
                                                                <strong
                                                                    id="room_capacity_display">{{ ($baseCapacity + 2) * $defaultRooms }}</strong>
                                                            </small>
                                                        </div>

                                                        <div class="col-6">
                                                            <label class="form-label">Trẻ em</label>
                                                            <input type="number" name="children" id="children"
                                                                class="form-control" min="0"
                                                                value="{{ $defaultChildren }}">
                                                            <small id="children_help" class="text-muted d-block">
                                                                Tối đa 2 trẻ em mỗi phòng.
                                                            </small>
                                                        </div>

                                                        <div class="col-6">
                                                            <label class="form-label">Số phòng</label>
                                                            <input type="number" name="rooms_count" id="rooms_count"
                                                                class="form-control" min="1"
                                                                max="{{ $availableRoomsDefault ?? 1 }}"
                                                                value="{{ old('rooms_count', $defaultRooms) }}">
                                                            <small class="text-muted d-block">
                                                                Có sẵn cho ngày đã chọn:
                                                                <strong
                                                                    id="available_rooms_display">{{ $availableRoomsDefault ?? 0 }}</strong>
                                                                phòng
                                                            </small>
                                                        </div>
                                                    </div>

                                                    <div id="children_ages_container" class="mb-2"></div>

                                                    <div class="mt-3">
                                                        <i class="fa-solid fa-bed"></i>
                                                        <strong>Giường trong phòng:</strong>
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
                                                                            <div class="small text">
                                                                                Số lượng: {{ $bt->pivot->quantity }}
                                                                            </div>
                                                                            <div class="small text">
                                                                                Giá/giường:
                                                                                {{ number_format($bt->price, 0, ',', '.') }}
                                                                                đ/đêm
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                            @empty
                                                                <li><em>Không có giường nào được cấu hình cho phòng
                                                                        này.</em></li>
                                                            @endforelse
                                                        </ul>
                                                    </div>

                                                    <input type="hidden" name="so_khach" id="so_khach"
                                                        value="{{ old('so_khach', $phong->suc_chua ?? 1) }}">
                                                    <div class="small text">
                                                        Phòng cho: {{ $phong->suc_chua ?? ($roomCapacity ?? '-') }} người
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card border mt-4">
                                            <div class="card-header border-bottom d-md-flex justify-content-md-between">
                                                <h5 class="card-title mb-0">
                                                    {{ $phong->name ?? ($phong->loaiPhong->ten ?? 'Room') }}
                                                </h5>
                                            </div>

                                            <div class="card-body">
                                                <h6>Tiện ích</h6>
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
                                                    <h6 class="mb-2" hidden>Dịch vụ bổ sung</h6>
                                                    @if (isset($availableAddons) && $availableAddons->count())
                                                        <ul class="list-unstyled">
                                                            @foreach ($availableAddons as $addon)
                                                                <li class="mb-2" hidden>
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
                                                                            <div class="small text">
                                                                                +
                                                                                {{ number_format($addon->gia ?? 0, 0, ',', '.') }}
                                                                                đ / đêm
                                                                            </div>
                                                                        </span>
                                                                    </label>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                @else
                                                    <p class="mb-0"><em>Không có tiện ích nào được liệt kê cho phòng
                                                            này.</em></p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="card shadow mt-4">
                                            <div class="card-header border-bottom p-4">
                                                <h4 class="card-title mb-0">
                                                    <i class="bi bi-people-fill me-2"></i>Thông tin khách hàng
                                                </h4>
                                            </div>

                                            <div class="card-body p-4">
                                                @php $u = $user ?? auth()->user(); @endphp

                                                <div class="mb-3">
                                                    <label class="form-label">Họ và tên</label>
                                                    <input type="text" name="name"
                                                        class="form-control form-control-lg"
                                                        value="{{ old('name', $u->name ?? '') }}" required>
                                                    @error('name')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Địa chỉ</label>
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
                                                        <label class="form-label">Số điện thoại</label>
                                                        <input type="text" name="phone" class="form-control"
                                                            value="{{ old('phone', $u->so_dien_thoai ?? '') }}" required>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Ghi chú</label>
                                                    <textarea name="ghi_chu" class="form-control" rows="3" placeholder="Ghi chú đặc biệt cho khách sạn">{{ old('ghi_chu') }}</textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <input type="hidden" name="phong_id" value="{{ $phong->id }}">
                                                    <input type="hidden" name="tong_tien" id="hidden_tong_tien"
                                                        value="{{ (int) $basePrice }}">
                                                    <input type="hidden" name="deposit_amount" id="hidden_deposit"
                                                        value="0">

                                                    {{-- voucher gửi lên server --}}
                                                    <input type="hidden" name="voucher_id" id="voucher_id_input"
                                                        value="">
                                                    <input type="hidden" name="voucher_discount"
                                                        id="voucher_discount_input" value="">
                                                    <input type="hidden" name="ma_voucher" id="voucher_code_input"
                                                        value="">

                                                    {{-- GIÁ GỐC (KHÔNG VOUCHER) --}}
                                                    <input type="hidden" id="original_total" value="0">
                                                    <input type="hidden" id="original_deposit" value="0">

                                                    <div class="mt-3">
                                                        <label class="form-label fw-bold">
                                                            <i class="bi bi-percent me-1"></i>Chọn hình thức thanh toán
                                                        </label>
                                                        <div class="card border">
                                                            <div class="card-body p-3">
                                                                <div class="form-check mb-2">
                                                                    <input type="radio" name="deposit_percentage"
                                                                        value="50" class="form-check-input"
                                                                        id="deposit_50" checked>
                                                                    <label for="deposit_50" class="form-check-label">
                                                                        <strong>Đặt cọc 50%</strong>
                                                                        <small class="text-muted d-block">
                                                                            Thanh toán phần còn lại khi check-in
                                                                        </small>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input type="radio" name="deposit_percentage"
                                                                        value="100" class="form-check-input"
                                                                        id="deposit_100">
                                                                    <label for="deposit_100" class="form-check-label">
                                                                        <strong>Thanh toán toàn bộ 100%</strong>
                                                                        <small class="text-muted d-block">
                                                                            Thanh toán ngay - Không cần thanh toán thêm
                                                                        </small>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Refund Policy Information --}}
                                                    <div class="alert alert-info mt-3 mb-0">
                                                        <h6 class="alert-heading">
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            Chính sách hủy phòng &amp; hoàn tiền
                                                        </h6>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered mb-2">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th class="small">Thời gian hủy</th>
                                                                        <th class="small text-center">Đặt cọc 50%</th>
                                                                        <th class="small text-center">Thanh toán 100%</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="small">≥ 7 ngày trước check-in</td>
                                                                        <td class="text-center"><span
                                                                                class="badge bg-success">Hoàn 100%</span>
                                                                        </td>
                                                                        <td class="text-center"><span
                                                                                class="badge bg-success">Hoàn 90%</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="small">3-6 ngày trước</td>
                                                                        <td class="text-center"><span
                                                                                class="badge bg-warning text-dark">Hoàn
                                                                                70%</span></td>
                                                                        <td class="text-center"><span
                                                                                class="badge bg-warning text-dark">Hoàn
                                                                                60%</span></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="small">1-2 ngày trước</td>
                                                                        <td class="text-center"><span
                                                                                class="badge bg-warning text-dark">Hoàn
                                                                                30%</span></td>
                                                                        <td class="text-center"><span
                                                                                class="badge bg-warning text-dark">Hoàn
                                                                                40%</span></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="small">&lt; 24 giờ</td>
                                                                        <td class="text-center"><span
                                                                                class="badge bg-danger">Không hoàn</span>
                                                                        </td>
                                                                        <td class="text-center"><span
                                                                                class="badge bg-warning text-dark">Hoàn
                                                                                20%</span></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            <strong>Lưu ý:</strong> Thanh toán 100% ngay được ưu đãi thêm
                                                            khi hủy phòng
                                                        </small>
                                                    </div>

                                                    <div class="mt-3">
                                                        <label for="phuong_thuc" class="form-label">Phương thức thanh
                                                            toán</label>
                                                        <select name="phuong_thuc" id="phuong_thuc" class="form-select"
                                                            required>
                                                            <option value="">Chọn phương thức</option>
                                                            <option value="vnpay"
                                                                {{ old('phuong_thuc') == 'vnpay' ? 'selected' : '' }}>
                                                                Thanh toán bằng VNPAY
                                                            </option>
                                                            <option value="momo"
                                                                {{ old('phuong_thuc') == 'momo' ? 'selected' : '' }}>
                                                                Thanh toán bằng MoMo
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <input type="hidden" name="final_per_night" id="final_per_night_input"
                                                    value="">
                                                <input type="hidden" name="snapshot_total" id="snapshot_total_input"
                                                    value="">

                                                <div class="mt-3">
                                                    <button type="submit" class="btn btn-lg btn-primary">Xác
                                                        nhận</button>
                                                    <a href="{{ route('rooms.show', $phong->id) }}@if ($backToDetailParams) ?{{ $backToDetailParams }} @endif"
                                                        class="btn btn-secondary ms-2">
                                                        Hủy
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="col-xl-4">
                        <div class="position-sticky" style="top: 80px;">
                            <div class="row g-4">
                                <!-- Price summary START -->
                                <div class="col-md-6 col-xl-12">
                                    <div class="card shadow rounded-3 border-0">
                                        <div class="card-header bg-light border-bottom py-3">
                                            <h5 class="card-title mb-0 fw-bold text-primary">
                                                <i class="bi bi-cash-stack me-2"></i>Tóm tắt giá
                                            </h5>
                                        </div>

                                        <div class="card-body">
                                            <ul class="list-group list-group-borderless mb-3">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-light">Căn phòng / đêm</span>
                                                    <span class="fw-semibold" id="price_base_display">
                                                        {{ number_format($basePrice, 0, ',', '.') }} đ
                                                    </span>
                                                </li>

                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-light">Người lớn thêm / đêm</span>
                                                    <span id="price_adults_display">-</span>
                                                </li>

                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-light">Trẻ em thêm / đêm</span>
                                                    <span id="price_children_display">-</span>
                                                </li>

                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-light">Dịch vụ bổ sung / đêm</span>
                                                    <span id="price_addons_display">0 đ</span>
                                                </li>

                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-light">Giá cuối cùng / đêm</span>
                                                    <span id="final_per_night_display">-</span>
                                                </li>

                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span class="fw-light">Đêm</span>
                                                    <span class="fw-semibold" id="nights_count_display">-</span>
                                                </li>

                                                <li
                                                    class="list-group-item d-flex justify-content-between border-top pt-2 mt-1">
                                                    <span class="fw-bold text-dark">Tổng</span>
                                                    <span class="fs-5 fw-bold text-dark"
                                                        id="total_snapshot_display">-</span>
                                                </li>
                                            </ul>

                                            {{-- Áp dụng mã giảm giá --}}
                                            <div class="voucher-section mt-4">
                                                <h6 class="fw-bold mb-3">
                                                    <i class="bi bi-ticket-perforated me-1 text-success"></i>
                                                    Áp dụng mã giảm giá
                                                </h6>

                                                <div class="input-group mb-3">
                                                    <input type="text" id="voucher_code"
                                                        class="form-control rounded-start"
                                                        placeholder="Nhập hoặc chọn mã giảm giá">
                                                    <button class="btn btn-success rounded-end px-4" id="applyVoucherBtn">
                                                        Áp dụng
                                                    </button>
                                                </div>

                                                @if (Auth::check())
                                                    @if ($vouchers->count() > 0)
                                                        <div class="border rounded p-3 bg-light"
                                                            style="max-height: 180px; overflow-y: auto;">
                                                            <small class="text-muted fw-bold d-block mb-2">Voucher của
                                                                bạn:</small>

                                                            @foreach ($vouchers as $voucher)
                                                                @php
                                                                    // $vouchers đã được lọc ở controller (active + còn hạn + còn lượt),
                                                                    // giữ check này để an toàn nếu sau này logic thay đổi
                                                                    $isExpired = \Carbon\Carbon::parse(
                                                                        $voucher->end_date,
                                                                    )->isPast();
                                                                @endphp

                                                                <label
                                                                    class="d-flex justify-content-between align-items-center p-2 mb-2 rounded border {{ $isExpired ? 'bg-light text-muted' : 'bg-white shadow-sm' }}"
                                                                    style="cursor: pointer; transition: 0.2s">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input voucher-checkbox"
                                                                            type="checkbox" value="{{ $voucher->code }}"
                                                                            data-code="{{ $voucher->code }}"
                                                                            {{ $isExpired ? 'disabled' : '' }}>
                                                                        <div class="ms-2">
                                                                            <span
                                                                                class="fw-semibold text-primary d-block">{{ $voucher->name }}</span>
                                                                            <small class="text-muted d-block">Mã:
                                                                                {{ $voucher->code }}</small>
                                                                            {{-- NEW: Hiển thị giá trị voucher --}}
                                                                            <small class="text-muted d-block">
                                                                                Giảm: <span
                                                                                    class="fw-semibold">{{ $voucher->display_value }}</span>
                                                                            </small>
                                                                            
                                                                            <small class="text-muted">
                                                                                {{ \Carbon\Carbon::parse($voucher->start_date)->format('d/m') }}
                                                                                -
                                                                                {{ \Carbon\Carbon::parse($voucher->end_date)->format('d/m') }}
                                                                            </small>
                                                                        </div>
                                                                    </div>

                                                                    <span
                                                                        class="badge {{ $isExpired ? 'bg-secondary' : 'bg-success' }} px-2 py-1 rounded-pill">
                                                                        {{ $isExpired ? 'Hết hạn' : 'Dùng được' }}
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        {{-- Không có voucher dùng được --}}
                                                        <div class="border rounded p-3 bg-light">
                                                            <p class="text-muted small mb-2">Bạn không có voucher nào.</p>
                                                            <a href="{{ route('client.vouchers.index') }}"
                                                                target="_blank" class="btn btn-outline-success btn-sm">
                                                                Nhận voucher
                                                            </a>
                                                        </div>
                                                    @endif
                                                @else
                                                    {{-- Chưa đăng nhập --}}
                                                    <div class="border rounded p-3 bg-light">
                                                        <p class="text-muted small mb-2">Vui lòng đăng nhập để sử dụng
                                                            voucher.</p>
                                                        <a href="{{ route('login') }}"
                                                            class="btn btn-outline-primary btn-sm">
                                                            Đăng nhập
                                                        </a>
                                                    </div>
                                                @endif


                                                <div id="voucherResult" class="mt-3"></div>
                                            </div>
                                        </div>

                                        <div class="card-footer border-top bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="h6 mb-0 fw-bold" id="deposit_percentage_label">Đặt cọc
                                                    (50%)</span>
                                                <span class="h6 mb-0 fw-bold text-primary"
                                                    id="payable_now_display">-</span>
                                            </div>
                                            <small class="text-muted d-block mt-1 fst-italic" id="remaining_info">
                                                Phần còn lại (50%) thanh toán tại khách sạn khi check in
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <!-- Price summary END -->
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        // ====== PHẦN XỬ LÝ VOUCHER (UI) ======
        document.addEventListener('DOMContentLoaded', function() {
            function resetVoucherUI() {
                const codeInput = document.getElementById('voucher_code');
                const resultBox = document.getElementById('voucherResult');
                const originalTotalInput = document.getElementById('original_total');
                const originalDepositInput = document.getElementById('original_deposit');

                const total = parseInt(originalTotalInput?.value || '0', 10) || 0;
                const deposit = parseInt(originalDepositInput?.value || '0', 10) || 0;

                const voucherIdInput = document.getElementById('voucher_id_input');
                const voucherDiscountInput = document.getElementById('voucher_discount_input');
                const voucherCodeHiddenInput = document.getElementById('voucher_code_input');

                if (voucherIdInput) voucherIdInput.value = '';
                if (voucherDiscountInput) voucherDiscountInput.value = '';
                if (voucherCodeHiddenInput) voucherCodeHiddenInput.value = '';

                if (codeInput) codeInput.value = '';
                document.querySelectorAll('.voucher-checkbox').forEach(cb => cb.checked = false);

                const hiddenTotal = document.getElementById('hidden_tong_tien');
                const hiddenDeposit = document.getElementById('hidden_deposit');
                const snapshotTotalInput = document.getElementById('snapshot_total_input');

                if (hiddenTotal) hiddenTotal.value = total;
                if (snapshotTotalInput) snapshotTotalInput.value = total;
                if (hiddenDeposit) hiddenDeposit.value = deposit;

                const totalDisplayEl = document.getElementById('total_snapshot_display');
                const payableDisplay = document.getElementById('payable_now_display');

                const fmtVnd = (num) => new Intl.NumberFormat('vi-VN').format(Math.round(num)) + ' đ';

                if (totalDisplayEl) totalDisplayEl.innerText = total > 0 ? fmtVnd(total) : '-';
                if (payableDisplay) payableDisplay.innerText = deposit > 0 ? fmtVnd(deposit) : '-';

                if (resultBox) resultBox.innerHTML = '';

                if (window.bookingUpdateSummary) window.bookingUpdateSummary();
            }

            document.querySelectorAll('.voucher-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const codeInput = document.getElementById('voucher_code');
                    if (this.checked) {
                        document.querySelectorAll('.voucher-checkbox').forEach(other => {
                            if (other !== this) other.checked = false;
                        });
                        if (codeInput) codeInput.value = this.dataset.code || this.value;
                    } else if (codeInput) {
                        codeInput.value = '';
                    }
                });
            });

            const applyBtn = document.getElementById('applyVoucherBtn');
            if (applyBtn) {
                applyBtn.addEventListener('click', function() {
                    const codeInput = document.getElementById('voucher_code');
                    const resultBox = document.getElementById('voucherResult');
                    const originalTotalInput = document.getElementById('original_total');

                    const code = (codeInput?.value || '').trim();
                    if (!code) {
                        resetVoucherUI();
                        return;
                    }

                    let total = 0;
                    if (originalTotalInput && originalTotalInput.value) {
                        total = parseInt(originalTotalInput.value, 10) || 0;
                    }

                    if (!total || total <= 0) {
                        if (resultBox) {
                            resultBox.innerHTML = `
                                <div class="alert alert-danger p-2">
                                    Giá trị đơn hàng không hợp lệ. Vui lòng chọn ngày, số phòng và khách trước khi áp dụng mã.
                                </div>`;
                        }
                        return;
                    }

                    fetch('{{ route('booking.apply-voucher') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                code: code,
                                total: total
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (!resultBox) return;

                            const fmtVndLocal = (num) =>
                                new Intl.NumberFormat('vi-VN').format(Math.round(num)) + ' đ';

                            if (data.success) {
                                const discountAmount = Number(data.discount || 0);
                                const discountText = discountAmount > 0 ? fmtVndLocal(discountAmount) :
                                    '0 đ';

                                const finalTotalLocal = Math.max(0, total - discountAmount);
                                const finalTextLocal = fmtVndLocal(finalTotalLocal);

                                resultBox.innerHTML = `
                                <div class="alert alert-success p-2">
                                    ✅ Áp dụng thành công <strong>${data.voucher_name || code}</strong><br>
                                    Giảm: <strong>${discountText}</strong><br>
                                    Tổng mới (ước tính): <strong>${finalTextLocal}</strong><br>
                                    <small class="text-muted">Tiền cọc sẽ được tính lại theo % bạn chọn (50% hoặc 100%).</small>
                                </div>`;

                                const voucherIdInput = document.getElementById('voucher_id_input');
                                if (voucherIdInput && data.voucher_id) voucherIdInput.value = data
                                    .voucher_id;

                                const voucherDiscountInput = document.getElementById(
                                    'voucher_discount_input');
                                if (voucherDiscountInput && typeof data.discount !== 'undefined') {
                                    voucherDiscountInput.value = data.discount;
                                }

                                const voucherCodeHiddenInput = document.getElementById(
                                    'voucher_code_input');
                                if (voucherCodeHiddenInput) voucherCodeHiddenInput.value = data
                                    .voucher_code || code;

                                if (window.bookingUpdateSummary) window.bookingUpdateSummary();
                            } else {
                                resultBox.innerHTML =
                                    `<div class="alert alert-danger p-2">${data.message || 'Không áp dụng được mã giảm giá.'}</div>`;
                                const voucherIdInput = document.getElementById('voucher_id_input');
                                const voucherDiscountInput = document.getElementById(
                                    'voucher_discount_input');
                                const voucherCodeHiddenInput = document.getElementById(
                                    'voucher_code_input');
                                if (voucherIdInput) voucherIdInput.value = '';
                                if (voucherDiscountInput) voucherDiscountInput.value = '';
                                if (voucherCodeHiddenInput) voucherCodeHiddenInput.value = '';

                                if (window.bookingUpdateSummary) window.bookingUpdateSummary();
                            }
                        })
                        .catch(() => {
                            if (resultBox) resultBox.innerHTML =
                                `<div class="alert alert-danger p-2">Có lỗi xảy ra khi áp dụng mã giảm giá.</div>`;
                            const voucherIdInput = document.getElementById('voucher_id_input');
                            const voucherDiscountInput = document.getElementById(
                                'voucher_discount_input');
                            const voucherCodeHiddenInput = document.getElementById(
                            'voucher_code_input');
                            if (voucherIdInput) voucherIdInput.value = '';
                            if (voucherDiscountInput) voucherDiscountInput.value = '';
                            if (voucherCodeHiddenInput) voucherCodeHiddenInput.value = '';

                            if (window.bookingUpdateSummary) window.bookingUpdateSummary();
                        });
                });
            }
        });

        // ====== PHẦN LOGIC ĐẶT PHÒNG (giá, khách, VNPAY) ======
        (function() {
            const initialChildrenAges = {!! json_encode(old('children_ages', [])) !!};
            const initialChildrenCount = Number({{ (int) old('children', $defaultChildren) }});
            const initialSelectedAddons = {!! json_encode(old('addons', [])) !!};

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
            const weekendNoticeEl = document.getElementById('weekend_notice');

            const pricePerNight = Number({!! json_encode($basePrice) !!});
            const baseCapacity = Number({{ $baseCapacity }});
            const ADULT_PRICE = {{ \App\Http\Controllers\Client\BookingController::ADULT_PRICE }};
            const CHILD_PRICE = {{ \App\Http\Controllers\Client\BookingController::CHILD_PRICE }};
            const CHILD_FREE_AGE = {{ \App\Http\Controllers\Client\BookingController::CHILD_FREE_AGE }};
            const WEEKEND_MULTIPLIER = 1.10;

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

            function clampNumberInput(el, min, max) {
                if (!el) return;
                let v = Number(el.value || 0);
                if (isNaN(v)) v = min;
                if (v < min) el.value = min;
                else if (v > max) el.value = max;
            }

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

            // ====== flatpickr đồng nhất (range + altInput hiển thị đẹp) ======
            if (typeof flatpickr !== 'undefined' && dateRangeInput) {
                if (dateRangeInput._flatpickr) dateRangeInput._flatpickr.destroy();

                flatpickr(dateRangeInput, {
                    mode: "range",
                    minDate: "today",
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d M Y",
                    altInputClass: dateRangeInput.className,
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

                if (fromInput.value && toInput.value) {
                    setHiddenDates([new Date(fromInput.value), new Date(toInput.value)]);
                }
            }

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
                    if (!res.ok) return;

                    const data = await res.json();
                    const avail = Number(data.available || 0);
                    currentAvailableRooms = avail;

                    if (availDisplayEl) availDisplayEl.innerText = avail;
                    if (roomsInput) {
                        roomsInput.max = avail;
                        if (Number(roomsInput.value || 0) > avail) roomsInput.value = Math.max(1, avail);
                    }

                    if (avail === 0) {
                        if (availabilityMessageEl) {
                            availabilityMessageEl.className = 'small mt-2 text-danger';
                            availabilityMessageEl.innerText =
                                `Phòng {{ $phong->ma_phong }} không khả dụng trong khoảng thời gian đã chọn.`;
                        }
                        toggleSubmit(false);
                    } else {
                        if (availabilityMessageEl) availabilityMessageEl.innerText = '';
                        toggleSubmit(true);
                    }

                    updateSummary();
                } catch (err) {
                    console.error(err);
                }
            }

            function toggleSubmit(enabled) {
                const form = document.getElementById('bookingForm');
                if (!form) return;
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = !enabled;
            }

            function renderChildrenAges() {
                const count = Number(childrenInput.value || initialChildrenCount || 0);
                childrenAgesContainer.innerHTML = '';

                for (let i = 0; i < count; i++) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-2 child-age-wrapper';
                    const initialVal = (Array.isArray(initialChildrenAges) && typeof initialChildrenAges[i] !==
                            'undefined') ?
                        Number(initialChildrenAges[i]) : 0;

                    wrapper.innerHTML = `
                        <label class="form-label">Tuổi trẻ em ${i + 1}</label>
                        <input type="number" name="children_ages[]" class="form-control child-age-input" min="0" max="12" value="${initialVal}" />
                        <div class="small text-danger mt-1 age-error" style="display:none;"></div>
                    `;
                    childrenAgesContainer.appendChild(wrapper);
                }

                document.querySelectorAll('.child-age-input').forEach((el) => {
                    el.addEventListener('input', function() {
                        let v = Number(this.value);
                        if (isNaN(v)) v = 0;
                        if (v < 0) this.value = 0;
                        if (v > 12) this.value = 12;
                        updateSummary();
                    });
                });

                updateSummary();
            }

            // Đồng nhất rule: adults max = (baseCapacity+2)*rooms, children max = rooms*2
            function updateInputLimitsByRooms() {
                const rooms = Number(roomsInput ? (roomsInput.value || 1) : 1);
                const safeRooms = Math.max(1, isNaN(rooms) ? 1 : rooms);

                const adultsMax = (baseCapacity + 2) * safeRooms;
                const childrenMax = 2 * safeRooms;

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
                    let v = Number(x.value || 0);
                    if (isNaN(v)) v = 0;
                    if (v < 0) v = 0;
                    if (v > 12) v = 12;
                    return v;
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

            function countWeekendNights(fromDate, toDate) {
                const cursor = new Date(fromDate.getTime());
                const end = new Date(toDate.getTime());
                let count = 0;
                while (cursor < end) {
                    const day = cursor.getDay();
                    if (day === 5 || day === 6 || day === 0) count++;
                    cursor.setDate(cursor.getDate() + 1);
                }
                return count;
            }

            function updateSummary() {
                const fromVal = fromInput.value;
                const toVal = toInput.value;

                const snapshotTotalInput = document.getElementById('snapshot_total_input');
                const hiddenTotalInput = document.getElementById('hidden_tong_tien');
                const hiddenDepositInput = document.getElementById('hidden_deposit');
                const originalTotalInput = document.getElementById('original_total');
                const originalDepositInput = document.getElementById('original_deposit');
                const voucherDiscountInput = document.getElementById('voucher_discount_input');

                if (!fromVal || !toVal) return;

                const from = new Date(fromVal + 'T00:00:00');
                const to = new Date(toVal + 'T00:00:00');
                const nights = Math.max(0, Math.round((to - from) / (1000 * 60 * 60 * 24)));
                if (nights <= 0) return;

                nightsDisplay.innerText = nights;

                let roomsCount = Number(roomsInput ? (roomsInput.value || 1) : 1);
                if (isNaN(roomsCount) || roomsCount < 1) roomsCount = 1;

                updateInputLimitsByRooms();

                const persons = computePersonCharges();
                const computedAdults = persons.computedAdults;
                const chargeableChildren = persons.chargeableChildren;
                const countedPersons = computedAdults + chargeableChildren;

                const totalMaxAllowed = (baseCapacity + 2) * roomsCount;

                // extra logic như cũ
                const extraCountTotal = Math.max(0, countedPersons - (baseCapacity * roomsCount));
                const adultsBeyondBaseTotal = Math.max(0, computedAdults - (baseCapacity * roomsCount));
                const adultExtraTotal = Math.min(adultsBeyondBaseTotal, extraCountTotal);
                let childrenExtraTotal = Math.max(0, extraCountTotal - adultExtraTotal);
                childrenExtraTotal = Math.min(childrenExtraTotal, chargeableChildren);

                const adultsChargePerNightTotal = adultExtraTotal * ADULT_PRICE;
                const childrenChargePerNightTotal = childrenExtraTotal * CHILD_PRICE;
                const addonsPerNight = computeAddonsPerNight();
                const basePerRoom = pricePerNight;

                const weekendNights = countWeekendNights(from, to);
                const weekdayNights = Math.max(0, nights - weekendNights);

                if (weekendNoticeEl) {
                    if (weekendNights > 0) {
                        weekendNoticeEl.style.display = 'block';
                        weekendNoticeEl.innerText =
                            `Lưu ý: Giá phòng tăng 10% cho các đêm cuối tuần (Thứ 6, Thứ 7, Chủ nhật). ` +
                            `Số đêm cuối tuần trong khoảng bạn chọn: ${weekendNights}.`;
                    } else {
                        weekendNoticeEl.style.display = 'none';
                    }
                }

                const baseWeekdayTotal = basePerRoom * roomsCount * weekdayNights;
                const baseWeekendTotal = basePerRoom * WEEKEND_MULTIPLIER * roomsCount * weekendNights;
                const roomBaseTotal = baseWeekdayTotal + baseWeekendTotal;

                const extrasPerNightTotal = adultsChargePerNightTotal + childrenChargePerNightTotal + addonsPerNight;
                const extrasTotal = extrasPerNightTotal * nights;

                const rawTotal = roomBaseTotal + extrasTotal;

                let voucherDiscount = 0;
                if (voucherDiscountInput && voucherDiscountInput.value) {
                    voucherDiscount = Number(voucherDiscountInput.value) || 0;
                    if (voucherDiscount < 0) voucherDiscount = 0;
                    if (voucherDiscount > rawTotal) voucherDiscount = rawTotal;
                }

                let total = rawTotal;
                if (voucherDiscount > 0) total = Math.max(0, rawTotal - voucherDiscount);

                const finalPerNight = total / nights;

                const selectedDepositRadio = document.querySelector('input[name="deposit_percentage"]:checked');
                const depositPercentageValue = selectedDepositRadio ? parseInt(selectedDepositRadio.value, 10) : 50;
                const depositPercent = depositPercentageValue / 100;

                let deposit = depositPercent === 1 ? total : Math.ceil(total * depositPercent / 1000) * 1000;

                // UI labels
                const percentageText = depositPercent * 100 + '%';
                const depositLabel = document.getElementById('deposit_percentage_label');
                const modalDepositLabel = document.getElementById('modal_deposit_label');
                const remainingInfo = document.getElementById('remaining_info');

                if (depositLabel) depositLabel.innerText = depositPercent === 1 ? 'Thanh toán toàn bộ (100%)' :
                    `Đặt cọc (${percentageText})`;
                if (modalDepositLabel) modalDepositLabel.innerText = depositPercent === 1 ?
                    'Thanh toán toàn bộ (100%)' : `Đặt cọc (${percentageText})`;
                if (remainingInfo) {
                    if (depositPercent === 1) remainingInfo.innerText =
                        'Đã thanh toán đủ - Không cần thanh toán thêm khi check-in';
                    else remainingInfo.innerText =
                        `Phần còn lại (${100 - (depositPercent * 100)}%) thanh toán tại khách sạn khi check in`;
                }

                // display
                priceBaseDisplay.innerText = fmtVnd(basePerRoom);
                priceAdultsDisplay.innerText = adultsChargePerNightTotal > 0 ? fmtVnd(adultsChargePerNightTotal) :
                '0 đ';
                priceChildrenDisplay.innerText = childrenChargePerNightTotal > 0 ? fmtVnd(childrenChargePerNightTotal) :
                    '0 đ';
                const addonsEl = document.getElementById('price_addons_display');
                if (addonsEl) addonsEl.innerText = addonsPerNight > 0 ? fmtVnd(addonsPerNight) : '0 đ';

                finalPerNightDisplay.innerText = fmtVnd(finalPerNight);
                totalDisplay.innerText = fmtVnd(total);
                payableDisplay.innerText = fmtVnd(deposit);

                const finalPerNightInput = document.getElementById('final_per_night_input');
                if (finalPerNightInput) finalPerNightInput.value = finalPerNight;

                if (snapshotTotalInput) snapshotTotalInput.value = total;
                if (hiddenTotalInput) hiddenTotalInput.value = total;
                if (hiddenDepositInput) hiddenDepositInput.value = deposit;

                if (originalTotalInput) originalTotalInput.value = rawTotal;
                if (originalDepositInput) {
                    const depositRaw = depositPercent === 1 ? rawTotal : Math.ceil(rawTotal * depositPercent / 1000) *
                        1000;
                    originalDepositInput.value = depositRaw;
                }

                // basic guard
                const form = document.getElementById('bookingForm');
                const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
                if (submitBtn) submitBtn.disabled = (currentAvailableRooms <= 0) || (countedPersons > totalMaxAllowed);
            }

            window.bookingUpdateSummary = updateSummary;

            document.querySelectorAll('input[name="deposit_percentage"]').forEach(radio => {
                radio.addEventListener('change', updateSummary);
            });

            function showVNPAYConfirmModal() {
                const nights = Number(nightsDisplay.innerText || 0);
                const basePrice = pricePerNight * (roomsInput ? Number(roomsInput.value || 1) : 1);
                const adultsCharge = priceAdultsDisplay.innerText || '0 đ';
                const childrenCharge = priceChildrenDisplay.innerText || '0 đ';

                const addonsEl = document.getElementById('price_addons_display');
                const addonsCharge = addonsEl ? (addonsEl.innerText || '0 đ') : '0 đ';

                const finalPerNight = finalPerNightDisplay.innerText || '0 đ';
                const total = totalDisplay.innerText || '0 đ';
                const deposit = payableDisplay.innerText || '0 đ';

                document.getElementById('modal_price_base').innerText = fmtVnd(basePrice);
                document.getElementById('modal_price_adults').innerText = adultsCharge;
                document.getElementById('modal_price_children').innerText = childrenCharge;
                document.getElementById('modal_price_addons').innerText = addonsCharge;
                document.getElementById('modal_final_per_night').innerText = finalPerNight;
                document.getElementById('modal_nights_count').innerText = nights;
                document.getElementById('modal_total_snapshot').innerText = total;
                document.getElementById('modal_payable_now').innerText = deposit;

                const modal = new bootstrap.Modal(document.getElementById('vnpayConfirmModal'));
                modal.show();
            }

            const vnpayProceedBtn = document.getElementById('vnpayProceedBtn');
            if (vnpayProceedBtn) {
                vnpayProceedBtn.addEventListener('click', async function() {
                    const modalInstance = bootstrap.Modal.getInstance(document.getElementById(
                        'vnpayConfirmModal'));
                    if (modalInstance) modalInstance.hide();

                    const submitBtn = document.querySelector('#bookingForm button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.dataset.origHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý...';

                    const phongId = document.querySelector('input[name="phong_id"]').value;
                    const ngayNhan = fromInput.value;
                    const ngayTra = toInput.value;
                    const tongTien = document.getElementById('hidden_tong_tien').value;
                    const deposit = document.getElementById('hidden_deposit').value;

                    const adults = adultsInput.value;
                    const children = childrenInput.value;
                    const childrenAges = Array.from(document.querySelectorAll(
                        'input[name="children_ages[]"]')).map(el => el.value);
                    const addons = Array.from(document.querySelectorAll('input[name="addons[]"]:checked'))
                        .map(el => el.value);
                    const roomsCount = roomsInput.value;
                    const soKhach = Number(adults) + Number(children);

                    const name = document.querySelector('input[name="name"]').value.trim();
                    const address = document.querySelector('input[name="address"]').value.trim();
                    const phone = document.querySelector('input[name="phone"]').value.trim();

                    const voucherId = document.getElementById('voucher_id_input')?.value || null;
                    const voucherDiscount = document.getElementById('voucher_discount_input')?.value ||
                    null;
                    const voucherCodeHidden = document.getElementById('voucher_code_input')?.value || null;

                    const depositRadio = document.querySelector('input[name="deposit_percentage"]:checked');
                    const depositPercentageValue = depositRadio ? depositRadio.value : '50';

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
                                ngay_nhan_phong: ngayNhan,
                                ngay_tra_phong: ngayTra,
                                amount: deposit,
                                total_amount: tongTien,
                                deposit_percentage: depositPercentageValue,

                                so_khach: soKhach,
                                adults: adults,
                                children: children,
                                children_ages: childrenAges,
                                addons: addons,
                                rooms_count: roomsCount,

                                voucher_id: voucherId,
                                voucher_discount: voucherDiscount,
                                ma_voucher: voucherCodeHidden,

                                phuong_thuc: 'vnpay',
                                name: name,
                                address: address,
                                phone: phone,
                                ghi_chu: document.querySelector('textarea[name="ghi_chu"]')
                                    .value.trim() || '',
                            }),
                        });

                        const data = await response.json();
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = submitBtn.dataset.origHtml;
                            alert(data.error || 'Không thể khởi tạo thanh toán.');
                        }
                    } catch (err) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitBtn.dataset.origHtml;
                        alert('Lỗi khi tạo thanh toán: ' + err.message);
                    }
                });
            }

            (function setupSubmitUx() {
                const form = document.getElementById('bookingForm');
                if (!form) return;
                const submitBtn = form.querySelector('button[type="submit"]');
                if (!submitBtn) return;

                const paymentMethodSelect = document.querySelector('select[name="phuong_thuc"]');
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    if (submitBtn.disabled) return;

                    if (currentAvailableRooms <= 0) return;

                    const paymentMethod = paymentMethodSelect.value;
                    if (paymentMethod === 'vnpay') {
                        showVNPAYConfirmModal();
                        return;
                    }

                    submitBtn.disabled = true;
                    submitBtn.dataset.origHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý...';
                    form.submit();
                });
            })();

            document.querySelectorAll('.addon-checkbox').forEach(chk => chk.addEventListener('change', updateSummary));
            if (Array.isArray(initialSelectedAddons) && initialSelectedAddons.length) {
                document.querySelectorAll('.addon-checkbox').forEach(chk => {
                    chk.checked = initialSelectedAddons.includes(String(chk.value)) || initialSelectedAddons
                        .includes(Number(chk.value));
                });
            }

            if (adultsInput) adultsInput.addEventListener('input', updateSummary);
            if (childrenInput) childrenInput.addEventListener('input', renderChildrenAges);
            if (roomsInput) {
                roomsInput.addEventListener('input', () => {
                    updateInputLimitsByRooms();
                    renderChildrenAges();
                    updateSummary();
                    updateRoomsAvailability();
                });
            }

            // init
            updateInputLimitsByRooms();
            renderChildrenAges();
            updateSummary();
            updateRoomsAvailability();
        })();
    </script>
@endpush
