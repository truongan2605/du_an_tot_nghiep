@extends('layouts.app')

@section('title', 'Ưu đãi khách hàng thân thiết')

@section('content')
<section class="pt-3">
    <div class="container">
        <div class="row">

            <!-- Sidebar START - copy từ profile.blade.php -->
            <div class="col-lg-4 col-xl-3">
                <div class="offcanvas-lg offcanvas-end" tabindex="-1" id="offcanvasSidebar">
                    <div class="offcanvas-header justify-content-end pb-2">
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                            aria-label="Close"></button>
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
                                        {{-- Trang hiện tại: Ưu đãi --}}
                                        <a class="nav-link active" href="{{ route('account.rewards') }}">
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
                    <h3 class="mb-4">Ưu đãi khách hàng thân thiết</h3>

                    {{-- Hạng hiện tại --}}
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-2">Hạng hiện tại: {{ $currentLevel }}</h5>
                            <p class="text-muted mb-0">
                                Tổng chi tiêu:
                                <strong>{{ number_format($totalSpent, 0, ',', '.') }}đ</strong>
                            </p>
                        </div>
                    </div>

                    {{-- Tiến độ lên hạng tiếp theo --}}
                    @if($nextLevelInfo['name'])
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold">
                                    Tiến độ lên hạng tiếp theo ({{ $nextLevelInfo['name'] }}):
                                </h6>

                                <div class="progress mb-2" style="height: 20px;">
                                    <div class="progress-bar bg-warning"
                                         role="progressbar"
                                         style="width: {{ $progressPercent }}%;"
                                         aria-valuenow="{{ $progressPercent }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                        {{ number_format($progressPercent, 1) }}%
                                    </div>
                                </div>

                                <p class="text-muted mb-0">
                                    Bạn cần tiêu thêm
                                    <strong>{{ number_format($nextLevelInfo['remaining'], 0, ',', '.') }}đ</strong>
                                    để lên hạng {{ $nextLevelInfo['name'] }}.
                                </p>
                                <small class="text-muted">
                                    Hiện tại:
                                    {{ number_format($nextLevelInfo['current'], 0, ',', '.') }}đ /
                                    {{ number_format($nextLevelInfo['required'], 0, ',', '.') }}đ
                                </small>
                            </div>
                        </div>
                    @else
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="alert alert-success mb-0">
                                    <h6 class="fw-bold mb-2">Bạn đã đạt hạng cao nhất!</h6>
                                    <p class="mb-0">
                                        Bạn đang ở hạng Kim Cương và được nhận những ưu đãi đặc biệt như vouchers miễn phí
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Các mức hạng --}}
                    <div class="card">
                        <div class="card-body">
                            <h6 class="fw-bold">Các mức hạng</h6>

                            <ul class="list-group">
                                <li class="list-group-item {{ $currentLevel == 'Đồng' ? 'active' : '' }}">
                                    <strong>Đồng</strong> 
                                </li>
                                <li class="list-group-item {{ $currentLevel == 'Bạc' ? 'active' : '' }}">
                                    <strong>Bạc</strong> 
                                    (tiêu ≥ 1.000.000đ trong 1 đơn hoàn thành hoặc tổng chi tiêu ≥ 1.000.000đ)
                                </li>
                                <li class="list-group-item {{ $currentLevel == 'Vàng' ? 'active' : '' }}">
                                    <strong>Vàng</strong>(tổng chi tiêu ≥ 15.000.000đ)
                                </li>
                                <li class="list-group-item {{ $currentLevel == 'Kim Cương' ? 'active' : '' }}">
                                    <strong>Kim Cương</strong> 
                                    (tổng chi tiêu ≥ 50.000.000đ)
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Main content END -->

        </div>
    </div>
</section>
@endsection
