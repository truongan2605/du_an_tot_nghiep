@extends('layouts.app')

@section('title', 'Đánh giá phòng')

@section('content')
<div class="container mt-4">

    <h3 class="mb-4">Đánh giá phòng: {{ $booking->phong->ten }}</h3>

    <form action="{{ route('account.danhgia.store', $booking->id) }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label fw-bold">Số sao</label>
            <select name="rating" class="form-control" required>
                <option value="">-- Chọn số sao --</option>
                @for ($i=1; $i<=5; $i++)
                    <option value="{{ $i }}">{{ $i }} ⭐</option>
                @endfor
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Nội dung đánh giá</label>
            <textarea name="noi_dung" class="form-control" rows="4" required></textarea>
        </div>

        <button class="btn btn-primary w-100">Gửi đánh giá</button>
    </form>

</div>
@endsection
