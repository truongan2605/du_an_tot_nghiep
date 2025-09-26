@extends('layouts.admin')

@section('title', 'Quản Lý Nhân Viên')

@section('content')
    <h1 class="mb-4">Danh Sách Nhân Viên</h1>
    <a href="{{ route('admin.nhan-vien.create') }}" class="btn btn-primary mb-3">Thêm Nhân Viên Mới</a>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <table class="table table-striped">
        <thead>
            <tr><th>ID</th><th>Tên</th><th>Email</th><th>Phòng Ban</th><th>Trạng Thái</th><th>Hành Động</th></tr>
        </thead>
        <tbody>
            @forelse($nhanViens as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phong_ban ?? 'N/A' }}</td>
                    <td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>
                        <a href="{{ route('admin.nhan-vien.show', $user) }}" class="btn btn-info btn-sm">Chi Tiết</a>
                        <a href="{{ route('admin.nhan-vien.edit', $user) }}" class="btn btn-warning btn-sm">Chỉnh Sửa</a>
                        <form action="{{ route('admin.nhan-vien.toggle', $user) }}" method="POST" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-secondary btn-sm">{{ $user->is_active ? 'Vô Hiệu Hóa' : 'Kích Hoạt' }}</button>
                        </form>
                        <form action="{{ route('admin.nhan-vien.destroy', $user) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xác nhận xóa nhân viên?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">Không có nhân viên</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection