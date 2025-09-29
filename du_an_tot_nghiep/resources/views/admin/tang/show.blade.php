@extends('layouts.admin')

@section('title', 'Chi Tiết Tầng - Admin Panel')

@section('content')
    <h1 class="mb-4">Chi Tiết Tầng #{{ $tang->id }}</h1>
    <div class="card">
        <div class="card-body">
            <p><strong>Số Tầng:</strong> {{ $tang->so_tang }}</p>
            <p><strong>Tên:</strong> {{ $tang->ten }}</p>
            <p><strong>Ghi Chú:</strong> {{ $tang->ghi_chu ?? 'Không có' }}</p>
            <p><strong>Ngày Tạo:</strong> {{ $tang->created_at }}</p>
            <p><strong>Ngày Cập Nhật:</strong> {{ $tang->updated_at }}</p>
        </div>
    </div>
    <a href="{{ route('admin.tang.index') }}" class="btn btn-secondary mt-3">Quay Lại</a>
@endsection