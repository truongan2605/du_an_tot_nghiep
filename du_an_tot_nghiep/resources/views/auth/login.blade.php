@extends('layouts.auth')

@section('title', 'Đăng nhập')

@section('content')
    <section class="vh-xxl-100">
        <div class="container h-100 d-flex px-0 px-sm-4">
            <div class="row justify-content-center align-items-center m-auto">
                <div class="col-12">
                    <div class="bg-mode shadow rounded-3 overflow-hidden">
                        <div class="row g-0">
                            <!-- Vector Image -->
                            <div class="col-lg-6 d-flex align-items-center order-2 order-lg-1">
                                <div class="p-3 p-lg-5">
                                    <img src="{{ asset('template/stackbros/assets/images/element/signin.svg') }}"
                                        alt="">
                                </div>
                                <div class="vr opacity-1 d-none d-lg-block"></div>
                            </div>

                            <!-- Information -->
                            <div class="col-lg-6 order-1">
                                <div class="p-4 p-sm-7">
                                    <a href="{{ url('/') }}">
                                        <img class="h-50px mb-4"
                                            src="{{ asset('template/stackbros/assets/images/logo-icon.svg') }}"
                                            alt="logo">
                                    </a>
                                    <h1 class="mb-2 h3">Chào mừng trở lại</h1>
                                    <p class="mb-0">Chưa có tài khoản? <a href="{{ route('register') }}">Tạo tài khoản</a></p>

                                    <!-- Form START -->
                                    <form class="mt-4 text-start" method="POST" action="{{ route('login') }}">
                                        @csrf

                                        <!-- Hiển thị lỗi từ session (khi logout từ toggle) -->
                                        @if (session('error'))
                                            <div class="alert alert-danger mb-3">
                                                {{ session('error') }}
                                            </div>
                                        @endif

                                        <!-- Email -->
                                        <div class="mb-3">
                                            <label class="form-label">Nhập email</label>
                                            <input type="email" name="email" value="{{ old('email') }}"
                                                class="form-control @error('email') is-invalid @enderror" required
                                                autofocus>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Password -->
                                        <div class="mb-3 position-relative">
                                            <label class="form-label">Nhập mật khẩu</label>
                                            <input name="password" class="form-control fakepassword" type="password"
                                                id="psw-input" required>
                                            <span class="position-absolute top-50 end-0 translate-middle-y p-0 mt-3">
                                                <i class="fakepasswordicon fas fa-eye-slash cursor-pointer p-2"></i>
                                            </span>
                                        </div>

                                        <!-- Remember me -->
                                        <div class="mb-3 d-sm-flex justify-content-between">
                                            <div>
                                                <input type="checkbox" name="remember" class="form-check-input"
                                                    id="rememberCheck" {{ old('remember') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="rememberCheck">Ghi nhớ đăng nhập?</label>
                                            </div>
                                            <a href="{{ route('password.request') }}">Quên mật khẩu?</a>
                                        </div>

                                        <!-- Button -->
                                        <div><button type="submit" class="btn btn-primary w-100 mb-0">Đăng nhập</button></div>

                                        <div class="position-relative my-4">
                                            <hr>
                                            <p
                                                class="small bg-mode position-absolute top-50 start-50 translate-middle px-2">
                                                Hoặc đăng nhập bằng</p>
                                        </div>

                                        <!-- Google and facebook button -->
                                        <div class="vstack gap-3" >
                                            <a href="{{ route('auth.google') }}" class="btn btn-light mb-0">
                                                <i class="fab fa-fw fa-google text-google-icon me-2"></i>Đăng nhập bằng Google
                                            </a>
                                        </div>
                                    </form>
                                    <!-- Form END -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
