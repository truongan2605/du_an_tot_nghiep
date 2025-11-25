@extends('layouts.admin')

@section('title', 'Quản lý đánh giá phòng')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4 text-primary fw-bold">Quản lý đánh giá phòng</h2>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @forelse ($phongs as $phong)
            @php
                $avg = $phong->average_rating ?? 0;
                $count = $phong->danhGias->count();
            @endphp
            <div class="col">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">{{ $phong->name ?? 'Tên phòng trống' }}</h5>
                            <p class="card-text mb-2">
                                @if($count > 0)
                                    <span class="fw-semibold">⭐ {{ number_format($avg,1) }}/5</span> 
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

    <div class="mt-4">
        {{ $phongs->links() }}
    </div>
</div>
@endsection
