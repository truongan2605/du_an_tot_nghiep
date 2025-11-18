@extends('layouts.admin')

@section('title', 'Quản lý đánh giá phòng')

@section('content')
<div class="container mt-4">
    <h3 class="mb-4">Quản lý đánh giá phòng</h3>

    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Tên loại phòng</th>
                <th>Đánh giá trung bình</th>
                <th>Tổng đánh giá</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($phongs as $index => $phong)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $phong->ten_phong ?? 'Không có tên' }}</td>
                    <td>
                        @if ($phong->average_rating)
                            ⭐ {{ number_format($phong->average_rating, 1) }}/5
                        @else
                            Chưa có đánh giá
                        @endif
                    </td>
                    <td>{{ $phong->danhGias->count() }}</td>
                    <td>
                        @if ($phong->new_reviews_count > 0)
                            <span class="badge bg-success">
                                +{{ $phong->new_reviews_count }} đánh giá mới
                            </span>
                        @else
                            <span class="badge bg-secondary">Không có mới</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.danhgia.show', $phong->id) }}" class="btn btn-sm btn-primary">
                            Xem chi tiết
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Chưa có phòng nào được đánh giá</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
