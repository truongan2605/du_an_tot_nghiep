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
                            <option value="expired" {{ request('filter') == 'expired' ? 'selected' : '' }}>Hết hạn</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill mt-2">Lọc</button>
                </form>
            </div>
        </aside>

        {{-- ===== MAIN CONTENT ===== --}}
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Danh sách Voucher</h3>
            </div>

            @if ($vouchers->count() > 0)
                <div class="row g-4">
                    @foreach ($vouchers as $voucher)
                        @php
                            $isClaimed = in_array($voucher->id, $claimedIds ?? []);
                            $isExpired = \Carbon\Carbon::parse($voucher->end_date)->isPast();
                        @endphp

                        <div class="col-md-6 col-xl-4">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <h5 class="fw-bold mb-2 text-primary">{{ $voucher->name }}</h5>
                                        <p class="small text-muted mb-2">Mã: {{ $voucher->code  }}</p>
                                        <p class="mb-2">
                                            <strong>Giảm:</strong>
                                            @if($voucher->type == 'percent')
                                                {{ $voucher->value }}%
                                            @else
                                                {{ number_format($voucher->value, 0, ',', '.') }}đ
                                            @endif
                                        </p>
                                        <p class="small mb-0">
                                            <i class="bi bi-clock-history me-1"></i>
                                            Hiệu lực: {{ \Carbon\Carbon::parse($voucher->start_date)->format('d/m/Y') }}
                                            - {{ \Carbon\Carbon::parse($voucher->end_date)->format('d/m/Y') }}
                                        </p>
                                    </div>

                                    <div class="mt-3">
                                        @if($isExpired)
                                            <button class="btn btn-secondary w-100 rounded-pill" disabled>Hết hạn</button>
                                        @elseif($isClaimed)
                                            <button class="btn btn-outline-success w-100 rounded-pill" disabled>Đã nhận</button>
                                        @else
                                            <button class="btn btn-primary w-100 rounded-pill btn-claim"
                                                    data-id="{{ $voucher->id }}">Nhận mã</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <img src="{{ asset('template/stackbros/assets/images/no-data.svg') }}" width="180" alt="No vouchers">
                    <p class="mt-3 mb-0">Không có voucher nào.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- SweetAlert2 (hiển thị thông báo đẹp) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.btn-claim');

            buttons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const button = this;

                    button.disabled = true;
                    button.textContent = 'Đang xử lý...';

                    fetch(`/vouchers/claim/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            });

                            button.classList.remove('btn-primary');
                            button.classList.add('btn-outline-success');
                            button.textContent = 'Đã nhận';
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Thông báo',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            button.disabled = false;
                            button.textContent = 'Nhận mã';
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
                        button.textContent = 'Nhận mã';
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
