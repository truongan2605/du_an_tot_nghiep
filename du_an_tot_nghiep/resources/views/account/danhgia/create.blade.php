@extends('layouts.app')

@section('content')
<div class="container mt-4">
   <h2>Đánh giá các phòng:</h2>
<ul>
@foreach($booking->phongs as $phong)
    <li>{{ $phong->name }}</li>
@endforeach
</ul>
    <form action="{{ route('account.danhgia.store', $booking->id) }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Số sao</label>
            <select name="rating" class="form-control" required>
                <option value="">-- Chọn sao --</option>
                <option value="1">1 sao</option>
                <option value="2">2 sao</option>
                <option value="3">3 sao</option>
                <option value="4">4 sao</option>
                <option value="5">5 sao</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Nội dung đánh giá</label>
            <textarea name="noi_dung" class="form-control" rows="4" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            Gửi đánh giá
        </button>
    </form>
</div>
@endsection
