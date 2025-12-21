@extends('layouts.app')

@section('title', 'Danh sách Voucher')

@section('content')
    <div class="container py-5">
        <div class="row g-4">

            {{-- ===== SIDEBAR FILTER ===== --}}
            <aside class="col-lg-3">
                <div class="card shadow-sm border-0 rounded-4 p-3">
                    <h5 class="fw-bold mb-3">Bộ lọc</h5>

                    <form method="GET" action="{{ route('client.vouchers.index') }}">
                        {{-- Tìm kiếm --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tìm kiếm voucher</label>
                            <input type="text" name="search" class="form-control" placeholder="Nhập mã hoặc tên voucher"
                                value="{{ request('search') }}">
                        </div>

                        {{-- Trạng thái --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Trạng thái</label>
                            <select name="filter" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="valid" {{ request('filter') == 'valid' ? 'selected' : '' }}>Còn hạn</option>
                                <option value="expired" {{ request('filter') == 'expired' ? 'selected' : '' }}>Hết hạn
                                </option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill mt-2">Lọc</button>
                    </form>

                    {{-- Hiển thị điểm user (nếu có) --}}
                    @if (isset($currentPoints))
                        <hr class="my-3">
                        <div class="small text-muted">Số điểm hiện có</div>
                        <div class="h4 fw-bold" id="current-points">{{ number_format($currentPoints, 0, ',', '.') }}</div>
                    @endif
                </div>
            </aside>

            {{-- ===== MAIN CONTENT ===== --}}
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Danh sách Voucher</h3>

    @auth
        <a href="{{ route('client.vouchers.my') }}" class="btn btn-outline-primary rounded-pill">
            Ví Voucher
        </a>
    @else
        <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill">
            Ví Voucher
        </a>
    @endauth
</div>


                @if ($vouchers->count() > 0)
                    <div class="row g-4">
                        @foreach ($vouchers as $voucher)
                            @php
                                $isClaimed = in_array($voucher->id, $claimedIds ?? []);
                                $isExpired = $voucher->end_date
                                    ? \Carbon\Carbon::parse($voucher->end_date)->isPast()
                                    : false;

                                // nhãn giá để hiển thị trên nút
                                if (($voucher->type ?? '') == 'percent' || ($voucher->type ?? '') == 'phan_tram') {
                                    $valueLabel = ($voucher->value ?? 0) . '%';
                                } else {
                                    $valueLabel = isset($voucher->value)
                                        ? number_format($voucher->value, 0, ',', '.') . 'đ'
                                        : '-';
                                }

                                $pointsRequired = $voucher->points_required ?? 0;
                            @endphp

                            <div class="col-md-6 col-xl-4">
                                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                                        <div>
                                            <h5 class="fw-bold mb-2 text-primary">{{ $voucher->name }}</h5>
                                            <p class="small text-muted mb-2">Mã: {{ $voucher->code }}</p>
                                            <p class="mb-2">
                                                <strong>Giảm:</strong> {{ $valueLabel }}
                                            </p>
                                            <p class="small mb-0">
                                                <i class="bi bi-clock-history me-1"></i>
                                                Hiệu lực:
                                                @if ($voucher->start_date)
                                                    {{ \Carbon\Carbon::parse($voucher->start_date)->format('d/m/Y') }}
                                                @else
                                                    -
                                                @endif
                                                -
                                                @if ($voucher->end_date)
                                                    {{ \Carbon\Carbon::parse($voucher->end_date)->format('d/m/Y') }}
                                                @else
                                                    -
                                                @endif
                                            </p>
                                        </div>

                                        <div class="mt-3">
                                            @if ($isExpired)
                                                <button class="btn btn-secondary w-100 rounded-pill" disabled>Hết
                                                    hạn</button>
                                            @elseif($isClaimed)
                                                <button class="btn btn-outline-success w-100 rounded-pill" disabled>Đã
                                                    nhận</button>
                                            @else
                                                @if ($pointsRequired && $pointsRequired > 0)
                                                    <button class="btn btn-primary w-100 rounded-pill btn-claim"
                                                        data-id="{{ $voucher->id }}" data-points="{{ $pointsRequired }}">
                                                        Đổi voucher — {{ number_format($pointsRequired, 0, ',', '.') }} điểm
                                                    </button>
                                                @else
                                                    <button class="btn btn-primary w-100 rounded-pill btn-claim"
                                                        data-id="{{ $voucher->id }}">
                                                        Nhận mã — {{ $valueLabel }}
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <img src="{{ asset('template/stackbros/assets/images/no-data.svg') }}" width="180"
                            alt="No vouchers">
                        <p class="mt-3 mb-0">Không có voucher nào.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn-claim');

            buttons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const pointsNeeded = this.dataset.points ? parseInt(this.dataset.points, 10) :
                    0;
                    const button = this;

                    // Nếu user chưa đăng nhập, backend sẽ trả 401 — xử lý tiếp theo
                    button.disabled = true;
                    button.textContent = 'Đang xử lý...';

                    fetch(`/vouchers/claim/${id}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({})
                        })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            if (res.status === 401) {
                                // chưa đăng nhập
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Bạn chưa đăng nhập',
                                    text: 'Vui lòng đăng nhập để nhận voucher.',
                                }).then(() => {
                                    window.location.href = '{{ route('login') }}';
                                });
                                return;
                            }

                            if (data && data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Thành công!',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });

                                // cập nhật button UI
                                button.classList.remove('btn-primary');
                                button.classList.add('btn-outline-success');
                                button.textContent = pointsNeeded ? 'Đã đổi' : 'Đã nhận';
                                button.disabled = true;

                                // cập nhật số điểm hiển thị (nếu backend trả)
                                if (data.currentPoints !== undefined && document
                                    .getElementById('current-points')) {
                                    document.getElementById('current-points').textContent =
                                        new Intl.NumberFormat('vi-VN').format(data
                                            .currentPoints);
                                }
                            } else {
                                // lỗi (không đủ điểm, đã nhận, hết lượt, v.v.)
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Thông báo',
                                    text: data.message || 'Không thể nhận voucher.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });

                                // re-enable button
                                button.disabled = false;
                                // reset text (nếu voucher yêu cầu điểm hiển thị label khác)
                                if (pointsNeeded && pointsNeeded > 0) {
                                    button.textContent = 'Đổi voucher — ' + new Intl
                                        .NumberFormat('vi-VN').format(pointsNeeded) +
                                        ' điểm';
                                } else {
                                    // lấy value label hiển thị ban đầu từ attribute data-value (nếu cần)
                                    // fallback:
                                    button.textContent = 'Nhận mã';
                                }
                            }
                        })
                        .catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: 'Không thể nhận voucher, vui lòng thử lại.',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            button.disabled = false;
                            if (pointsNeeded && pointsNeeded > 0) {
                                button.textContent = 'Đổi voucher — ' + new Intl.NumberFormat(
                                    'vi-VN').format(pointsNeeded) + ' điểm';
                            } else {
                                button.textContent = 'Nhận mã';
                            }
                        });
                });
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        .card:hover {
            transform: translateY(-5px);
            transition: 0.25s ease;
        }
    </style>
@endpush
