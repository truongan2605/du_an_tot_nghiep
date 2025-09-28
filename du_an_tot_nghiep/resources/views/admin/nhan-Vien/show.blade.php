@extends('layouts.admin')

@section('title', 'Chi Tiết Nhân Viên')

@section('content')
    <h1 class="mb-4">Chi Tiết Nhân Viên {{ $user->name }}</h1>
    <div class="card">
        <div class="card-body">
            <p><strong>Tên:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Số Điện Thoại:</strong> {{ $user->so_dien_thoai ?? 'N/A' }}</p>
            <p><strong>Phòng Ban:</strong> {{ $user->phong_ban ?? 'N/A' }}</p>
            <p><strong>Trạng Thái:</strong> {{ $user->is_active ? 'Active' : 'Inactive' }}</p>
        </div>
    </div>
    <a href="{{ route('admin.nhan-vien.index') }}" class="btn btn-secondary mt-3">Quay Lại</a>
@endsection