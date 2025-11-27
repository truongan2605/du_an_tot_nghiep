@extends('layouts.admin')
@section('title', 'Chi tiết đánh giá phòng')

@section('content')

<style>
    .review-card {
        transition: 0.25s;
        border-radius: 14px;
    }
    .review-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }

    .reply-item {
        background: #f1f2f6;
        border-radius: 10px;
        padding: 10px 15px;
        margin-top: 10px;
        border-left: 4px solid #1e90ff;
    }

    .reply-form {
        display: none;
        animation: fadeIn .3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .reply-btn-hover {
        opacity: 0;
        transition: 0.3s;
    }
    .review-card:hover .reply-btn-hover {
        opacity: 1;
    }

    /* Search UI đẹp */
    .filter-box {
        background: #ffffff;
        border-radius: 12px;
        padding: 18px 22px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
</style>

<div class="container py-4">

    <h3 class="fw-bold mb-4">
        <i class="fas fa-comment-dots text-primary"></i>
        Chi tiết đánh giá phòng
    </h3>

    <!-- ======================== SEARCH BAR ======================== -->
    <div class="filter-box mb-4">
        <form action="" method="GET" class="row g-3">

            <!-- Tìm theo tên -->
            <div class="col-md-4">
                <label class="form-label fw-bold">Tìm theo người dùng</label>
                <input type="text" name="keyword" class="form-control"
                       placeholder="Nhập tên người dùng..."
                       value="{{ request('keyword') }}">
            </div>

            <!-- Lọc theo ngày -->
            <div class="col-md-3">
                <label class="form-label fw-bold">Từ ngày</label>
                <input type="date" name="from_date" class="form-control"
                       value="{{ request('from_date') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Đến ngày</label>
                <input type="date" name="to_date" class="form-control"
                       value="{{ request('to_date') }}">
            </div>

            <!-- Nút lọc -->
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100 fw-bold">
                    <i class="fas fa-search"></i> Lọc
                </button>
            </div>

        </form>
    </div>
    <!-- ======================== END SEARCH BAR ======================== -->


    @foreach ($danhGias as $danhGia)

        <div class="card shadow-sm border-0 mb-4 review-card">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <!-- Thông tin người dùng -->
                    <div class="d-flex">
                        <img src="https://ui-avatars.com/api/?name={{ $danhGia->user->name }}"
                             class="rounded-circle me-3" width="55">

                        <div>
                            <h5 class="fw-bold mb-1">{{ $danhGia->user->name }}</h5>
                            <div class="text-warning fw-bold">⭐ {{ $danhGia->rating }}/5</div>
                            <p class="text-muted fst-italic mt-2">"{{ $danhGia->noi_dung }}"</p>
                            <small class="text-secondary">
                                {{ $danhGia->created_at->format('d/m/Y H:i') }}
                            </small>
                        </div>
                    </div>

                    <!-- Nút mở form trả lời -->
                    <button class="btn btn-sm btn-outline-primary reply-btn-hover"
                        onclick="toggleReplyForm({{ $danhGia->id }})">
                        <i class="fas fa-reply"></i>
                    </button>

                </div>

                <!-- Danh sách phản hồi -->
                @if($danhGia->replies->count() > 0)
                    <button class="btn btn-outline-secondary btn-sm mt-3"
                        onclick="document.getElementById('reply-list-{{ $danhGia->id }}').classList.toggle('d-none')">
                        <i class="fas fa-comments"></i>
                        Xem {{ $danhGia->replies->count() }} phản hồi
                    </button>
                @endif

                <div id="reply-list-{{ $danhGia->id }}" class="d-none mt-3">
                    @foreach($danhGia->replies as $rep)
                        <div class="reply-item">
                            <strong>Admin:</strong>
                            <p class="mb-1">{{ $rep->noi_dung }}</p>
                            <small class="text-muted">{{ $rep->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                    @endforeach
                </div>

                <!-- Form trả lời -->
                <div id="reply-form-{{ $danhGia->id }}" class="reply-form mt-3 border-top pt-3">

                    <h6 class="fw-bold mb-2">Trả lời người dùng</h6>

                    <form action="{{ route('admin.danhgia.reply', $danhGia->id) }}" method="POST">
                        @csrf

                        <textarea name="noi_dung" class="form-control mb-3"
                            rows="3" placeholder="Nhập câu trả lời..." required></textarea>

                        <button class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane"></i> Gửi
                        </button>
                    </form>
                </div>

            </div>
        </div>

    @endforeach

    <div class="d-flex justify-content-end mt-2">
        {{ $danhGias->appends(request()->all())->links() }}
    </div>

</div>

<script>
    function toggleReplyForm(id) {
        let form = document.getElementById('reply-form-' + id);
        form.style.display = (form.style.display === 'block') ? 'none' : 'block';
    }
</script>

@endsection
