@extends('layouts.auth')

@section('title', 'Forgot password')

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
                                    <h1 class="mb-2 h3">Forgot password?</h1>
                                    <p class="mb-sm-0">Enter the email address associated with an account.</p>

                                    @if (session('status'))
                                        <div class="alert alert-success">
                                            {{ session('status') }}
                                        </div>
                                    @endif

                                    <!-- Form START -->
                                    <form class="mt-sm-4 text-start" method="POST" action="{{ route('password.email') }}">
                                        @csrf

                                        <!-- Email -->
                                        <div class="mb-3">
                                            <label class="form-label">Enter email id</label>
                                            <input type="email" name="email" value="{{ old('email') }}"
                                                class="form-control @error('email') is-invalid @enderror" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3 text-center">
                                            <p>Back to <a href="{{ route('login') }}">Sign in</a></p>
                                        </div>

                                        <!-- Button -->
                                        <div class="d-grid"><button type="submit" class="btn btn-primary">Reset
                                                Password</button></div>

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
