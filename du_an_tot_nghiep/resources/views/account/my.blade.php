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
                    <input
                        type="text"
                        name="search"
                        class="form-control border-start-0"
                        placeholder="Tìm theo mã hoặc tên voucher..."
                        value="{{ request('search') }}"
                    >
                </div>

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
        $now  = now();
        $start = $voucher->start_date ? \Carbon\Carbon::parse($voucher->start_date) : null;
        $end   = $voucher->end_date ? \Carbon\Carbon::parse($voucher->end_date) : null;

        $usedCount  = $usageCounts[$voucher->id] ?? 0;
        $usageLimit = $voucher->usage_limit_per_user;

        $status = 'active';

        if ($usageLimit && $usedCount >= $usageLimit) {
            $status = 'used_up';           // đã dùng hết lượt
        } elseif ($end && $end->lt($now)) {
            $status = 'expired';           // hết hạn
        } elseif ($start && $start->gt($now)) {
            $status = 'not_started';       // chưa bắt đầu
        }

        $badgeClass = match ($status) {
            'used_up'     => 'bg-secondary',
            'expired'     => 'bg-dark',
            'not_started' => 'bg-warning text-dark',
            default       => 'bg-success',
        };
    @endphp

    <div class="col-md-4 col-lg-3 mb-4">
        <div class="card border-0 shadow-sm h-100 voucher-card position-relative">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold text-primary mb-0">
                        <i class="fa-solid fa-ticket me-1"></i> {{ $voucher->name }}
                        <p class="small text-muted mb-2">Mã: {{ $voucher->code }}</p>
                    </h5>

                    <span class="badge {{ $badgeClass }}">
                        @if ($status === 'used_up')
                            Hết lượt
                        @elseif ($status === 'expired')
                            Hết hạn
                        @elseif ($status === 'not_started')
                            Chưa bắt đầu
                        @else
                            Còn hiệu lực
                        @endif
                    </span>
                </div>

                {{-- phần nội dung còn lại giữ nguyên --}}
                <p class="small text-muted mt-1">
                    Đã dùng: {{ $usedCount }}{{ $usageLimit ? '/' . $usageLimit : '' }} lần
                </p>
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
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }

        .voucher-card .badge {
            font-size: 0.75rem;
        }
    </style>
@endsection
