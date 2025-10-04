@extends('layouts.admin')

@section('title', 'Thêm Nhân Viên Mới')

@section('content')
    <h1 class="mb-4">Thêm Nhân Viên Mới</h1>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form action="{{ route('admin.nhan-vien.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Tên</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Số Điện Thoại</label>
            <input type="text" name="so_dien_thoai" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Phòng Ban</label>
            <input type="text" name="phong_ban" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Mật Khẩu</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Xác Nhận Mật Khẩu</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Thêm Mới</button>
        <a href="{{ route('admin.nhan-vien.index') }}" class="btn btn-secondary">Quay Lại</a>
    </form>
@endsection