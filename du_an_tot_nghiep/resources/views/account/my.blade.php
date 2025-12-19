@extends('layouts.app')

@section('title', 'Ví Voucher')

@section('content')
    <section class="pt-3">
        <div class="container">
            <div class="row">

                <!-- Sidebar START - copy từ rewards/profile -->
                <div class="col-lg-4 col-xl-3">
                    <div class="offcanvas-lg offcanvas-end" tabindex="-1" id="offcanvasSidebar">
                        <div class="offcanvas-header justify-content-end pb-2">
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>

                        <div class="offcanvas-body p-3 p-lg-0">
                            <div class="card bg-light w-100">
                                <div class="position-absolute top-0 end-0 p-3">
                                    <a href="{{ route('account.settings') }}" class="text-primary-hover"
                                        data-bs-toggle="tooltip" title="Chỉnh sửa hồ sơ">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </div>

                                <div class="card-body p-3">
                                    <div class="text-center mb-3">
                                        <div class="avatar avatar-xl mb-2">
                                            <img class="avatar-img rounded-circle border border-2 border-white"
                                                src="{{ auth()->user() && auth()->user()->avatar
                                                    ? asset('storage/' . auth()->user()->avatar)
                                                    : asset('template/stackbros/assets/images/avatar/avt.jpg') }}"
                                                alt="avatar">
                                        </div>
                                        <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                                        <a href="mailto:{{ auth()->user()->email }}"
                                            class="text-reset text-primary-hover small">
                                            {{ auth()->user()->email }}
                                        </a>
                                        <hr>
                                    </div>

                                    <ul class="nav nav-pills-primary-soft flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('account.settings') }}">
                                                <i class="bi bi-person fa-fw me-2"></i>Hồ sơ của tôi
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('account.rewards') }}">
                                                <i class="bi bi-gift fa-fw me-2"></i>Ưu đãi
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('account.booking.index') }}">
                                                <i class="bi bi-ticket-perforated fa-fw me-2"></i>Đặt phòng của tôi
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ url('/account/wishlist') }}">
                                                <i class="bi bi-heart fa-fw me-2"></i>Danh sách yêu thích
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link active" href="{{ route('client.vouchers.my') }}">
                                                <i class="fa-solid fa-wallet fa-fw me-2"></i>Ví Voucher
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit"
                                                    class="btn nav-link text-start text-danger bg-danger-soft-hover w-100">
                                                    <i class="fas fa-sign-out-alt fa-fw me-2"></i>Đăng xuất
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sidebar END -->

                <!-- Main content START -->
                <div class="col-lg-8 col-xl-9">
                    {{-- Nút mở sidebar trên mobile --}}
                    <div class="d-grid mb-0 d-lg-none w-100">
                        <button class="btn btn-primary mb-4" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                            <i class="fas fa-sliders-h"></i> Menu
                        </button>
                    </div>

                    <div class="container py-0 px-0">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                            <h3 class="mb-2 mb-md-0">Voucher của tôi</h3>

                            <form method="GET" action="{{ route('client.vouchers.my') }}" class="d-flex gap-2">
                                ...
                            </form>
                        </div>

                        @if (isset($currentPoints))
                            <div class="mb-3">
                                <div class="card">
                                    <div
                                        class="card-body d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                                        <div>
                                            <small class="text-muted">Số điểm hiện có</small>
                                            <div class="h5 mb-0">{{ number_format($currentPoints, 0, ',', '.') }} điểm
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif


                        {{-- Nội dung vouchers --}}
                        @if (
                            (method_exists($vouchers, 'total') && $vouchers->total() === 0) ||
                                (method_exists($vouchers, 'isEmpty') && $vouchers->isEmpty()))
                            <div class="text-center py-5">
                                <img src="{{ asset('images/empty-voucher.svg') }}" alt="Empty" width="180"
                                    class="mb-3">
                                <h5 class="text-muted">Bạn chưa nhận mã giảm giá nào.</h5>
                                <a href="{{ route('client.vouchers.index') }}" class="btn btn-outline-primary mt-3">
                                    <i class="fa-solid fa-gift me-1"></i> Nhận mã ngay
                                </a>
                            </div>
                        @else
                            <div class="row g-3 mb-3 align-items-center">
                                <div class="col-12 col-md-6">
                                    {{-- Summary: hiển thị đang show bao nhiêu --}}
                                    @if (method_exists($vouchers, 'total'))
                                        <p class="small text-muted mb-0">
                                            Hiển thị
                                            <strong>{{ $vouchers->firstItem() ?? 0 }} -
                                                {{ $vouchers->lastItem() ?? 0 }}</strong>
                                            trên tổng <strong>{{ $vouchers->total() }}</strong> voucher
                                        </p>
                                    @endif
                                </div>

                                <div class="col-6 col-md-3">
                                    {{-- Per-page selector --}}
                                    <form id="perPageForm" method="GET" action="{{ route('client.vouchers.my') }}"
                                        class="d-flex">
                                        {{-- preserve search param --}}
                                        @if (request('search'))
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                        @endif
                                        {{-- preserve other possible filters --}}
                                        @foreach (request()->except(['page', 'per_page', '_token']) as $k => $v)
                                            @if (is_array($v))
                                                @foreach ($v as $vv)
                                                    <input type="hidden" name="{{ $k }}[]"
                                                        value="{{ $vv }}">
                                                @endforeach
                                            @else
                                                <input type="hidden" name="{{ $k }}"
                                                    value="{{ $v }}">
                                            @endif
                                        @endforeach

                                        <label for="per_page"
                                            class="me-2 small text-muted d-none d-md-inline-block align-self-center">Số /
                                            trang</label>
                                        <select name="per_page" id="per_page" class="form-select form-select-sm"
                                            style="width: 110px;">
                                            @php $currentPer = request('per_page', 9); @endphp
                                            <option value="6" {{ $currentPer == 6 ? 'selected' : '' }}>6</option>
                                            <option value="9" {{ $currentPer == 9 ? 'selected' : '' }}>9</option>
                                            <option value="12" {{ $currentPer == 12 ? 'selected' : '' }}>12</option>
                                            <option value="18" {{ $currentPer == 18 ? 'selected' : '' }}>18</option>
                                            <option value="24" {{ $currentPer == 24 ? 'selected' : '' }}>24</option>
                                        </select>
                                    </form>
                                </div>

                            </div>

                            <div class="row">
                                @foreach ($vouchers as $voucher)
                                    @php
                                        $now = now();
                                        $start = $voucher->start_date
                                            ? \Carbon\Carbon::parse($voucher->start_date)
                                            : null;
                                        $end = $voucher->end_date ? \Carbon\Carbon::parse($voucher->end_date) : null;

                                        $usedCount = $usageCounts[$voucher->id] ?? 0;
                                        $usageLimit = $voucher->usage_limit_per_user;

                                        $status = 'active';

                                        if ($usageLimit && $usedCount >= $usageLimit) {
                                            $status = 'used_up';
                                        } elseif ($end && $end->lt($now)) {
                                            $status = 'expired';
                                        } elseif ($start && $start->gt($now)) {
                                            $status = 'not_started';
                                        }

                                        $badgeClass = match ($status) {
                                            'used_up' => 'bg-secondary',
                                            'expired' => 'bg-dark',
                                            'not_started' => 'bg-warning text-dark',
                                            default => 'bg-success',
                                        };
                                    @endphp

                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card border-0 shadow-sm h-100 voucher-card position-relative">
                                            <span class="badge voucher-badge {{ $badgeClass }}">
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

                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <div class="voucher-header mb-2">
                                                    <h5 class="fw-bold mb-1 voucher-title">
                                                        {{ $voucher->name }}
                                                    </h5>
                                                    <p class="small text-muted mb-1">Mã: <span
                                                            class="fw-semibold">{{ $voucher->code }}</span></p>

                                                    @if ($voucher->description)
                                                        <p class="small text-muted mb-1">
                                                            {{ Str::limit($voucher->description, 80) }}</p>
                                                    @endif
                                                </div>

                                                <div class="mt-2">
                                                    <p class="small text-muted mb-1">
                                                        Giá trị: <strong>{{ $voucher->gia_tri_hien_thi }}</strong>
                                                    </p>
                                                    <p class="small text-muted mb-1">
                                                        Thời gian:
                                                        @if ($start)
                                                            {{ $start->format('d/m/Y') }}
                                                        @else
                                                            -
                                                        @endif
                                                        @if ($end)
                                                            — {{ $end->format('d/m/Y') }}
                                                        @endif
                                                    </p>
                                                    <p class="small text-muted mb-0">
                                                        Đã dùng:
                                                        {{ $usedCount }}{{ $usageLimit ? '/' . $usageLimit : '' }} lần
                                                    </p>
                                                </div>

                                                <div class="mt-3 d-flex gap-2">
                                                    <button type="button"
                                                        class="btn btn-outline-secondary btn-sm copy-code"
                                                        data-code="{{ $voucher->code }}">
                                                        <i class="fa-regular fa-copy me-1"></i> Sao chép mã
                                                    </button>

                                                    @if ($status === 'active')
                                                        <a href="{{ route('home') }}"
                                                            class="btn btn-primary btn-sm ms-auto">
                                                            Dùng ngay
                                                        </a>
                                                    @else
                                                        <button class="btn btn-light btn-sm ms-auto" disabled>Không khả
                                                            dụng</button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Pagination --}}
                            @if (method_exists($vouchers, 'links'))
                                <div
                                    class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
                                    <div class="mb-2 mb-md-0 small text-muted">
                                        {{-- show current page info --}}
                                        Trang <strong>{{ $vouchers->currentPage() }}</strong> /
                                        <strong>{{ $vouchers->lastPage() }}</strong>
                                    </div>

                                    <div>
                                        {{ $vouchers->withQueryString()->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                <!-- Main content END -->

            </div>
        </div>
    </section>

    {{-- Styles riêng cho voucher --}}
    @push('styles')
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
                padding: .45rem .6rem;
            }
        </style>
    @endpush

    {{-- Script copy mã (tuỳ chọn) --}}
    @push('scripts')
        <script>
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.copy-code');
                if (!btn) return;
                const code = btn.getAttribute('data-code');
                if (!code) return;
                navigator.clipboard?.writeText(code).then(() => {
                    btn.classList.add('btn-success');
                    btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Đã sao chép';
                    setTimeout(() => {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-secondary');
                        btn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> Sao chép mã';
                    }, 1500);
                }).catch(() => {
                    alert('Không thể sao chép mã. Vui lòng sao chép thủ công.');
                });
            });

            document.addEventListener('change', function(e) {
                const sel = e.target;
                if (sel && sel.id === 'per_page') {
                    const form = document.getElementById('perPageForm');
                    if (!form) return;

                    const pageInput = form.querySelector('input[name="page"]');
                    if (pageInput) pageInput.remove();

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'page';
                    input.value = '1';
                    form.appendChild(input);

                    form.submit();
                }
            });
        </script>
    @endpush

    @push('styles')
        <style>
            .voucher-card {
                border-radius: 1rem;
                overflow: hidden;
                transition: all 0.25s ease;
                background: #fff;
                position: relative;
                padding-top: 0.75rem;
            }

            .voucher-card .voucher-badge {
                position: absolute;
                top: 0.7rem;
                right: 0.7rem;
                z-index: 5;
                font-size: 0.75rem;
                padding: .45rem .6rem;
                border-radius: 0.5rem;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            }

            .voucher-card .voucher-title {
                white-space: normal;
                word-break: break-word;
                overflow-wrap: anywhere;
                padding-right: 3.2rem;
            }

            .voucher-card .voucher-header {
                padding-top: 0.25rem;
                padding-bottom: 0.25rem;
            }
        </style>
    @endpush

@endsection
