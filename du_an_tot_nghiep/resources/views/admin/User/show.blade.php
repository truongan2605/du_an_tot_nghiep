@extends('layouts.admin')

@section('title', 'Chi Tiết Khách Hàng')

@section('content')
    <h1 class="mb-4">Chi Tiết Khách Hàng #{{ $user->id }}</h1>
    <div class="card">
        <div class="card-body">
            <p><strong>Tên:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>SĐT:</strong> {{ $user->so_dien_thoai ?? 'N/A' }}</p>
            <p><strong>Phòng Ban:</strong> {{ $user->phong_ban ?? 'N/A' }}</p>
            <p><strong>Vai Trò:</strong> {{ $user->vai_tro }}</p>
            <p><strong>Trạng Thái:</strong> {{ $user->is_active ? 'Active' : 'Inactive' }}</p>
            <p><strong>Email Đã Xác Thực:</strong> {{ $user->email_verified_at ? 'Có' : 'Không' }}</p>
        </div>
    </div>
    <a href="{{ route('admin.user.index') }}" class="btn btn-secondary mt-3">Quay Lại</a>
@endsection