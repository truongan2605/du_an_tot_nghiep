@extends('layouts.admin')

@section('title', 'Thêm Nhân Viên Mới')

@section('content')
    <h1 class="mb-4">Thêm Nhân Viên Mới</h1>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('admin.nhan-vien.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Tên</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Mật Khẩu</label>
            <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" required>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()" id="togglePasswordBtn">
                    👁
                </button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Số Điện Thoại</label>
            <input type="text" name="so_dien_thoai" class="form-control" value="{{ old('so_dien_thoai') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Phòng Ban (Bắt Buộc)</label>
            <input type="text" name="phong_ban" class="form-control" required value="{{ old('phong_ban') }}">
        </div>
        <button type="submit" class="btn btn-success">Thêm</button>
        <a href="{{ route('admin.nhan-vien.index') }}" class="btn btn-secondary">Quay Lại</a>
    </form>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const toggleBtn = document.getElementById("togglePasswordBtn");
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleBtn.textContent = "👁‍🗨"; // đổi icon khi hiện
            } else {
                passwordInput.type = "password";
                toggleBtn.textContent = "👁"; // đổi icon khi ẩn
            }
        }
    </script>
@endsection
