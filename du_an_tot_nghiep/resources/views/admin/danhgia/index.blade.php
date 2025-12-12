@extends('layouts.admin')

@section('title', 'Quản lý đánh giá phòng')

@section('content')

<style>
    /* Hover mở dropdown */
    .dropdown:hover .dropdown-menu {
        display: block;
        margin-top: 0;
    }
</style>

<div class="container mt-5">

    <h2 class="mb-4 text-primary fw-bold">Quản lý đánh giá phòng</h2>

    <!-- 🔍 BỘ LỌC TÌM KIẾM -->
    <form method="GET" class="mb-4">
        <div class="d-flex flex-wrap gap-3">

            <!-- 🎯 Dropdown chọn loại phòng -->
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button">
                    {{ request('loai_phong') 
                        ? 'Loại: ' . $loaiPhongs->find(request('loai_phong'))->ten_loai 
                        : 'Chọn loại phòng' }}
                </button>

                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.danhgia.index') }}">Tất cả</a>
                    </li>

                    @foreach($loaiPhongs as $loai)
                        <li>
                            <a class="dropdown-item"
                               href="{{ route('admin.danhgia.index', ['loai_phong' => $loai->id] + request()->except('page')) }}">
                                {{ $loai->ten }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- 🔎 Tìm theo tên phòng -->
            <input type="text"
                   name="keyword"
                   class="form-control"
                   placeholder="Nhập tên phòng..."
                   value="{{ request('keyword') }}"
                   style="max-width: 260px;">

            <button type="submit" class="btn btn-primary">Tìm kiếm</button>

        </div>
    </form>

    <!-- 🏘 DANH SÁCH PHÒNG -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @forelse ($phongs as $phong)
            @php
                $avg = $phong->rating_trung_binh ?? 0; 
                $count = $phong->tong_danh_gia ?? 0;  // CHỈ tính đánh giá gốc, không tính trả lời
            @endphp

            <div class="col">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column justify-content-between">

                        <div>
                            <h5 class="card-title">{{ $phong->name ?? 'Tên phòng trống' }}</h5>

                            <p class="card-text mb-2">
                                @if ($count > 0)
                                    <span class="fw-semibold">⭐ {{ number_format($avg, 1) }}/5</span>
                                    <span class="text-muted">({{ $count }} đánh giá)</span>
                                @else
                                    <span class="text-muted">Chưa có đánh giá</span>
                                @endif
                            </p>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('admin.danhgia.show', $phong->id) }}" class="btn btn-primary w-100">
                                Xem chi tiết
                            </a>
                        </div>

                    </div>
                </div>
            </div>

        @empty
            <div class="col-12">
                <div class="alert alert-secondary text-center">
                    Chưa có phòng nào được đánh giá
                </div>
            </div>
        @endforelse
    </div>

    <!-- PHÂN TRANG -->
    <div class="mt-4">
        {{ $phongs->appends(request()->query())->links() }}
    </div>
</div>
@endsection
