@extends('layouts.admin')

@section('title', 'Thêm Khách Hàng Mới')

@section('content')
    <h1 class="mb-4">Thêm Khách Hàng Mới</h1>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('admin.user.store') }}" method="POST">
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
            <label class="form-label">Số Điện Thoại</label>
            <input type="text" name="so_dien_thoai" class="form-control" value="{{ old('so_dien_thoai') }}">
        </div>
        
        {{-- TRƯỜNG MẬT KHẨU GỐC --}}
        <div class="mb-3">
            <label class="form-label">Mật Khẩu</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        
        {{-- TRƯỜNG XÁC NHẬN MẬT KHẨU (ĐÃ THÊM) --}}
        <div class="mb-3">
            <label class="form-label">Xác Nhận Mật Khẩu</label>
            {{-- Đảm bảo tên là 'password_confirmation' --}}
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success">Thêm Khách Hàng</button>
        <a href="{{ route('admin.user.index') }}" class="btn btn-secondary">Quay Lại</a>
    </form>
@endsection