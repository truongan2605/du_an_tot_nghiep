@extends('layouts.admin')

@section('title', 'Chỉnh Sửa Nhân Viên')

@section('content')
    <h1 class="mb-4">Chỉnh Sửa Nhân Viên #{{ $user->id }}</h1>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('admin.nhan-vien.update', $user) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Tên</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $user->name) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required value="{{ old('email', $user->email) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Số Điện Thoại</label>
            <input type="text" name="so_dien_thoai" class="form-control" value="{{ old('so_dien_thoai', $user->so_dien_thoai) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Phòng Ban</label>
            <input type="text" name="phong_ban" class="form-control" required value="{{ old('phong_ban', $user->phong_ban) }}">
        </div>
        <button type="submit" class="btn btn-success">Cập Nhật</button>
        <a href="{{ route('admin.nhan-vien.index') }}" class="btn btn-secondary">Quay Lại</a>
    </form>
@endsection