@extends('layouts.app')

@section('content')
<div class="container py-5">

    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <h3 class="fw-bold text-primary mb-3 mb-md-0">
            <i class="fa-solid fa-ticket me-2"></i> Voucher của tôi
        </h3>

        {{-- Filter & Search --}}
        <form method="GET" action="{{ route('client.vouchers.my') }}" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fa-solid fa-magnifying-glass text-secondary"></i>
                </span>
                <input type="text" name="search" class="form-control border-start-0"
                       placeholder="Tìm theo mã hoặc tên voucher..."
                       value="{{ request('search') }}">
            </div>

            <select name="filter" class="form-select w-auto">
                <option value="">Tất cả</option>
                <option value="valid" {{ request('filter') === 'valid' ? 'selected' : '' }}>Còn hiệu lực</option>
                <option value="expired" {{ request('filter') === 'expired' ? 'selected' : '' }}>Hết hạn</option>
            </select>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-filter me-1"></i> Lọc
            </button>
        </form>
    </div>

    {{-- Nội dung --}}
    @if ($vouchers->isEmpty())
        <div class="text-center py-5">
            <img src="{{ asset('images/empty-voucher.svg') }}" alt="Empty" width="180" class="mb-3">
            <h5 class="text-muted">Bạn chưa nhận mã giảm giá nào.</h5>
            <a href="{{ route('client.vouchers.index') }}" class="btn btn-outline-primary mt-3">
                <i class="fa-solid fa-gift me-1"></i> Nhận mã ngay
            </a>
        </div>
    @else
        <div class="row">
            @foreach ($vouchers as $voucher)
                @php
                    $isExpired = \Carbon\Carbon::parse($voucher->end_date)->isPast();
                    $isNotStarted = \Carbon\Carbon::parse($voucher->start_date)->isFuture();
                @endphp

                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card border-0 shadow-sm h-100 voucher-card position-relative {{ $isExpired ? 'opacity-75' : '' }}">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="fw-bold text-primary mb-0">
                                        <i class="fa-solid fa-ticket me-1"></i> {{ $voucher->name }}
                                        <p class="small text-muted mb-2">Mã: {{ $voucher->code  }}</p>
                                    </h5>
                                    <span class="badge
                                        {{ $isExpired ? 'bg-secondary' : ($isNotStarted ? 'bg-warning text-dark' : 'bg-success') }}">
                                        {{ $isExpired ? 'Hết hạn' : ($isNotStarted ? 'Chưa bắt đầu' : 'Còn hiệu lực') }}
                                    </span>
                                </div>

                                <p class="mb-1 text-muted small">
                                    @if ($voucher->type == 'phan_tram' || $voucher->type == 'percent')
                                        <strong>Giảm:</strong> {{ $voucher->value }}%
                                    @else
                                        <strong>Giảm:</strong> {{ number_format($voucher->value, 0, ',', '.') }}đ
                                    @endif
                                </p>

                                <p class="mb-1 text-muted small">
                                    <i class="fa-regular fa-calendar-days me-1"></i>
                                    {{ \Carbon\Carbon::parse($voucher->start_date)->format('d/m/Y') }} –
                                    {{ \Carbon\Carbon::parse($voucher->end_date)->format('d/m/Y') }}
                                </p>

                                @if (!empty($voucher->note))
                                    <p class="small text-muted fst-italic mt-2">{{ $voucher->note }}</p>
                                @endif
                            </div>

                            <div class="border-top mt-3 pt-2">
                                <p class="small text-muted mb-0">
                                    <i class="fa-regular fa-clock me-1"></i>
                                    <strong>Nhận lúc:</strong>
                                    {{ \Carbon\Carbon::parse($voucher->pivot->claimed_at)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
.voucher-card {
    border-radius: 1rem;
    overflow: hidden;
    transition: all 0.25s ease;
    background: #fff;
}
.voucher-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08);
}
.voucher-card .badge {
    font-size: 0.75rem;
}
</style>
@endsection
