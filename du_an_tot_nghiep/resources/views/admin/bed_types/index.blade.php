@extends('layouts.admin')

@section('title', 'Danh sách Loại Giường')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Danh sách Loại Giường</h2>
        <a href="{{ route('admin.bed-types.create') }}" class="btn btn-primary">+ Thêm loại giường</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Tên</th>
                    <th>Giới hạn người</th>
                    <th>Giá mặc định / đêm</th>
                    <th>Mô tả</th>
                    <th class="text-end">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bedTypes as $bt)
                    <tr>
                        <td>{{ $bt->id }}</td>
                        <td><strong>{{ $bt->name }}</strong><br><small class="text-muted">{{ $bt->slug }}</small></td>
                        <td>{{ $bt->capacity }}</td>
                        <td>{{ number_format($bt->price, 0, ',', '.') }} đ</td>
                        <td style="max-width:350px;">{{ \Illuminate\Support\Str::limit($bt->description ?? '-', 120) }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.bed-types.edit', $bt->id) }}" class="btn btn-sm btn-warning">Sửa</a>

                            <form action="{{ route('admin.bed-types.destroy', $bt->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Xóa loại giường này?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Chưa có loại giường nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
