@extends('layouts.admin')

@section('title', 'Chỉnh Sửa Vai Trò Khách Hàng')

@section('content')
    <h1 class="mb-4">Chỉnh Sửa Vai Trò Khách Hàng #{{ $user->id }}</h1>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form action="{{ route('admin.user.update', $user) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Tên</label>
            <input type="text" class="form-control" value="{{ $user->name }}" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" value="{{ $user->email }}" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Số Điện Thoại</label>
            <input type="text" class="form-control" value="{{ $user->so_dien_thoai ?? 'N/A' }}" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Vai Trò</label>
            <select name="vai_tro" class="form-control" id="vai_tro" required>
                <option value="khach_hang" {{ $user->vai_tro === 'khach_hang' ? 'selected' : '' }}>Khách Hàng</option>
                <option value="nhan_vien" {{ $user->vai_tro === 'nhan_vien' ? 'selected' : '' }}>Nhân Viên</option>
            </select>
        </div>
        <div class="mb-3" id="phong_ban_div" style="display: none;">
            <label class="form-label">Phòng Ban (Bắt Buộc Khi Là Nhân Viên)</label>
            <input type="text" name="phong_ban" class="form-control" value="{{ $user->phong_ban ?? '' }}">
        </div>
        <button type="submit" class="btn btn-success" onclick="return confirm('Xác nhận thay đổi vai trò?')">Cập Nhật</button>
        <a href="{{ route('admin.user.index') }}" class="btn btn-secondary">Quay Lại</a>

    <script>
        document.getElementById('vai_tro').addEventListener('change', function() {
            var phongBanDiv = document.getElementById('phong_ban_div');
            if (this.value === 'nhan_vien') {
                phongBanDiv.style.display = 'block';
                phongBanDiv.querySelector('input').required = true;
            } else {
                phongBanDiv.style.display = 'none';
                phongBanDiv.querySelector('input').required = false;
            }
        });
        // Set initial state based on current vai_tro
        var vaiTroSelect = document.getElementById('vai_tro');
        if (vaiTroSelect.value === 'nhan_vien') {
            document.getElementById('phong_ban_div').style.display = 'block';
            document.getElementById('phong_ban_div').querySelector('input').required = true;
        }
    </script>
@endsection