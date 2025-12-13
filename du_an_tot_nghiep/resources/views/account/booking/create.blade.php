@extends('layouts.app')

@section('title', 'Đặt phòng - ' . ($phong->name ?? $phong->ma_phong))

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

    <script>
        window.LOAI_PHONGS = {!! json_encode($loaiPhongs->keyBy('id')) !!};
        window.CURRENT_LOAI_ID = {{ (int) $phong->loai_phong_id }};
        window.CURRENT_PHONG_ID = {{ (int) $phong->id }};
        // optional debug
        console.log('LOAI_PHONGS keys:', Object.keys(window.LOAI_PHONGS || {}));
    </script>


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
                                            <a href="{{ route('rooms.show', $phong->id) }}">Chi tiết phòng</a>
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
                                                        <input id="date_range" type="text" class="form-control flatpickr"
                                                            placeholder="Chọn khoảng thời gian" readonly>
                                                        <input type="hidden" name="ngay_nhan_phong" id="ngay_nhan_phong"
                                                            value="{{ old('ngay_nhan_phong', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                                                        <input type="hidden" name="ngay_tra_phong" id="ngay_tra_phong"
                                                            value="{{ old('ngay_tra_phong', \Carbon\Carbon::tomorrow()->format('Y-m-d')) }}">
                                                        <small class="text-muted">
                                                            Giờ nhận phòng: 14:00 – Giờ trả phòng: 12:00
                                                        </small>
                                                        {{-- Thông báo tăng giá cuối tuần --}}
                                                        <div id="weekend_notice" class="small mt-1 text-danger"
                                                            style="display:none;">
                                                            Lưu ý: Giá phòng tăng 10% cho các đêm cuối tuần
                                                            (Thứ 6, Thứ 7, Chủ nhật).
                                                        </div>
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
                                                                max="{{ max(1, $roomCapacity + 2) }}"
                                                                value="{{ old('adults', min(2, max(1, $roomCapacity))) }}">
                                                            <small id="adults_help" class="text-muted d-block">
                                                                Số người tối đa:
                                                                <strong
                                                                    id="room_capacity_display">{{ $roomCapacity + 2 }}</strong>
                                                            </small>
                                                        </div>

                                                        <div class="col-6">
                                                            <label class="form-label">Trẻ em</label>
                                                            <input type="number" name="children" id="children"
                                                                class="form-control" min="0" max="2"
                                                                value="{{ old('children', 0) }}">
                                                            <small id="children_help" class="text-muted d-block">
                                                                Tối đa 2 trẻ em mỗi phòng.
                                                            </small>
                                                        </div>

                                                        <div class="col-6">
                                                            <label class="form-label">Số phòng</label>
                                                            <input type="number" name="rooms_count" id="rooms_count"
                                                                class="form-control" min="1"
                                                                max="{{ $availableRoomsDefault ?? 1 }}"
                                                                value="{{ old('rooms_count', 1) }}">
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
                                                                                Số lượng:
                                                                                {{ $bt->pivot->quantity }}
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
                                                                <li>
                                                                    <em>Không có giường nào được cấu hình
                                                                        cho phòng này.</em>
                                                                </li>
                                                            @endforelse
                                                        </ul>
                                                    </div>

                                                    <input type="hidden" name="so_khach" id="so_khach"
                                                        value="{{ old('so_khach', $phong->suc_chua ?? 1) }}">
                                                    <div class="small text">
                                                        Phòng cho:
                                                        {{ $phong->suc_chua ?? ($roomCapacity ?? '-') }}
                                                        người
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
                                                    @else
                                                        {{-- Không có dịch vụ bổ sung --}}
                                                    @endif
                                                @else
                                                    <p class="mb-0">
                                                        <em>Không có tiện ích nào được liệt kê cho phòng
                                                            này.</em>
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- MR MULTI-TYPE: duplicate right-side guest/card UI per added room type --}}
                                        <div class="card mt-3 mr-multiroom-module">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <h6 class="mb-0">Thêm loại phòng khác</h6>

                                                    <div class="d-flex align-items-center">
                                                        <select id="mr_add_type_select"
                                                            class="form-select form-select-sm me-2"
                                                            style="min-width:220px;">
                                                            <option value="">-- Chọn loại phòng --</option>
                                                            @foreach ($loaiPhongs as $lp)
                                                                @if ($lp->id != $phong->loai_phong_id)
                                                                    <option value="{{ $lp->id }}">
                                                                        {{ $lp->ten }}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>

                                                        <button type="button" id="mr_add_type_btn"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-plus-lg"></i> Thêm loại phòng
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- container để append các nhóm loại phòng động --}}
                                                <div id="mr_rooms_container" class="mb-2">

                                                </div>
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
                                                        value="{{ $phong->tong_gia ?? ($phong->tong_tien ?? ($phong->gia_mac_dinh ?? 0)) }}">
                                                    <input type="hidden" name="deposit_amount" id="hidden_deposit"
                                                        value="0">

                                                    {{-- voucher gửi lên server --}}
                                                    <input type="hidden" name="voucher_id" id="voucher_id_input"
                                                        value="">
                                                    <input type="hidden" name="voucher_discount"
                                                        id="voucher_discount_input" value="">
                                                    <input type="hidden" name="ma_voucher" id="voucher_code_input"
                                                        value="">

                                                    {{-- GIÁ GỐC (KHÔNG VOUCHER) ĐỂ RESET / APPLY VOUCHER --}}
                                                    <input type="hidden" id="original_total" value="0">
                                                    <input type="hidden" id="original_deposit" value="0">

                                                    <div class="mt-3">
                                                        <label class="form-label fw-bold">
                                                            <i class="bi bi-percent me-1"></i>Chọn hình thức
                                                            thanh toán
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
                                                                            Thanh toán phần còn lại khi
                                                                            check-in
                                                                        </small>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input type="radio" name="deposit_percentage"
                                                                        value="100" class="form-check-input"
                                                                        id="deposit_100">
                                                                    <label for="deposit_100" class="form-check-label">
                                                                        <strong>Thanh toán toàn bộ
                                                                            100%</strong>
                                                                        <small class="text-muted d-block">
                                                                            Thanh toán ngay - Không cần
                                                                            thanh toán thêm
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
                                                                        <th class="small text-center">
                                                                            Đặt cọc 50%
                                                                        </th>
                                                                        <th class="small text-center">
                                                                            Thanh toán 100%
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="small">≥ 7 ngày trước
                                                                            check-in
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-success">Hoàn 100%</span>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-success">Hoàn 90%</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="small">3-6 ngày trước</td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-warning text-dark">Hoàn
                                                                                70%</span>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-warning text-dark">Hoàn
                                                                                60%</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="small">1-2 ngày trước</td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-warning text-dark">Hoàn
                                                                                30%</span>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-warning text-dark">Hoàn
                                                                                40%</span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="small">&lt; 24 giờ</td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-danger">Không hoàn</span>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-warning text-dark">Hoàn
                                                                                20%</span>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            <strong>Lưu ý:</strong> Thanh toán 100% ngay được
                                                            ưu đãi thêm khi hủy phòng
                                                        </small>
                                                    </div>

                                                    <div class="mt-3">
                                                        <label for="phuong_thuc" class="form-label">Phương thức thanh
                                                            toán</label>
                                                        <select name="phuong_thuc" id="phuong_thuc" class="form-select"
                                                            required>
                                                            <option value="">Chọn phương thức</option>
                                                            <option value="vnpay"
                                                                {{ old('phuong_thuc') == 'vnpay' ? 'selected' : '' }}>Thanh
                                                                toán
                                                                bằng VNPAY</option>
                                                            <option value="momo"
                                                                {{ old('phuong_thuc') == 'momo' ? 'selected' : '' }}>Thanh
                                                                toán
                                                                bằng MoMo</option>
                                                            {{-- <option value="chuyen_khoan"
                                                                {{ old('phuong_thuc') == 'chuyen_khoan' ? 'selected' : '' }}>
                                                                Chuyển khoản ngân hàng</option> --}}
                                                        </select>
                                                    </div>
                                                </div>

                                                <input type="hidden" name="final_per_night" id="final_per_night_input"
                                                    value="">
                                                <input type="hidden" name="snapshot_total" id="snapshot_total_input"
                                                    value="">

                                                <div class="mt-3">
                                                    <button type="submit" class="btn btn-lg btn-primary">
                                                        Xác nhận
                                                    </button>
                                                    <a href="{{ route('rooms.show', $phong->id) }}"
                                                        class="btn btn-secondary ms-2">Hủy</a>
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
                                                        {{ number_format($phong->tong_gia ?? ($phong->gia_mac_dinh ?? 0), 0, ',', '.') }}
                                                        đ
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

                                                {{-- Ô nhập và nút áp dụng --}}
                                                <div class="input-group mb-3">
                                                    <input type="text" id="voucher_code"
                                                        class="form-control rounded-start"
                                                        placeholder="Nhập hoặc chọn mã giảm giá">
                                                    <button class="btn btn-success rounded-end px-4" id="applyVoucherBtn">
                                                        Áp dụng
                                                    </button>
                                                </div>

                                                {{-- Danh sách voucher --}}
                                                @if (Auth::check() && Auth::user()->vouchers->count() > 0)
                                                    <div class="border rounded p-3 bg-light"
                                                        style="max-height: 180px; overflow-y: auto;">
                                                        <small class="text-muted fw-bold d-block mb-2">
                                                            Voucher của bạn:
                                                        </small>

                                                        @foreach ($vouchers as $voucher)
                                                            @php
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
                                                                        <small class="text-muted d-block">
                                                                            Mã:
                                                                            {{ $voucher->code }}
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
                                                    <p class="text-muted small">
                                                        Bạn chưa nhận mã giảm giá nào.
                                                    </p>
                                                @endif

                                                {{-- Kết quả áp dụng --}}
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
        document.addEventListener('DOMContentLoaded', function() {
            // ====== VOUCHER (giữ nguyên logic) ======
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

                const fmtVnd = (num) =>
                    new Intl.NumberFormat('vi-VN').format(Math.round(num || 0)) + ' đ';

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
                    } else if (codeInput) codeInput.value = '';
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
                            resultBox.innerHTML =
                                `<div class="alert alert-danger p-2">Giá trị đơn hàng không hợp lệ. Vui lòng chọn ngày, số phòng và khách trước khi áp dụng mã.</div>`;
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
                            const fmtVndLocal = (num) => new Intl.NumberFormat('vi-VN').format(Math
                                .round(num)) + ' đ';
                            if (data.success) {
                                const discountAmount = Number(data.discount || 0);
                                const finalTotalLocal = Math.max(0, total - discountAmount);
                                resultBox.innerHTML = `
                        <div class="alert alert-success p-2">
                            Áp dụng thành công <strong>${data.voucher_name || code}</strong><br>
                            Giảm: <strong>${discountAmount > 0 ? fmtVndLocal(discountAmount) : '0 đ'}</strong><br>
                            Tổng mới (ước tính): <strong>${fmtVndLocal(finalTotalLocal)}</strong><br>
                            <small class="text-muted">Tiền cọc sẽ được tính lại theo % bạn chọn (50% hoặc 100%).</small>
                        </div>`;
                                const voucherIdInput = document.getElementById('voucher_id_input');
                                if (voucherIdInput && data.voucher_id) voucherIdInput.value = data
                                    .voucher_id;
                                const voucherDiscountInput = document.getElementById(
                                    'voucher_discount_input');
                                if (voucherDiscountInput && typeof data.discount !== 'undefined')
                                    voucherDiscountInput.value = data.discount;
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
                            const resultBox = document.getElementById('voucherResult');
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
            // ====== END VOUCHER ======


            // ===== BOOKING + AVAILABILITY + MR (multi-type) =====
            (function() {
                // -------- blade-provided data (safe) --------
                const LOAI_PHONGS = (typeof window.LOAI_PHONGS !== 'undefined') ? window.LOAI_PHONGS : {};
                const CURRENT_LOAI_ID = Number(window.CURRENT_LOAI_ID || {{ (int) $phong->loai_phong_id }});
                const CURRENT_PHONG_ID = String(window.CURRENT_PHONG_ID || (document.querySelector(
                    'input[name="phong_id"]')?.value || ''));

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

                // MR UI elements
                const MR_CONTAINER = document.getElementById('mr_rooms_container');
                const MR_ADD_SELECT = document.getElementById('mr_add_type_select');
                const MR_ADD_BTN = document.getElementById('mr_add_type_btn');

                // config constants from blade
                const pricePerNight = Number({!! json_encode((float) ($phong->tong_gia ?? ($phong->gia_mac_dinh ?? 0))) !!}) || 0;
                const baseCapacity = Number({{ $baseCapacity }}) || 1;
                const ADULT_PRICE = {{ \App\Http\Controllers\Client\BookingController::ADULT_PRICE }};
                const CHILD_PRICE = {{ \App\Http\Controllers\Client\BookingController::CHILD_PRICE }};
                const CHILD_FREE_AGE = {{ \App\Http\Controllers\Client\BookingController::CHILD_FREE_AGE }};
                const WEEKEND_MULTIPLIER = 1.10;

                let currentAvailableRooms = Number(availDisplayEl ? (availDisplayEl.innerText || 0) : 0);

                function fmtVnd(num) {
                    return new Intl.NumberFormat('vi-VN').format(Math.round(num || 0)) + ' đ';
                }

                // ---------- helpers robustly read arrays from LOAI_PHONGS objects ----------
                function getArr(obj, candidates) {
                    for (const k of candidates) {
                        if (!obj) continue;
                        if (Array.isArray(obj[k])) return obj[k];
                    }
                    return [];
                }

                function getStr(obj, candidates) {
                    for (const k of candidates) {
                        if (!obj) continue;
                        if (typeof obj[k] === 'string' && obj[k].trim() !== '') return obj[k];
                    }
                    return '';
                }

                // ---------- ensure to > from ----------
                function ensureToAfterFromAndUpdateInputs() {
                    if (!fromInput || !toInput) return false;
                    const from = new Date(fromInput.value + 'T00:00:00');
                    let to = new Date(toInput.value + 'T00:00:00');
                    if (isNaN(from.getTime())) return false;
                    if (isNaN(to.getTime()) || to <= from) {
                        to = new Date(from.getTime());
                        to.setDate(to.getDate() + 1);
                        const y = to.getFullYear(),
                            m = String(to.getMonth() + 1).padStart(2, '0'),
                            d = String(to.getDate()).padStart(2, '0');
                        toInput.value = `${y}-${m}-${d}`;
                        return true;
                    }
                    return true;
                }

                function countWeekendNights(fromDate, toDate) {
                    const cursor = new Date(fromDate.getTime());
                    const end = new Date(toDate.getTime());
                    let cnt = 0;
                    while (cursor < end) {
                        const d = cursor.getDay();
                        if (d === 5 || d === 6 || d === 0) cnt++;
                        cursor.setDate(cursor.getDate() + 1);
                    }
                    return cnt;
                }

                // ---------- input limits ----------
                function updateInputLimitsByRooms() {
                    const rooms = Number(roomsInput ? (roomsInput.value || 1) : 1);
                    const adultsMax = (baseCapacity + 2) * Math.max(1, rooms);
                    const childrenMax = Math.min(12, 2 * Math.max(1, rooms));
                    if (adultsInput) {
                        adultsInput.max = adultsMax;
                        if (!adultsInput.min) adultsInput.min = 1;
                        let v = Number(adultsInput.value || adultsInput.min || 1);
                        if (isNaN(v) || v < Number(adultsInput.min)) v = Number(adultsInput.min);
                        if (v > adultsMax) v = adultsMax;
                        adultsInput.value = v;
                        const roomCapDisplay = document.getElementById('room_capacity_display');
                        if (roomCapDisplay) roomCapDisplay.innerText = adultsMax;
                    }
                    if (childrenInput) {
                        childrenInput.max = childrenMax;
                        let v2 = Number(childrenInput.value || 0);
                        if (isNaN(v2) || v2 < 0) v2 = 0;
                        if (v2 > childrenMax) v2 = childrenMax;
                        childrenInput.value = v2;
                    }
                }

                // ---------- availability main ----------
                async function updateRoomsAvailabilityMain() {
                    try {
                        if (!fromInput || !toInput) return;
                        if (!ensureToAfterFromAndUpdateInputs()) return;
                        const from = fromInput.value,
                            to = toInput.value;
                        if (!from || !to) return;

                        const loaiId = {{ $phong->loai_phong_id }};
                        const phongId = {{ $phong->id }};
                        const params = new URLSearchParams({
                            loai_phong_id: String(loaiId),
                            phong_id: String(phongId),
                            from,
                            to
                        }).toString();
                        const url = '{{ route('booking.availability') }}' + '?' + params;
                        console.debug('[availability main] GET', url);
                        const res = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) {
                            if (res.status === 422) {
                                const body = await res.json().catch(() => null);
                                console.warn('availability main 422 body:', body);
                            }
                            console.error('Availability check error: status', res.status);
                            return;
                        }
                        const data = await res.json();
                        const avail = Number(data.available || 0);
                        currentAvailableRooms = avail;
                        if (availDisplayEl) availDisplayEl.innerText = avail;
                        if (roomsInput) {
                            roomsInput.max = avail;
                            if (Number(roomsInput.value || 0) > avail) roomsInput.value = Math.max(1,
                                avail);
                        }
                        if (avail === 0) showNoAvailabilityMessage();
                        else clearAvailabilityMessage();
                        updateSummary();
                    } catch (err) {
                        console.error('Availability check error', err);
                    }
                }

                function showNoAvailabilityMessage() {
                    if (!availabilityMessageEl) return;
                    availabilityMessageEl.className = 'small mt-2 text-danger';
                    availabilityMessageEl.innerText =
                        `Phòng {{ $phong->ma_phong }} không khả dụng trong khoảng thời gian đã chọn.`;
                    toggleSubmit(false);
                }

                function clearAvailabilityMessage() {
                    if (!availabilityMessageEl) return;
                    availabilityMessageEl.innerText = '';
                    toggleSubmit(true);
                }

                function toggleSubmit(enabled) {
                    const form = document.getElementById('bookingForm');
                    if (!form) return;
                    const btn = form.querySelector('button[type="submit"]');
                    if (!btn) return;
                    btn.disabled = !enabled;
                }
                // ---------- MR (additional groups) ----------
                let mrIndex = 0;

                function getLoaiObj(loaiId) {
                    return LOAI_PHONGS[String(loaiId)] || LOAI_PHONGS[loaiId] || null;
                }
                // Keep hidden inputs in sync with UI state so normal form submit works
                function syncGroupHiddenInputs() {
                    if (!MR_CONTAINER) return;
                    const groups = Array.from(MR_CONTAINER.querySelectorAll('.mr_group'));
                    groups.forEach(g => {
                        const gi = g.dataset.idx;
                        // ensure loai_phong_id hidden exists
                        let hLo = g.querySelector(`input[name="rooms[${gi}][loai_phong_id]"]`);
                        if (!hLo) {
                            hLo = document.createElement('input');
                            hLo.type = 'hidden';
                            hLo.name = `rooms[${gi}][loai_phong_id]`;
                            hLo.className = 'mr_hidden_loai';
                            g.appendChild(hLo);
                        }
                        hLo.value = g.dataset.loaiId;
                        // rooms_count
                        let hCnt = g.querySelector(`input[name="rooms[${gi}][rooms_count]"]`);
                        const roomsInp = g.querySelector('.mr_rooms_count_input');
                        if (!hCnt) {
                            hCnt = document.createElement('input');
                            hCnt.type = 'hidden';
                            hCnt.name = `rooms[${gi}][rooms_count]`;
                            hCnt.className = 'mr_hidden_rooms_count';
                            g.appendChild(hCnt);
                        }
                        hCnt.value = roomsInp ? roomsInp.value : 1;
                        // adults
                        let hAdults = g.querySelector(`input[name="rooms[${gi}][adults]"]`);
                        const adultsInp = g.querySelector('.mr_adults_input');
                        if (!hAdults) {
                            hAdults = document.createElement('input');
                            hAdults.type = 'hidden';
                            hAdults.name = `rooms[${gi}][adults]`;
                            hAdults.className = 'mr_hidden_adults';
                            g.appendChild(hAdults);
                        }
                        hAdults.value = adultsInp ? adultsInp.value : 0;
                        // children
                        const prevKids = g.querySelectorAll(`[name^="rooms[${gi}][children_ages]"]`);
                        prevKids.forEach(n => n.remove());
                        const ageInputs = g.querySelectorAll('.mr_child_age_input');
                        ageInputs.forEach(ai => {
                            const hi = document.createElement('input');
                            hi.type = 'hidden';
                            hi.name = `rooms[${gi}][children_ages][]`;
                            hi.value = ai.value || 0;
                            g.appendChild(hi);
                        });

                        // final_per_night (from dataset if present)
                        let hFinal = g.querySelector(`input[name="rooms[${gi}][final_per_night]"]`);
                        if (!hFinal) {
                            hFinal = document.createElement('input');
                            hFinal.type = 'hidden';
                            hFinal.name = `rooms[${gi}][final_per_night]`;
                            g.appendChild(hFinal);
                        }
                        hFinal.value = g.dataset.pricePerRoom || '0';

                        // nights
                        let hN = g.querySelector(`input[name="rooms[${gi}][nights]"]`);
                        if (!hN) {
                            hN = document.createElement('input');
                            hN.type = 'hidden';
                            hN.name = `rooms[${gi}][nights]`;
                            g.appendChild(hN);
                        }
                        hN.value = document.getElementById('nights_count_display')?.innerText || 1;

                        // remove previous 'addons' hidden to avoid duplicates
                        const prevAddons = g.querySelectorAll(`[name^="rooms[${gi}][addons]"]`);
                        prevAddons.forEach(n => n.remove());

                    });
                }

                function buildGroupNode(loaiId) {
                    const loai = getLoaiObj(loaiId);
                    if (!loai) {
                        console.warn('Không tìm thấy loai_phong trong LOAI_PHONGS:', loaiId);
                        return null;
                    }
                    // pick representative room (to get precise price if available)
                    const phongs = getArr(loai, ['phongs', 'phongs_list', 'rooms']) || [];
                    let rep = null;
                    if (Array.isArray(phongs) && phongs.length) {
                        rep = phongs.find(p => String(p.id) !== String(CURRENT_PHONG_ID)) || phongs[0];
                    }
                    const price = Number((rep && (rep.tong_gia || rep.gia_cuoi_cung || rep.gia_mac_dinh)) ||
                        loai.gia_mac_dinh || 0);
                    const capacity = loai.suc_chua || (rep ? (rep.suc_chua || 1) : 1);

                    // build amenities list robustly
                    const amenities = (getArr(loai, ['tienNghis', 'tien_nghis', 'tien_nghi', 'amenities',
                            'tien_nghi'
                        ]) || [])
                        .map(a => a.ten || a.name).filter(Boolean).slice(0, 6).join(', ') || '-';

                    // bed types display (try multiple keys)
                    const bedTypesArr = getArr(loai, ['bedTypes', 'bed_types', 'beds', 'bed_types_list']);
                    const bedTypesHtml = bedTypesArr.length ? (bedTypesArr.map(b => {
                        const label = b.ten || b.name || (b.label) || '';
                        const qty = (b.pivot && b.pivot.quantity) || b.quantity || '';
                        return `<div class="small">• ${label}${qty ? ` x${qty}` : ''}</div>`;
                    }).join('')) : '<div class="small">-</div>';

                    const wrapper = document.createElement('div');
                    wrapper.className = 'mr_group card p-2 mb-3';
                    wrapper.dataset.loaiId = String(loaiId);
                    wrapper.dataset.representativePhongId = rep ? String(rep.id) : '';
                    wrapper.dataset.pricePerRoom = String(price);
                    wrapper.dataset.baseCapacity = String(capacity);
                    wrapper.dataset.idx = String(++mrIndex);

                    wrapper.innerHTML = `
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-bold">${loai.ten || loai.name}</div>
                                <div class="small text-muted">${getStr(loai, ['mo_ta','description','desc'])}</div>
                                <div class="small"><strong>Tiện nghi:</strong> <span class="mr_amenities">${amenities}</span></div>
                                <div class="small"><strong>Cấu hình giường:</strong> <div class="mr_bedtypes">${bedTypesHtml}</div></div>
                                <div class="small"><strong>Sức chứa (mỗi phòng):</strong> <span class="mr_capacity">${capacity}</span></div>
                                <div class="small text-muted">Có sẵn cho ngày đã chọn: <span class="mr_available_display">-</span> phòng</div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger mr_remove_btn">Xóa</button>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-4">
                                <label class="form-label">Số phòng</label>
                                <input type="number" min="1" value="1" class="form-control mr_rooms_count_input" />
                            </div>
                            <div class="col-4">
                                <label class="form-label">Người lớn</label>
                                <input type="number" min="0" value="${capacity}" class="form-control mr_adults_input" />
                            </div>
                            <div class="col-4">
                                <label class="form-label">Trẻ em</label>
                                <div class="d-flex">
                                    <input type="number" min="0" value="0" class="form-control mr_children_input" />
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2 mr_manage_ages_btn">Tuổi</button>
                                </div>
                                <div class="mr_children_ages_container mt-2" style="display:none"></div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <label class="form-label">Giá / đêm (tạm)</label>
                                <div class="fw-bold mr_price_display">${price ? fmtVnd(price) : '-'}</div>
                            </div>
                        </div>
                    `;
                    // events
                    const roomsInp = wrapper.querySelector('.mr_rooms_count_input');
                    const adultsInp = wrapper.querySelector('.mr_adults_input');
                    const childrenInp = wrapper.querySelector('.mr_children_input');
                    const agesContainer = wrapper.querySelector('.mr_children_ages_container');
                    const agesBtn = wrapper.querySelector('.mr_manage_ages_btn');

                    function enforceGroupLimits() {
                        const roomsVal = Math.max(1, Number(roomsInp.value || 1));
                        roomsInp.value = roomsVal;

                        const perRoomMaxAdults = Number(wrapper.dataset.baseCapacity || 1) + 2;
                        const maxAdultsTotal = perRoomMaxAdults * roomsVal;
                        let a = Number(adultsInp.value || 0);
                        if (isNaN(a) || a < 0) a = 0;
                        if (a > maxAdultsTotal) a = maxAdultsTotal;
                        adultsInp.value = a;

                        const maxChildrenTotal = 2 * roomsVal;
                        let c = Number(childrenInp.value || 0);
                        if (isNaN(c) || c < 0) c = 0;
                        if (c > maxChildrenTotal) c = maxChildrenTotal;
                        childrenInp.value = c;
                        // ensure children ages inputs count equals childrenInp
                        const currentAgeInputs = agesContainer.querySelectorAll('input.mr_child_age_input');
                        const needed = Number(childrenInp.value || 0);
                        // add or remove age inputs
                        if (currentAgeInputs.length < needed) {
                            for (let k = currentAgeInputs.length; k < needed; k++) {
                                const el = document.createElement('input');
                                el.type = 'number';
                                el.min = 0;
                                el.max = 12;
                                el.value = 0;
                                el.className = 'form-control form-control-sm mr_child_age_input mt-1';
                                agesContainer.appendChild(el);
                                el.addEventListener('input', () => {
                                    if (el.value === '') el.value = 0;
                                    let v = Number(el.value || 0);
                                    if (isNaN(v) || v < 0) v = 0;
                                    if (v > 12) v = 12;
                                    el.value = v;
                                    // update summary (so main updates chargeable children per group via hidden input)
                                    updateSummary();
                                    syncGroupHiddenInputs();
                                });
                            }
                        } else if (currentAgeInputs.length > needed) {
                            for (let k = currentAgeInputs.length - 1; k >= needed; k--) {
                                currentAgeInputs[k].remove();
                            }
                        }
                        // show/hide ages container
                        agesContainer.style.display = needed > 0 ? 'block' : 'none';
                    }
                    agesBtn.addEventListener('click', () => {
                        // toggle visibility
                        agesContainer.style.display = agesContainer.style.display === 'none' ? 'block' :
                            'none';
                    });
                    // on input events
                    [roomsInp, adultsInp, childrenInp].forEach(el => {
                        el.addEventListener('input', () => {
                            enforceGroupLimits();
                            syncGroupHiddenInputs();
                            fetchAndWriteAvailabilityForGroup(wrapper);
                            updateSummary();
                        });
                    });
                    // initial enforce
                    enforceGroupLimits();

                    roomsInp.addEventListener('input', onGroupChange);
                    adultsInp.addEventListener('input', onGroupChange);
                    childrenInp.addEventListener('input', onGroupChange);

                    rmBtn.addEventListener('click', function() {
                        if (MR_ADD_SELECT) {
                            const opt = MR_ADD_SELECT.querySelector(`option[value="${loaiId}"]`);
                            if (opt) opt.hidden = false;
                        }
                        wrapper.remove();
                        // re-sync after removal
                        syncGroupHiddenInputs();
                        updateSummary();
                    });

                    // define a reusable handler so we don't reference an undefined function
                    function onGroupChange() {
                        enforceGroupLimits();
                        syncGroupHiddenInputs();
                        fetchAndWriteAvailabilityForGroup(wrapper);
                        updateSummary();
                    }

                    // attach events using the handler
                    [roomsInp, adultsInp, childrenInp].forEach(el => {
                        if (!el) return;
                        el.addEventListener('input', onGroupChange);
                        el.addEventListener('change', onGroupChange);
                    });

                    // toggle ages panel button
                    agesBtn.addEventListener('click', () => {
                        agesContainer.style.display = agesContainer.style.display === 'none' ? 'block' :
                            'none';
                    });

                    // remove button (make sure rmBtn exists)
                    const rmBtn = wrapper.querySelector('.mr_remove_btn');
                    if (rmBtn) {
                        rmBtn.addEventListener('click', function() {
                            if (MR_ADD_SELECT) {
                                const opt = MR_ADD_SELECT.querySelector(`option[value="${loaiId}"]`);
                                if (opt) opt.hidden = false;
                            }
                            wrapper.remove();
                            // re-sync after removal
                            syncGroupHiddenInputs();
                            updateSummary();
                        });
                    } else {
                        console.warn('mr_remove_btn not found in group for loaiId', loaiId);
                    }

                    // initial enforce & sync
                    enforceGroupLimits();

                    // initial fetch small delay + create initial hidden inputs
                    setTimeout(() => {
                        fetchAndWriteAvailabilityForGroup(wrapper);
                        syncGroupHiddenInputs();
                    }, 50);
                    return wrapper;
                }
                async function fetchAndWriteAvailabilityForGroup(wrapper) {
                    try {
                        if (!fromInput || !toInput) return;
                        if (!ensureToAfterFromAndUpdateInputs()) return;
                        const loaiId = wrapper.dataset.loaiId;
                        const rep = wrapper.dataset.representativePhongId;
                        const paramsObj = {
                            loai_phong_id: String(loaiId),
                            from: fromInput.value,
                            to: toInput.value
                        };
                        if (rep) paramsObj.phong_id = String(rep);
                        const url = '{{ route('booking.availability') }}' + '?' + new URLSearchParams(
                            paramsObj).toString();
                        console.debug('[availability group] GET', url);
                        const res = await fetch(url, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) {
                            if (res.status === 422) {
                                const body = await res.json().catch(() => null);
                                console.warn('group availability 422 body:', body);
                            }
                            console.warn('group availability http status', res.status);
                            const ad = wrapper.querySelector('.mr_available_display');
                            if (ad) ad.innerText = '-';
                            return;
                        }
                        const data = await res.json();
                        const avail = Number(data.available || 0);
                        const ad = wrapper.querySelector('.mr_available_display');
                        if (ad) ad.innerText = avail;
                        const roomsInp = wrapper.querySelector('.mr_rooms_count_input');
                        if (roomsInp) {
                            roomsInp.max = Math.max(1, avail);
                            if (Number(roomsInp.value || 0) > avail) roomsInp.value = Math.max(1, avail);
                        }
                        // keep hidden inputs synced after availability changes
                        syncGroupHiddenInputs();
                    } catch (err) {
                        console.error('group availability error', err);
                    }
                }

                function refreshAllGroupsAvailability() {
                    if (!MR_CONTAINER) return;
                    const groups = Array.from(MR_CONTAINER.querySelectorAll('.mr_group'));
                    groups.forEach(g => fetchAndWriteAvailabilityForGroup(g));
                }

                // MR add button
                if (MR_ADD_BTN && MR_ADD_SELECT && MR_CONTAINER) {
                    MR_ADD_BTN.addEventListener('click', function() {
                        const val = MR_ADD_SELECT.value;
                        if (!val) return;
                        if (MR_CONTAINER.querySelector(`.mr_group[data-loai-id="${val}"]`)) {
                            showInlineMsg('Loại phòng này đã được thêm', true);
                            return;
                        }
                        const node = buildGroupNode(val);
                        if (node) {
                            MR_CONTAINER.appendChild(node);
                            const opt = MR_ADD_SELECT.querySelector(`option[value="${val}"]`);
                            if (opt) opt.hidden = true;
                            MR_ADD_SELECT.value = '';
                        }
                        // ensure hidden inputs created
                        syncGroupHiddenInputs();
                        updateSummary();
                    });
                } else {
                    if (!MR_ADD_BTN) console.warn('MR_ADD_BTN not found');
                    if (!MR_ADD_SELECT) console.warn('MR_ADD_SELECT not found');
                    if (!MR_CONTAINER) console.warn('MR_CONTAINER not found');
                }

                function showInlineMsg(msg, isError = false, t = 2500) {
                    try {
                        const d = document.createElement('div');
                        d.className = 'alert ' + (isError ? 'alert-danger' : 'alert-success') +
                            ' position-fixed';
                        d.style.right = '20px';
                        d.style.bottom = '20px';
                        d.style.zIndex = 1150;
                        d.style.minWidth = '200px';
                        d.textContent = msg;
                        document.body.appendChild(d);
                        setTimeout(() => d.remove(), t);
                    } catch (e) {
                        console.warn(e);
                    }
                }
                // ---------- SUMMARY (main + groups) ----------
                function computeAddonsPerNight() {
                    let sum = 0;
                    document.querySelectorAll('input[name="addons[]"]:checked').forEach(chk => {
                        const p = Number(chk.dataset.price || 0);
                        if (!isNaN(p)) sum += p;
                    });
                    const rooms = Number(roomsInput ? (roomsInput.value || 1) : 1);
                    return sum * Math.max(1, rooms);
                }

                function updateSummary() {
                    if (!ensureToAfterFromAndUpdateInputs()) return;
                    const fromVal = fromInput?.value,
                        toVal = toInput?.value;
                    if (!fromVal || !toVal) {
                        nightsDisplay && (nightsDisplay.innerText = '-');
                        finalPerNightDisplay && (finalPerNightDisplay.innerText = '-');
                        totalDisplay && (totalDisplay.innerText = '-');
                        payableDisplay && (payableDisplay.innerText = '-');
                        return;
                    }
                    const from = new Date(fromVal + 'T00:00:00'),
                        to = new Date(toVal + 'T00:00:00');
                    const nights = Math.max(0, Math.round((to - from) / (1000 * 60 * 60 * 24)));
                    if (nights <= 0) {
                        nightsDisplay && (nightsDisplay.innerText = '-');
                        finalPerNightDisplay && (finalPerNightDisplay.innerText = '-');
                        totalDisplay && (totalDisplay.innerText = '-');
                        payableDisplay && (payableDisplay.innerText = '-');
                        return;
                    }
                    if (nightsDisplay) nightsDisplay.innerText = nights;

                    // main rooms count clamp
                    let roomsCount = 1;
                    if (roomsInput) {
                        roomsCount = Number(roomsInput.value || 1);
                        if (isNaN(roomsCount) || roomsCount < 1) roomsCount = 1;
                        if (roomsInput.max && Number(roomsInput.max) >= 0 && roomsCount > Number(roomsInput
                                .max)) {
                            roomsCount = Number(roomsInput.max);
                            roomsInput.value = roomsCount;
                        }
                    }
                    updateInputLimitsByRooms();
                    // ---- MAIN: compute adults/children for main room (uses children ages to decide chargeable) ----
                    const agesGlobal = Array.from(document.querySelectorAll('.child-age-input')).map(x =>
                        Number(x.value || 0));
                    let computedAdultsMain = Number(adultsInput?.value || 0);
                    let chargeableChildrenMain = 0;
                    agesGlobal.forEach(a => {
                        if (a >= 13) computedAdultsMain++;
                        else if (a >= CHILD_FREE_AGE) chargeableChildrenMain++;
                    });
                    // main extras per-night
                    const basePerRoomMain = pricePerNight; // representative price from blade
                    const weekendNights = countWeekendNights(from, to);
                    const weekdayNights = Math.max(0, nights - weekendNights);

                    const baseWeekdayTotalMain = basePerRoomMain * roomsCount * weekdayNights;
                    const baseWeekendTotalMain = basePerRoomMain * WEEKEND_MULTIPLIER * roomsCount *
                        weekendNights;
                    const roomBaseTotalMain = baseWeekdayTotalMain + baseWeekendTotalMain;

                    const baseCapacityMain = baseCapacity; // blade-provided
                    const extraCountMain = Math.max(0, computedAdultsMain - (baseCapacityMain * roomsCount));
                    const adultExtraTotalMain = Math.min(Math.max(0, computedAdultsMain - (baseCapacityMain *
                        roomsCount)), extraCountMain);
                    const adultsChargePerNightMain = adultExtraTotalMain * ADULT_PRICE;
                    const childrenChargePerNightMain = chargeableChildrenMain * CHILD_PRICE;

                    let additionalGroupsBaseTotal = 0;
                    let additionalGroupsExtrasPerNight = 0;
                    let groupsRoomCount = 0;

                    if (MR_CONTAINER) {
                        const groups = Array.from(MR_CONTAINER.querySelectorAll('.mr_group'));
                        if (MR_CONTAINER) {
                            const groups = Array.from(MR_CONTAINER.querySelectorAll('.mr_group'));
                            groups.forEach(g => {
                                const price = Number(g.dataset.pricePerRoom || 0);
                                const roomsG = Number(g.querySelector('.mr_rooms_count_input')?.value ||
                                    0);
                                // make adultsG mutable (let) because we may increment when child >=13
                                let adultsG = Number(g.querySelector('.mr_adults_input')?.value || 0);
                                const childrenG = Number(g.querySelector('.mr_children_input')?.value ||
                                    0);
                                let chargeableChildrenG = 0;

                                const ageEls = g.querySelectorAll('.mr_child_age_input');
                                if (ageEls && ageEls.length) {
                                    ageEls.forEach(ael => {
                                        const av = Number(ael.value || 0);
                                        if (av >= 13) {
                                            adultsG += 1;
                                        } else if (av >= CHILD_FREE_AGE) {
                                            chargeableChildrenG += 1;
                                        }
                                    });
                                } else {
                                    // fallback: if no age inputs, assume all children are chargeable up to limit
                                    chargeableChildrenG = childrenG;
                                }

                                const baseCapGroup = Number(g.dataset.baseCapacity || baseCapacity ||
                                1);
                                // compute extra adults/children for this group
                                const totalSlots = baseCapGroup * Math.max(1, roomsG);
                                const extraAdultsGroup = Math.max(0, adultsG - totalSlots);
                                // adults taken occupy some slots; remaining slots could cover children
                                const adultsTaken = Math.min(adultsG, totalSlots);
                                const remainingSlots = Math.max(0, totalSlots - adultsTaken);
                                const extraChildrenGroup = Math.max(0, chargeableChildrenG -
                                    remainingSlots);

                                additionalGroupsExtrasPerNight += (extraAdultsGroup * ADULT_PRICE) + (
                                    extraChildrenGroup * CHILD_PRICE);
                                additionalGroupsBaseTotal += (price * roomsG *nights);
                                groupsRoomCount += roomsG;
                            });
                        }

                    }

                    // ---- ADDONS (main-level) ----
                    const addonsPerNight = computeAddonsPerNight(); // already multiplies by main roomsCount
                    const extrasPerNightTotal = (adultsChargePerNightMain + additionalGroupsExtrasPerNight) + (
                        childrenChargePerNightMain) + addonsPerNight;
                    const extrasTotal = extrasPerNightTotal * nights;
                    // raw totals
                    const rawTotal = roomBaseTotalMain + additionalGroupsBaseTotal + extrasTotal;
                    // voucher
                    const voucherDiscountInput = document.getElementById('voucher_discount_input');
                    let voucherDiscount = 0;
                    if (voucherDiscountInput && voucherDiscountInput.value) {
                        voucherDiscount = Number(voucherDiscountInput.value) || 0;
                        if (voucherDiscount < 0) voucherDiscount = 0;
                        if (voucherDiscount > rawTotal) voucherDiscount = rawTotal;
                    }
                    let total = rawTotal;
                    if (voucherDiscount > 0) total = Math.max(0, rawTotal - voucherDiscount);

                    const finalPerNight = total / Math.max(1, nights);
                    const selectedDepositRadio = document.querySelector(
                        'input[name="deposit_percentage"]:checked');
                    const depositPercent = (selectedDepositRadio ? parseInt(selectedDepositRadio.value, 10) :
                        50) / 100;
                    const deposit = depositPercent === 1 ? total : Math.ceil(total * depositPercent / 1000) *
                        1000;

                    // base price display: display aggregated base (main + groups) per-night total (not only main)
                    const aggregatedBasePerNight = (roomBaseTotalMain + additionalGroupsBaseTotal) / Math.max(1,
                        nights);
                    priceBaseDisplay && (priceBaseDisplay.innerText = fmtVnd(aggregatedBasePerNight));
                    priceAdultsDisplay && (priceAdultsDisplay.innerText = ((adultsChargePerNightMain +
                        additionalGroupsExtrasPerNight) > 0) ? fmtVnd(adultsChargePerNightMain +
                        additionalGroupsExtrasPerNight) : '0 đ');
                    priceChildrenDisplay && (priceChildrenDisplay.innerText = (childrenChargePerNightMain > 0) ?
                        fmtVnd(childrenChargePerNightMain) : '0 đ');
                    finalPerNightDisplay && (finalPerNightDisplay.innerText = fmtVnd(finalPerNight));
                    totalDisplay && (totalDisplay.innerText = fmtVnd(total));
                    payableDisplay && (payableDisplay.innerText = fmtVnd(deposit));

                    const hiddenTotal = document.getElementById('hidden_tong_tien');
                    const hiddenDeposit = document.getElementById('hidden_deposit');
                    const snapshotTotal = document.getElementById('snapshot_total_input');
                    if (hiddenTotal) hiddenTotal.value = total;
                    if (hiddenDeposit) hiddenDeposit.value = deposit;
                    if (snapshotTotal) snapshotTotal.value = total;

                    const originalTotalInput = document.getElementById('original_total');
                    const originalDepositInput = document.getElementById('original_deposit');
                    if (originalTotalInput) originalTotalInput.value = rawTotal;
                    if (originalDepositInput) originalDepositInput.value = deposit;

                    const totalMaxAllowed = (baseCapacity + 2) * (roomsCount + groupsRoomCount);
                    const countedPersons = (computedAdultsMain + chargeableChildrenMain) /* main */ +
                        0;
                    if (countedPersons > totalMaxAllowed || currentAvailableRooms <= 0) toggleSubmit(false);
                    else toggleSubmit(true);
                }

                // expose
                window.bookingUpdateSummary = function() {
                    updateSummary();
                    syncGroupHiddenInputs();
                };

                // ---------- events ----------
                function renderChildrenAges() {
                    const initialChildrenAges = {!! json_encode(old('children_ages', [])) !!};
                    const initialChildrenCount = Number({{ old('children', 0) }});
                    const count = Number(childrenInput?.value || initialChildrenCount || 0);
                    if (!childrenAgesContainer) return;
                    childrenAgesContainer.innerHTML = '';
                    for (let i = 0; i < count; i++) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'mb-2 child-age-wrapper';
                        const initialVal = (Array.isArray(initialChildrenAges) && typeof initialChildrenAges[
                            i] !== 'undefined') ? Number(initialChildrenAges[i]) : 0;
                        wrapper.innerHTML = `<label class="form-label">Tuổi trẻ em ${i+1}</label>
                        <input type="number" name="children_ages[]" class="form-control child-age-input" min="0" max="12" value="${initialVal}" />
                        <div class="small text-danger mt-1 age-error" style="display:none;"></div>`;
                        childrenAgesContainer.appendChild(wrapper);
                    }
                    document.querySelectorAll('.child-age-input').forEach(el => {
                        el.addEventListener('input', function() {
                            let v = Number(this.value || 0);
                            if (isNaN(v) || v < 0) this.value = 0;
                            if (v > 12) this.value = 12;
                            updateSummary();
                        });
                    });
                }

                if (adultsInput) adultsInput.addEventListener('input', updateSummary);
                if (childrenInput) {
                    childrenInput.addEventListener('input', () => {
                        renderChildrenAges();
                        updateSummary();
                    });
                }
                if (roomsInput) {
                    roomsInput.addEventListener('input', updateSummary);
                    roomsInput.addEventListener('change', updateSummary);
                }

                // flatpickr glue
                function setHiddenDatesAndRefresh(selectedDates) {
                    if (!selectedDates || selectedDates.length === 0) return;
                    const from = selectedDates[0],
                        to = selectedDates[1] || selectedDates[0];

                    function fmt(d) {
                        const y = d.getFullYear(),
                            m = String(d.getMonth() + 1).padStart(2, '0'),
                            day = String(d.getDate()).padStart(2, '0');
                        return `${y}-${m}-${day}`;
                    }
                    if (fromInput) fromInput.value = fmt(from);
                    if (toInput) toInput.value = fmt(to);
                    ensureToAfterFromAndUpdateInputs();
                    updateSummary();
                    updateRoomsAvailabilityMain();
                    refreshAllGroupsAvailability();
                }
                if (typeof flatpickr !== 'undefined' && dateRangeInput) {
                    if (dateRangeInput._flatpickr) dateRangeInput._flatpickr.destroy();
                    flatpickr(dateRangeInput, {
                        mode: "range",
                        minDate: "today",
                        dateFormat: "Y-m-d",
                        defaultDate: [fromInput?.value || new Date().toISOString().slice(0, 10), toInput
                            ?.value || (() => {
                                let d = new Date();
                                d.setDate(d.getDate() + 1);
                                return d.toISOString().slice(0, 10);
                            })()
                        ],
                        onChange: function(selectedDates) {
                            if (selectedDates.length) setHiddenDatesAndRefresh(selectedDates);
                        }
                    });
                    if (fromInput?.value && toInput?.value) setHiddenDatesAndRefresh([new Date(fromInput.value),
                        new Date(toInput.value)
                    ]);
                }

                // initial
                renderChildrenAges();
                updateInputLimitsByRooms();
                updateSummary();
                updateRoomsAvailabilityMain();
                refreshAllGroupsAvailability();


                // ---------- VNPAY modal + submit handler ----------
                function showVNPAYConfirmModal() {
                    const nights = Number(nightsDisplay?.innerText || 0);
                    const basePrice = pricePerNight * (roomsInput ? Number(roomsInput.value || 1) : 1);
                    const adultsCharge = priceAdultsDisplay?.innerText || '0 đ';
                    const childrenCharge = priceChildrenDisplay?.innerText || '0 đ';
                    const addonsEl = document.getElementById('price_addons_display');
                    const addonsCharge = addonsEl ? (addonsEl.innerText || '0 đ') : '0 đ';
                    const finalPerNight = finalPerNightDisplay?.innerText || '0 đ';
                    const total = totalDisplay?.innerText || '0 đ';
                    const deposit = payableDisplay?.innerText || '0 đ';

                    if (document.getElementById('modal_price_base')) document.getElementById('modal_price_base')
                        .innerText = fmtVnd(basePrice);
                    if (document.getElementById('modal_price_adults')) document.getElementById(
                        'modal_price_adults').innerText = adultsCharge;
                    if (document.getElementById('modal_price_children')) document.getElementById(
                        'modal_price_children').innerText = childrenCharge;
                    if (document.getElementById('modal_price_addons')) document.getElementById(
                        'modal_price_addons').innerText = addonsCharge;
                    if (document.getElementById('modal_final_per_night')) document.getElementById(
                        'modal_final_per_night').innerText = finalPerNight;
                    if (document.getElementById('modal_nights_count')) document.getElementById(
                        'modal_nights_count').innerText = nights;
                    if (document.getElementById('modal_total_snapshot')) document.getElementById(
                        'modal_total_snapshot').innerText = total;
                    if (document.getElementById('modal_payable_now')) document.getElementById(
                        'modal_payable_now').innerText = deposit;

                    const modalEl = document.getElementById('vnpayConfirmModal');
                    if (!modalEl) {
                        console.warn('VNPAY modal not found: #vnpayConfirmModal');
                        return;
                    }
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }

                const vnpayProceedBtn = document.getElementById('vnpayProceedBtn');
                // ensure deposit radio changes immediately refresh UI (fix 50%/100% not updating)
                document.querySelectorAll('input[name="deposit_percentage"]').forEach(r => {
                    r.addEventListener('change', () => {
                        updateSummary();
                        syncGroupHiddenInputs();
                    });
                });

                if (vnpayProceedBtn) {
                    vnpayProceedBtn.addEventListener('click', async function() {
                        const modalInstance = bootstrap.Modal.getInstance(document.getElementById(
                            'vnpayConfirmModal'));
                        if (modalInstance) modalInstance.hide();

                        // ensure hidden inputs are in sync
                        syncGroupHiddenInputs();

                        const submitBtn = document.querySelector(
                            '#bookingForm button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.dataset.origHtml = submitBtn.innerHTML;
                            submitBtn.innerHTML =
                                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý...';
                        }

                        // --- build rooms payload (main room + groups) ---
                        const roomsPayload = [];
                        const nightsForPayload = (function() {
                            const f = fromInput?.value,
                                t = toInput?.value;
                            if (!f || !t) return 1;
                            const fromD = new Date(f + 'T00:00:00'),
                                toD = new Date(t + 'T00:00:00');
                            const diff = Math.max(1, Math.round((toD - fromD) / (1000 * 60 *
                                60 * 24)));
                            return diff;
                        })();

                        function computePerNightFromPrice(price) {
                            const weekendN = countWeekendNights(new Date(fromInput.value +
                                'T00:00:00'), new Date(toInput.value + 'T00:00:00'));
                            const weekdayN = Math.max(0, nightsForPayload - weekendN);
                            const base = Number(price || 0);
                            if (nightsForPayload <= 0) return base;
                            const total = (base * weekdayN) + (base * WEEKEND_MULTIPLIER *
                                weekendN);
                            return total / Math.max(1, nightsForPayload);
                        }

                        // 1) main room group
                        const mainPhongId = document.querySelector('input[name="phong_id"]')?.value;
                        if (mainPhongId) {
                            const mainRoomsCnt = Number(roomsInput?.value || 1);
                            const mainAdults = Number(adultsInput?.value || 0);
                            const mainChildren = Number(childrenInput?.value || 0);
                            const mainLoaiId = Number(CURRENT_LOAI_ID || 0) || null;
                            const loaiObj = LOAI_PHONGS && LOAI_PHONGS[String(mainLoaiId)];
                            const repPrice = Number((loaiObj && (loaiObj.tong_gia || loaiObj
                                .gia_mac_dinh)) || pricePerNight || 0);
                            const finalPerNightMain = computePerNightFromPrice(repPrice);
                            const specHashMain = (loaiObj && (loaiObj.spec_signature_hash || loaiObj
                                .specSignatureHash)) || null;
                            const selectedIdsMain = mainPhongId ? [parseInt(mainPhongId, 10)] : [];

                            roomsPayload.push({
                                loai_phong_id: mainLoaiId,
                                rooms_count: mainRoomsCnt,
                                adults: mainAdults,
                                children: mainChildren,
                                nights: nightsForPayload,
                                final_per_night: Math.round(finalPerNightMain),
                                spec_signature_hash: specHashMain,
                                selected_phong_ids: selectedIdsMain
                            });
                        }

                        // 2) MR groups
                        if (MR_CONTAINER) {
                            const groups = Array.from(MR_CONTAINER.querySelectorAll('.mr_group'));
                            groups.forEach(g => {
                                const loai = parseInt(g.dataset.loaiId || 0, 10) || null;
                                const price = Number(g.dataset.pricePerRoom || 0);
                                const roomsCnt = Number(g.querySelector(
                                    '.mr_rooms_count_input')?.value || 1);
                                const adultsG = Number(g.querySelector('.mr_adults_input')
                                    ?.value || 0);
                                const childrenG = Number(g.querySelector(
                                    '.mr_children_input')?.value || 0);
                                const repId = g.dataset.representativePhongId || '';
                                const selected_phong_ids = repId ? [parseInt(repId, 10)] :
                                [];
                                const finalPerNightGroup = computePerNightFromPrice(price);
                                const loaiObjG = LOAI_PHONGS && LOAI_PHONGS[String(loai)];
                                const specG = (loaiObjG && (loaiObjG.spec_signature_hash ||
                                    loaiObjG.specSignatureHash)) || null;

                                roomsPayload.push({
                                    loai_phong_id: loai,
                                    rooms_count: roomsCnt,
                                    adults: adultsG,
                                    children: childrenG,
                                    nights: nightsForPayload,
                                    final_per_night: Math.round(finalPerNightGroup),
                                    spec_signature_hash: specG,
                                    selected_phong_ids: selected_phong_ids
                                });
                            });
                        }

                        const phongId = document.querySelector('input[name="phong_id"]')?.value;
                        const ngayNhan = fromInput?.value;
                        const ngayTra = toInput?.value;
                        const tongTien = document.getElementById('hidden_tong_tien')?.value;
                        const deposit = document.getElementById('hidden_deposit')?.value;
                        const adults = adultsInput?.value;
                        const children = childrenInput?.value;
                        const childrenAges = Array.from(document.querySelectorAll(
                            'input[name="children_ages[]"]')).map(el => el.value);
                        const addons = Array.from(document.querySelectorAll(
                            'input[name="addons[]"]:checked')).map(el => el.value);
                        const roomsCount = roomsInput?.value;
                        const soKhach = Number(adults) + Number(children);
                        const name = document.querySelector('input[name="name"]')?.value.trim();
                        const address = document.querySelector('input[name="address"]')?.value
                            .trim();
                        const phone = document.querySelector('input[name="phone"]')?.value.trim();
                        const phuongThuc = 'vnpay';

                        const voucherId = document.getElementById('voucher_id_input')?.value ||
                            null;
                        const voucherDiscount = document.getElementById('voucher_discount_input')
                            ?.value || null;
                        const voucherCodeHidden = document.getElementById('voucher_code_input')
                            ?.value || null;

                        const depositRadio = document.querySelector(
                            'input[name="deposit_percentage"]:checked');
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
                                    // voucher
                                    voucher_id: voucherId,
                                    voucher_discount: voucherDiscount,
                                    ma_voucher: voucherCodeHidden,
                                    // rooms/groups => server bookingController expects 'rooms' array
                                    rooms: roomsPayload,
                                    phuong_thuc: phuongThuc,
                                    name: name,
                                    address: address,
                                    phone: phone,
                                    ghi_chu: document.querySelector(
                                            'textarea[name="ghi_chu"]')?.value
                                        .trim() || ''
                                }),
                            });

                            const data = await response.json();
                            const depositPercentage = parseFloat(depositPercentageValue) || 50;
                            const tolerance = depositPercentage === 100 ? 2000 : 0;
                            if (parseFloat(deposit) <= 0 || (parseFloat(deposit) > parseFloat(
                                    tongTien) + tolerance)) {
                                showInlineMsg('Deposit không hợp lệ.', true, 5000);
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = submitBtn.dataset.origHtml || 'Đặt';
                                }
                                return;
                            }
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                showInlineMsg(data.error || 'Không thể khởi tạo thanh toán.', true,
                                    5000);
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = submitBtn.dataset.origHtml || 'Đặt';
                                }
                            }
                        } catch (err) {
                            showInlineMsg('Lỗi khi tạo thanh toán: ' + (err.message || err), true,
                                5000);
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = submitBtn.dataset.origHtml || 'Đặt';
                            }
                        }
                    });
                }

                // Expose showVNPAYConfirmModal to be callable by form submit handler
                window.showVNPAYConfirmModal = showVNPAYConfirmModal;

                // ---------- form submit interception (show modal when vnpay) ----------
                (function setupSubmitUx() {
                    const form = document.getElementById('bookingForm');
                    if (!form) return;
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const paymentMethodSelect = document.querySelector('select[name="phuong_thuc"]');

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        if (submitBtn && submitBtn.disabled) return;

                        if (currentAvailableRooms <= 0) {
                            if (availabilityMessageEl) {
                                availabilityMessageEl.className = 'small mt-2 text-danger';
                                availabilityMessageEl.innerText =
                                    `Không thể đặt: Phòng {{ $phong->ma_phong }} không khả dụng trong khoảng thời gian đã chọn.`;
                            }
                            showInlineMsg(
                                `Không thể đặt: Phòng {{ $phong->ma_phong }} không khả dụng trong khoảng thời gian đã chọn.`,
                                true, 3500);
                            return;
                        }

                        // sync hidden inputs so server receives rooms[] on normal submit
                        syncGroupHiddenInputs();

                        const paymentMethod = paymentMethodSelect?.value || '';
                        if (paymentMethod === 'vnpay') {
                            // update modal then show
                            showVNPAYConfirmModal();
                            return;
                        } else {
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.dataset.origHtml = submitBtn.innerHTML;
                                submitBtn.innerHTML =
                                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý...';
                            }
                            // submit normally (hidden inputs contain rooms[])
                            form.submit();
                        }
                    });

                    // small UX: when changing date/rooms re-enable if disabled
                    ['ngay_nhan_phong', 'ngay_tra_phong', 'rooms_count'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.addEventListener('change', () => {
                            const btn = document.querySelector(
                                '#bookingForm button[type="submit"]');
                            if (btn && btn.disabled) {
                                btn.disabled = false;
                                if (btn.dataset.origHtml) btn.innerHTML = btn.dataset
                                    .origHtml;
                            }
                        });
                    });
                })();

            })(); // end booking IIFE
        });
    </script>
@endpush
