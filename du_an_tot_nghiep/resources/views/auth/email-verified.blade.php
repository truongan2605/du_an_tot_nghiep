@extends('layouts.auth')

@section('title', 'Xác thực email thành công')

@section('content')
    <section class="vh-xxl-100">
        <div class="container h-100 d-flex px-0 px-sm-4">
            <div class="row justify-content-center align-items-center m-auto">
                <div class="col-12">
                    <div class="bg-mode shadow rounded-3 overflow-hidden">
                        <div class="row g-0">
                            <div class="col-lg-6 d-md-flex align-items-center order-2 order-lg-1">
                                <div class="p-3 p-lg-5">
                                    <img src="{{ asset('template/stackbros/assets/images/element/forgot-pass.svg') }}"
                                        alt="">
                                </div>
                                <div class="vr opacity-1 d-none d-lg-block"></div>
                            </div>

                            <div class="col-lg-6 order-1">
                                <div class="p-4 p-sm-7">
                                    <a href="{{ url('/') }}">
                                        <img class="mb-4 h-50px"
                                            src="{{ asset('template/stackbros/assets/images/logo-icon.svg') }}"
                                            alt="logo">
                                    </a>
                                    
                                    <div class="text-center mb-4">
                                        @if(isset($error) && $error)
                                            <div class="mb-3">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" 
                                                     style="width: 80px; height: 80px;">
                                                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>
                                                </div>
                                            </div>
                                            <h1 class="mb-2 h3">Xác thực email thất bại</h1>
                                            <p class="text-muted mb-0">
                                                {{ $message ?? 'Link xác nhận không hợp lệ hoặc đã hết hạn.' }}
                                            </p>
                                        @elseif(isset($alreadyVerified) && $alreadyVerified)
                                            <div class="mb-3">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10" 
                                                     style="width: 80px; height: 80px;">
                                                    <i class="bi bi-info-circle-fill text-info" style="font-size: 3rem;"></i>
                                                </div>
                                            </div>
                                            <h1 class="mb-2 h3">Email đã được xác nhận</h1>
                                            <p class="text-muted mb-0">
                                                Email của bạn đã được xác nhận trước đó. Bạn có thể đăng nhập ngay bây giờ.
                                            </p>
                                        @else
                                            <div class="mb-3">
                                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10" 
                                                     style="width: 80px; height: 80px;">
                                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                                </div>
                                            </div>
                                            <h1 class="mb-2 h3">Xác thực email thành công!</h1>
                                            <p class="text-muted mb-0">
                                                @if(isset($userName))
                                                    Chào {{ $userName }}, 
                                                @endif
                                                Email của bạn đã được xác nhận. Tài khoản đã được kích hoạt và sẵn sàng sử dụng.
                                            </p>
                                        @endif
                                    </div>

                                    @if(isset($error) && $error)
                                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                            <div>
                                                <strong>Lỗi!</strong> {{ $message ?? 'Vui lòng yêu cầu gửi lại email xác nhận.' }}
                                            </div>
                                        </div>
                                    @elseif(isset($alreadyVerified) && $alreadyVerified)
                                        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            <div>
                                                <strong>Thông tin:</strong> Email này đã được xác nhận trước đó.
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            <div>
                                                <strong>Thành công!</strong> Bạn có thể đăng nhập ngay bây giờ.
                                            </div>
                                        </div>
                                    @endif

                                    <div class="d-grid gap-2">
                                        <a href="{{ route('login') }}" class="btn btn-primary">
                                            <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập ngay
                                        </a>
                                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                                            <i class="bi bi-house-door me-2"></i>Về trang chủ
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

