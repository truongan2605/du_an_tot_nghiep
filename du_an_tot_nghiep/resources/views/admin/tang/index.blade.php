@extends('layouts.admin')

@section('title', 'Quản Lý Tầng - Admin Panel')

@section('content')
    <h1 class="mb-4">Danh Sách Tầng</h1>
    <a href="{{ route('admin.tang.create') }}" class="btn btn-primary mb-3">Tạo Mới</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Số Tầng</th>
                <th>Tên</th>
                <th>Ghi Chú</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tangList as $tang)
                <tr>
                    <td>{{ $tang->id }}</td>
                    <td>{{ $tang->so_tang }}</td>
                    <td>{{ $tang->ten }}</td>
                    <td>{{ $tang->ghi_chu ?? 'Không có' }}</td>
                    <td>
                        <a href="{{ route('admin.tang.show', $tang) }}" class="btn btn-info btn-sm">Xem</a>
                        <a href="{{ route('admin.tang.edit', $tang) }}" class="btn btn-warning btn-sm">Sửa</a>
                        <form action="{{ route('admin.tang.destroy', $tang) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Xác nhận xóa tầng?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">Không có dữ liệu tầng.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection