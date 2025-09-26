@extends('layouts.admin')

@section('title', 'Quản Lý Khách Hàng')

@section('content')
    <h1 class="mb-4">Danh Sách Khách Hàng</h1>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <table class="table table-striped">
        <thead>
            <tr><th>ID</th><th>Tên</th><th>Email</th><th>SĐT</th><th>Trạng Thái</th><th>Hành Động</th></tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->so_dien_thoai ?? 'N/A' }}</td>
                    <td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>
                        <a href="{{ route('admin.user.show', $user) }}" class="btn btn-info btn-sm">Chi Tiết</a>
                        <form action="{{ route('admin.user.toggle', $user) }}" method="POST" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-warning btn-sm">{{ $user->is_active ? 'Vô Hiệu Hóa' : 'Kích Hoạt' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">Không có khách hàng</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection