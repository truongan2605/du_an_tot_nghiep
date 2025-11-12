@extends('layouts.admin')

@section('title', 'Chỉnh Sửa Nhân Viên')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 rounded">
            <div class="card-header bg-primary text-white text-center py-3">
                <h5 class="mb-0 fw-bold">Chỉnh Sửa Nhân Viên</h5>
                <small class="opacity-75">Cập nhật thông tin cho {{ $nhan_vien->name }} (ID: {{ $nhan_vien->id }})</small>
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

                <form action="{{ route('admin.nhan-vien.update', $nhan_vien) }}" method="POST" novalidate>
                    @csrf @method('PUT')
                    
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
                                       value="{{ old('name', $nhan_vien->name) }}" 
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
                                       value="{{ old('email', $nhan_vien->email) }}" 
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
                                   value="{{ old('so_dien_thoai', $nhan_vien->so_dien_thoai) }}"
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
                                   value="{{ old('phong_ban', $nhan_vien->phong_ban) }}" 
                                   required>
                            @error('phong_ban')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text small">Tên phòng ban hoặc bộ phận.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.nhan-vien.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay Lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Cập Nhật
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light text-center py-2 border-0">
                <small class="text-muted">Các trường có dấu * là bắt buộc. Thay đổi sẽ được lưu ngay sau khi xác nhận.</small>
            </div>
        </div>
    </div>
</div>
@endsection