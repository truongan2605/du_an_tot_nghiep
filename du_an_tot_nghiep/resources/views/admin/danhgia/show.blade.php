@extends('layouts.admin')
@section('title', 'Chi tiết đánh giá phòng')

@section('content')

<style>
    .reply-item {
        background: #f1f2f6;
        border-radius: 10px;
        padding: 10px 15px;
        margin-top: 10px;
    }
</style>

<div class="container py-4">

    <h3 class="fw-bold mb-4">
        <i class="fas fa-comment-dots text-primary"></i> Chi tiết đánh giá phòng
    </h3>

    @foreach ($danhGias as $danhGia)
        <!-- ĐÁNH GIÁ GỐC -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">

                <div class="d-flex">
                    <img src="https://ui-avatars.com/api/?name={{ $danhGia->user->name }}"
                         class="rounded-circle me-3" width="50">

                    <div>
                        <h5 class="fw-bold">{{ $danhGia->user->name }}</h5>
                        <div class="text-warning mb-2">⭐ {{ $danhGia->rating }}/5</div>
                        <p class="text-muted fst-italic">"{{ $danhGia->noi_dung }}"</p>
                        <small class="text-secondary">{{ $danhGia->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                </div>

                <!-- NÚT HIỆN TRẢ LỜI -->
                @if($danhGia->replies->count() > 0)
                    <button class="btn btn-outline-primary mt-3 btn-sm"
                        onclick="document.getElementById('reply-list-{{ $danhGia->id }}').classList.toggle('d-none')">
                        <i class="fas fa-reply"></i> Xem {{ $danhGia->replies->count() }} trả lời
                    </button>
                @endif

                <!-- DANH SÁCH TRẢ LỜI -->
                <div id="reply-list-{{ $danhGia->id }}" class="d-none mt-3">
                    @foreach($danhGia->replies as $rep)
                        <div class="reply-item">
                            <strong>Admin trả lời:</strong>
                            <p class="mb-1">{{ $rep->noi_dung }}</p>
                            <small class="text-muted">{{ $rep->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>

        <!-- FORM TRẢ LỜI CHO TỪNG ĐÁNH GIÁ -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Trả lời người dùng</h5>

                <form action="{{ route('admin.danhgia.reply', $danhGia->id) }}" method="POST">
                    @csrf

                    <textarea name="noi_dung" class="form-control mb-3" rows="3"
                        placeholder="Nhập nội dung trả lời..." required></textarea>

                    <button class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Gửi trả lời
                    </button>
                </form>

            </div>
        </div>
    @endforeach

    <!-- PHÂN TRANG -->
    <div class="d-flex justify-content-end mt-3">
        {{ $danhGias->links() }}
    </div>

</div>
@endsection
