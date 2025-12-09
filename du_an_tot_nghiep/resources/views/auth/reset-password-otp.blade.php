@extends('layouts.auth')

@section('title', 'Đặt lại mật khẩu bằng mã')

@section('content')
    <div class="auth-card">
        <div class="bg-mode rounded-3 overflow-hidden p-4 p-sm-5">
            <div class="text-center mb-3">
                <a href="{{ url('/') }}">
                    <img class="mb-3 h-50px" src="{{ asset('template/stackbros/assets/images/logo-icon.svg') }}"
                        alt="logo">
                </a>
                <h1 class="mb-2 h4">Nhập mã xác thực</h1>
                <p class="mb-4 small text-muted">
                    Mã 6 số đã được gửi tới email của bạn. Vui lòng nhập mã và mật khẩu mới.
                </p>
            </div>

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="mt-2 text-start" method="POST" action="{{ route('password.otp.reset') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-3">
                    <label class="form-label small">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                </div>

                {{-- Mã OTP --}}
                <div class="mb-3">
                    <label class="form-label small">Mã xác thực (6 số)</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control" maxlength="6"
                        required>
                </div>

                {{-- Mật khẩu mới --}}
                <div class="mb-3">
                    <label class="form-label small">Mật khẩu mới</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                {{-- Xác nhận mật khẩu --}}
                <div class="mb-3">
                    <label class="form-label small">Nhập lại mật khẩu mới</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('password.request') }}" class="text-muted small">Gửi lại mã</a>
                    <button type="submit" class="btn btn-primary">Đặt lại mật khẩu</button>
                </div>
            </form>
        </div>
    </div>
@endsection





