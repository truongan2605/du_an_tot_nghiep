@extends('layouts.admin')

@section('title', 'Thêm Nhân Viên Mới')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-header bg-primary text-white text-center py-3">
                <h5 class="mb-0 fw-bold">Thêm Nhân Viên Mới</h5>
                <small class="opacity-75">Điền thông tin chi tiết để tạo tài khoản nhân viên mới</small>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Có lỗi xảy ra!</strong> Vui lòng kiểm tra lại thông tin.
                        <ul class="mb-0 mt-2 small">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('admin.nhan-vien.store') }}" method="POST" novalidate>
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label fw-semibold">Họ và Tên <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       placeholder="Nhập họ và tên đầy đủ" 
                                       value="{{ old('name') }}" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text small">Tên đầy đủ của nhân viên.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       placeholder="example@company.com" 
                                       value="{{ old('email') }}" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text small">Email công ty hợp lệ.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="so_dien_thoai" class="form-label fw-semibold">Số Điện Thoại</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" 
                                   id="so_dien_thoai" 
                                   name="so_dien_thoai" 
                                   class="form-control @error('so_dien_thoai') is-invalid @enderror" 
                                   placeholder="0123 456 789" 
                                   value="{{ old('so_dien_thoai') }}"
                                   pattern="[0-9\s+()-]{10,15}">
                            @error('so_dien_thoai')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text small">Số điện thoại di động hoặc bàn.</div>
                    </div>

                    <div class="mb-3">
                        <label for="phong_ban" class="form-label fw-semibold">Phòng Ban <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" 
                                   id="phong_ban" 
                                   name="phong_ban" 
                                   class="form-control @error('phong_ban') is-invalid @enderror" 
                                   placeholder="Nhập tên phòng ban" 
                                   value="{{ old('phong_ban') }}" 
                                   required>
                            @error('phong_ban')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text small">Tên phòng ban hoặc bộ phận.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label fw-semibold">Mật Khẩu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       placeholder="Nhập mật khẩu mạnh" 
                                       required
                                       minlength="8">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text small">Ít nhất 8 ký tự, bao gồm chữ hoa, thường, số và ký tự đặc biệt.</div>
                            <div id="passwordStrength" class="mt-1 small text-muted"></div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">Xác Nhận Mật Khẩu <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       class="form-control @error('password_confirmation') is-invalid @enderror" 
                                       placeholder="Nhập lại mật khẩu" 
                                       required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text small">Xác nhận lại mật khẩu để tránh lỗi nhập.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.nhan-vien.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay Lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i>Thêm Nhân Viên
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light text-center py-2 border-0">
                <small class="text-muted">Bằng cách thêm nhân viên, bạn đồng ý với <a href="#" class="text-decoration-none fw-semibold">chính sách bảo mật</a> của công ty. Các trường có dấu * là bắt buộc.</small>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function () {
        const password = document.getElementById('password');
        const icon = this.querySelector('i');
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
        const confirmPassword = document.getElementById('password_confirmation');
        const icon = this.querySelector('i');
        if (confirmPassword.type === 'password') {
            confirmPassword.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            confirmPassword.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthDiv = document.getElementById('passwordStrength');
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let feedback = '';

        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        switch (strength) {
            case 0: case 1: feedback = 'Rất yếu'; className = 'text-danger'; break;
            case 2: feedback = 'Yếu'; className = 'text-warning'; break;
            case 3: feedback = 'Trung bình'; className = 'text-info'; break;
            case 4: feedback = 'Mạnh'; className = 'text-success'; break;
            case 5: feedback = 'Rất mạnh'; className = 'text-success fw-bold'; break;
        }

        strengthDiv.textContent = password ? feedback : '';
        strengthDiv.className = password ? className : 'small text-muted';
    });

    // Real-time password confirmation check
    const confirmInput = document.getElementById('password_confirmation');
    confirmInput.addEventListener('input', function() {
        if (this.value && this.value !== passwordInput.value) {
            this.classList.add('is-invalid');
            this.setCustomValidity('Mật khẩu xác nhận không khớp.');
        } else {
            this.classList.remove('is-invalid');
            this.setCustomValidity('');
        }
    });
</script>
@endsection