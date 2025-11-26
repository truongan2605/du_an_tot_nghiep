@extends('layouts.admin')

@section('title', 'Đánh giá phòng')

@section('content')
<div class="container mt-4">

    <h3 class="fw-bold mb-3">Đánh giá cho phòng: {{ $phong->name ?? 'Không có tên' }}</h3>

    @if ($danhGias->count() == 0)
        <div class="alert alert-secondary">Phòng này chưa có đánh giá nào.</div>
    @endif

    <div class="list-group">
        @foreach ($danhGias as $dg)
            <div class="list-group-item shadow-sm mb-3 p-3 rounded">

                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-1">
                        ⭐ {{ $dg->rating }}/5
                    </h5>
                    <small class="text-muted">{{ $dg->created_at->format('d/m/Y H:i') }}</small>
                </div>

                <p class="mb-2">{{ $dg->noi_dung }}</p>

                <div class="text-end">
                    <form action="{{ route('admin.danhgia.toggle', $dg->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm {{ $dg->status ? 'btn-warning' : 'btn-success' }}">
                            {{ $dg->status ? 'Ẩn đánh giá' : 'Hiện đánh giá' }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-3">
        {{ $danhGias->links() }}
    </div>

</div>
@endsection
