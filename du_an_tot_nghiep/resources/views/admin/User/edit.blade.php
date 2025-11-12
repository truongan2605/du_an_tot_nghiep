@extends('layouts.admin')

@section('title', 'Chỉnh Sửa Vai Trò ')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-header bg-warning text-white text-center py-3">
                <h5 class="mb-0 fw-bold">Chỉnh Sửa Vai Trò</h5>
                <small class="opacity-75">Cập nhật vai trò cho {{ $user->name }} (ID: {{ $user->id }})</small>
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

                <form action="{{ route('admin.user.update', $user) }}" method="POST" novalidate>
                    @csrf @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Họ và Tên</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" 
                                       class="form-control bg-light" 
                                       value="{{ $user->name }}" 
                                       readonly>
                            </div>
                            <div class="form-text small text-muted">Thông tin không thể chỉnh sửa.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" 
                                       class="form-control bg-light" 
                                       value="{{ $user->email }}" 
                                       readonly>
                            </div>
                            <div class="form-text small text-muted">Thông tin không thể chỉnh sửa.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số Điện Thoại</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" 
                                   class="form-control bg-light" 
                                   value="{{ $user->so_dien_thoai ?? 'N/A' }}" 
                                   readonly>
                        </div>
                        <div class="form-text small text-muted">Thông tin không thể chỉnh sửa.</div>
                    </div>

                    <div class="mb-3">
                        <label for="vai_tro" class="form-label fw-semibold">Vai Trò <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                            <select name="vai_tro" 
                                    id="vai_tro" 
                                    class="form-select @error('vai_tro') is-invalid @enderror" 
                                    required>
                                <option value="khach_hang" {{ old('vai_tro', $user->vai_tro) === 'khach_hang' ? 'selected' : '' }}>Khách Hàng</option>
                                <option value="nhan_vien" {{ old('vai_tro', $user->vai_tro) === 'nhan_vien' ? 'selected' : '' }}>Nhân Viên</option>
                            </select>
                            @error('vai_tro')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text small">Chọn vai trò phù hợp cho tài khoản.</div>
                    </div>

                    <div class="mb-3" id="phong_ban_div" style="display: none;">
                        <label for="phong_ban" class="form-label fw-semibold">Phòng Ban <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                            <input type="text" 
                                   id="phong_ban" 
                                   name="phong_ban" 
                                   class="form-control @error('phong_ban') is-invalid @enderror" 
                                   placeholder="Nhập tên phòng ban" 
                                   value="{{ old('phong_ban', $user->phong_ban ?? '') }}">
                            @error('phong_ban')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text small">Bắt buộc khi chọn vai trò Nhân Viên.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.user.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay Lại
                        </a>
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Xác nhận thay đổi vai trò? Thay đổi này có thể ảnh hưởng đến quyền truy cập.')">
                            <i class="fas fa-save me-1"></i>Cập Nhật Vai Trò
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light text-center py-2 border-0">
                <small class="text-muted">Thay đổi vai trò sẽ cập nhật quyền truy cập ngay lập tức. Đảm bảo chọn đúng để tránh lỗi hệ thống.</small>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('vai_tro').addEventListener('change', function() {
        var phongBanDiv = document.getElementById('phong_ban_div');
        var phongBanInput = phongBanDiv.querySelector('input');
        if (this.value === 'nhan_vien') {
            phongBanDiv.style.display = 'block';
            phongBanInput.required = true;
            phongBanInput.classList.add('is-invalid'); // Trigger validation if empty
        } else {
            phongBanDiv.style.display = 'none';
            phongBanInput.required = false;
            phongBanInput.classList.remove('is-invalid');
            phongBanInput.setCustomValidity('');
        }
    });

    // Set initial state based on current vai_tro
    document.addEventListener('DOMContentLoaded', function() {
        var vaiTroSelect = document.getElementById('vai_tro');
        var phongBanDiv = document.getElementById('phong_ban_div');
        var phongBanInput = phongBanDiv.querySelector('input');
        if (vaiTroSelect.value === 'nhan_vien') {
            phongBanDiv.style.display = 'block';
            phongBanInput.required = true;
        }
    });
</script>
@endsection