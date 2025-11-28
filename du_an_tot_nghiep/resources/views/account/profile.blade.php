@extends('layouts.app')

@section('title', 'Hồ sơ của tôi')

@section('content')
    <section class="pt-3">
        <div class="container">
            <div class="row">

                <!-- Sidebar START -->
                <div class="col-lg-4 col-xl-3">
                    <div class="offcanvas-lg offcanvas-end" tabindex="-1" id="offcanvasSidebar">
                        <div class="offcanvas-header justify-content-end pb-2">
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>

                        <div class="offcanvas-body p-3 p-lg-0">
                            <div class="card bg-light w-100">
                                <div class="position-absolute top-0 end-0 p-3">
                                    <a href="{{ route('account.settings') }}" class="text-primary-hover"
                                        data-bs-toggle="tooltip" title="Chỉnh sửa hồ sơ">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </div>

                                <div class="card-body p-3">
                                    <div class="text-center mb-3">
                                        <div class="avatar avatar-xl mb-2">
                                            <img class="avatar-img rounded-circle border border-2 border-white"
                                                src="{{ auth()->user() && auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}"
                                                alt="avatar">
                                        </div>
                                        <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                                        <a href="mailto:{{ auth()->user()->email }}"
                                            class="text-reset text-primary-hover small">{{ auth()->user()->email }}</a>
                                        <hr>
                                    </div>

                                    <ul class="nav nav-pills-primary-soft flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="{{ route('account.settings') }}"><i
                                                    class="bi bi-person fa-fw me-2"></i>Hồ sơ của tôi</a>
                                        </li>
                                        <!--  THÊM DÒNG NÀY: Ưu đãi -->
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('account.rewards') }}">
                                                <i class="bi bi-gift fa-fw me-2"></i>Ưu đãi
                                            </a>
                                        </li>
                                        <!-- END -->
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('account.booking.index') }}"><i
                                                    class="bi bi-ticket-perforated fa-fw me-2"></i>Đặt phòng của tôi</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ url('/account/wishlist') }}"><i
                                                    class="bi bi-heart fa-fw me-2"></i>Danh sách yêu thích</a>
                                        </li>
                                        <li class="nav-item">
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit"
                                                    class="btn nav-link text-start text-danger bg-danger-soft-hover w-100"><i
                                                        class="fas fa-sign-out-alt fa-fw me-2"></i>Đăng xuất</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sidebar END -->

                <!-- Main content START -->
                <div class="col-lg-8 col-xl-9">
                    <div class="d-grid mb-0 d-lg-none w-100">
                        <button class="btn btn-primary mb-4" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                            <i class="fas fa-sliders-h"></i> Menu
                        </button>
                    </div>

                    <div class="vstack gap-4">
                        <!-- Personal info START -->
                        <div class="card border">
                            <div class="card-header border-bottom">
                                <h4 class="card-header-title">Thông tin cá nhân</h4>
                            </div>
                            @if (session('status') === 'verification-link-sent')
                                <div class="alert alert-success">
                                    Liên kết xác thực đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư đến (hoặc
                                    Mailtrap).
                                </div>
                            @elseif (session('status') === 'already-verified')
                                <div class="alert alert-info">
                                    Email của bạn đã được xác thực.
                                </div>
                            @endif

                            @if (!auth()->user()->is_active)
                                <form method="POST" action="{{ route('verification.send') }}"
                                    style="margin-top: 15px; margin-left: 15px">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary mb-3">
                                        Gửi email xác thực
                                    </button>
                                </form>

                                <p class="small text" style="margin-left: 15px">
                                    Bạn cần xác thực email để mở khóa tất cả tính năng. Kiểm tra hộp thư đến hoặc nhấp vào
                                    trên để gửi lại.
                                </p>
                            @endif

                            <div class="card-body">
                                <form class="row g-3" method="POST" action="{{ route('account.settings.update') }}"
                                    enctype="multipart/form-data" id="profile-form">
                                    @csrf
                                    @method('PATCH')
                                    {{-- avatar --}}
                                    <div class="col-12">
                                        <label class="form-label">Tải lên ảnh đại diện<span
                                                class="text-danger">*</span></label>
                                        <div class="d-flex align-items-center">
                                            <label class="position-relative me-4" for="uploadfile-1"
                                                title="Thay đổi ảnh này">
                                                <span class="avatar avatar-xl">
                                                    <img id="uploadfile-1-preview"
                                                        class="avatar-img rounded-circle border border-white border-3 shadow"
                                                        src="{{ auth()->user() && auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}"
                                                        alt="">
                                                </span>
                                            </label>
                                            <label class="btn btn-sm btn-primary-soft mb-0" for="uploadfile-1">Thay
                                                đổi</label>
                                            <input id="uploadfile-1" name="avatar" class="form-control d-none"
                                                type="file" accept="image/*">
                                        </div>
                                    </div>

                                    <div class="text-center mt-3">
                                        <div class="badge bg-warning p-2 px-3 text-dark rounded-pill">
                                            <i class="bi bi-star-fill me-1"></i>
                                            Hạng khách hàng: <strong>Vàng</strong>
                                        </div>
                                    </div>

                                    {{-- name --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Họ và tên<span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ old('name', auth()->user()->name) }}"
                                            placeholder="Nhập họ và tên của bạn">
                                    </div>

                                    {{-- email --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Địa chỉ email<span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email', auth()->user()->email) }}"
                                            placeholder="Nhập địa chỉ email của bạn">
                                    </div>

                                    {{-- mobile --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Số điện thoại<span class="text-danger">*</span></label>
                                        <input type="text" name="so_dien_thoai" class="form-control"
                                            value="{{ old('so_dien_thoai', auth()->user()->so_dien_thoai ?? '') }}"
                                            placeholder="Nhập số điện thoại của bạn">
                                    </div>

                                    {{-- nationality --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Quốc tịch<span class="text-danger">*</span></label>
                                        <select class="form-select js-choice" name="country">
                                            <option value="">Chọn quốc gia của bạn</option>
                                            <option value="USA"
                                                {{ old('country', auth()->user()->country ?? '') == 'USA' ? 'selected' : '' }}>
                                                USA</option>
                                            <option value="France"
                                                {{ old('country', auth()->user()->country ?? '') == 'France' ? 'selected' : '' }}>
                                                France</option>
                                            <option value="India"
                                                {{ old('country', auth()->user()->country ?? '') == 'India' ? 'selected' : '' }}>
                                                India</option>
                                            <option value="UK"
                                                {{ old('country', auth()->user()->country ?? '') == 'UK' ? 'selected' : '' }}>
                                                UK</option>
                                            <option value="VN"
                                                {{ old('country', auth()->user()->country ?? '') == 'VN' ? 'selected' : '' }}>
                                                Việt Nam</option>
                                        </select>
                                    </div>

                                    {{-- dob --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Ngày sinh<span class="text-danger">*</span></label>

                                        <input type="text" name="dob" class="form-control flatpickr"
                                            value="{{ old('dob', optional(auth()->user()->dob)->format('d M Y') ?? '') }}"
                                            placeholder="Nhập ngày sinh" data-date-format="d M Y">
                                    </div>


                                    {{-- gender --}}
                                    <div class="col-md-6">
                                        <label class="form-label">Chọn giới tính<span class="text-danger">*</span></label>
                                        <div class="d-flex gap-4">
                                            @php $gender = old('gender', auth()->user()->gender ?? 'male'); @endphp
                                            <div class="form-check radio-bg-light">
                                                <input class="form-check-input" type="radio" name="gender"
                                                    id="g1" value="male"
                                                    {{ $gender == 'male' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="g1">Nam</label>
                                            </div>
                                            <div class="form-check radio-bg-light">
                                                <input class="form-check-input" type="radio" name="gender"
                                                    id="g2" value="female"
                                                    {{ $gender == 'female' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="g2">Nữ</label>
                                            </div>
                                            <div class="form-check radio-bg-light">
                                                <input class="form-check-input" type="radio" name="gender"
                                                    id="g3" value="other"
                                                    {{ $gender == 'other' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="g3">Khác</label>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- address --}}
                                    <div class="col-12">
                                        <label class="form-label">Địa chỉ</label>
                                        <textarea name="address" class="form-control" rows="3" spellcheck="false">{{ old('address', auth()->user()->address ?? '') }}</textarea>
                                    </div>

                                    {{-- submit --}}
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary mb-0">Lưu thay đổi</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Update Password -->
                        @if (auth()->user()->provider)
                        @else
                            <div class="card border">
                                <div class="card-header border-bottom">
                                    <h4 class="card-header-title">Cập nhật mật khẩu</h4>
                                    <p class="mb-0">Thay đổi mật khẩu tài khoản của bạn</p>
                                </div>

                                <form class="card-body" method="POST" action="{{ route('password.update') }}">
                                    @csrf
                                    @method('PUT')

                                    @if (session('status') === 'password-updated')
                                        <div class="alert alert-success">Mật khẩu đã được cập nhật thành công.</div>
                                    @endif

                                    @if ($errors->updatePassword->any())
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                @foreach ($errors->updatePassword->all() as $err)
                                                    <li>{{ $err }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu hiện tại</label>
                                        <div class="input-group">
                                            <input id="current_password" class="form-control" type="password"
                                                name="current_password" placeholder="Nhập mật khẩu hiện tại" required>
                                            <span class="input-group-text p-0 bg-transparent">
                                                <i class="fas fa-eye-slash cursor-pointer password-toggle"
                                                    data-target="current_password" title="Hiện/Ẩn"
                                                    style="padding: 10px"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Nhập mật khẩu mới</label>
                                        <div class="input-group">
                                            <input id="new_password" class="form-control" type="password"
                                                name="password" placeholder="Nhập mật khẩu mới" required>
                                            <span class="input-group-text p-0 bg-transparent">
                                                <i class="fas fa-eye-slash cursor-pointer password-toggle"
                                                    data-target="new_password" title="Hiện/Ẩn" style="padding: 10px"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Xác nhận mật khẩu mới</label>
                                        <div class="input-group">
                                            <input id="new_password_confirmation" class="form-control" type="password"
                                                name="password_confirmation" placeholder="Xác nhận mật khẩu mới" required>
                                            <span class="input-group-text p-0 bg-transparent">
                                                <i class="fas fa-eye-slash cursor-pointer password-toggle"
                                                    data-target="new_password_confirmation" title="Hiện/Ẩn"
                                                    style="padding: 10px"></i>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <button class="btn btn-primary mb-0">Đổi mật khẩu</button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 fw-bold">
                                🎁 Ưu đãi khách hàng thân thiết
                            </h5>
                        </div>

                        <div class="card-body">
                            @php
                                $user = auth()->user();
                                $currentLevel = $user ? $user->getMemberLevelName() : 'Đồng';
                                $currentDiscount = $user ? $user->getMemberDiscountPercent() : 3.0;
                            @endphp
                            <div class="alert alert-warning">
                                <strong>Hạng hiện tại: {{ $currentLevel }}</strong> – Bạn được giảm {{ number_format($currentDiscount, 1) }}% cho mọi đơn đặt phòng.
                            </div>

                            <ul class="list-group">
                                <li class="list-group-item {{ $currentLevel == 'Đồng' ? 'active' : '' }}">
                                    🥉 <strong>Đồng</strong> – Giảm 3%
                                </li>
                                <li class="list-group-item {{ $currentLevel == 'Bạc' ? 'active' : '' }}">
                                    🥈 <strong>Bạc</strong> – Giảm 5%
                                </li>
                                <li class="list-group-item {{ $currentLevel == 'Vàng' ? 'active' : '' }}">
                                    🥇 <strong>Vàng</strong> – Giảm 10%
                                </li>
                                <li class="list-group-item {{ $currentLevel == 'Kim Cương' ? 'active' : '' }}">
                                    👑 <strong>Kim Cương</strong> – Giảm 15% + Ưu tiên hỗ trợ khách hàng
                                </li>
                            </ul>
                            
                            <div class="mt-3">
                                <a href="{{ route('account.rewards') }}" class="btn btn-sm btn-outline-primary">
                                    Xem chi tiết ưu đãi
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- Main content END -->

            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('uploadfile-1');
            const preview = document.getElementById('uploadfile-1-preview');
            if (input && preview) {
                input.addEventListener('change', function(e) {
                    const f = e.target.files[0];
                    if (!f) return;
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        preview.src = ev.target.result;
                    };
                    reader.readAsDataURL(f);
                });
            }

            // init Choices
            if (typeof Choices === 'function') {
                document.querySelectorAll('.js-choice').forEach(el => {
                    if (!el.dataset.choicesInitialized) {
                        new Choices(el, {
                            searchEnabled: true,
                            itemSelectText: ''
                        });
                        el.dataset.choicesInitialized = '1';
                    }
                });
            }

            if (typeof flatpickr === 'function') {
                document.querySelectorAll('.flatpickr').forEach(el => {
                    flatpickr(el, {
                        dateFormat: "d M Y",
                        allowInput: true,
                    });
                });
            }

            document.querySelectorAll('.password-toggle').forEach(icon => {
                icon.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    if (!targetId) return;
                    const input = document.getElementById(targetId);
                    if (!input) return;
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    } else {
                        input.type = 'password';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    }
                });
            });
        });
    </script>
@endpush
