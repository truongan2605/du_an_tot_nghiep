@extends('layouts.auth')

@section('title', 'Sign-up')

@section('content')
    <section class="vh-xxl-100" style="margin-bottom: 50px">
        <div class="container h-100 d-flex px-0 px-sm-4">
            <div class="row justify-content-center align-items-center m-auto">
                <div class="col-12">
                    <div class="bg-mode shadow rounded-3 overflow-hidden">
                        <div class="row g-0">
                            <div class="col-lg-6 d-md-flex align-items-center order-2 order-lg-1">
                                <div class="p-3 p-lg-5">
                                    <img src="{{ asset('template/stackbros/assets/images/element/signin.svg') }}"
                                        alt="">
                                </div>
                                <div class="vr opacity-1 d-none d-lg-block"></div>
                            </div>

                            <div class="col-lg-6 order-1">
                                <div class="p-4 p-sm-6">
                                    <a href="{{ url('/') }}">
                                        <img class="h-50px mb-4"
                                            src="{{ asset('template/stackbros/assets/images/logo-icon.svg') }}"
                                            alt="logo">
                                    </a>
                                    <h1 class="mb-2 h3">Create new account</h1>
                                    <p class="mb-0">Already a member? <a href="{{ route('login') }}">Log in</a></p>

                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $err)
                                                    <li>{{ $err }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form class="mt-4 text-start" method="POST" action="{{ route('register') }}"
                                        id="registerForm">
                                        @csrf

                                        <!-- Name -->
                                        <div class="mb-3">
                                            <label class="form-label">Full name</label>
                                            <input type="text" name="name" value="{{ old('name') }}"
                                                class="form-control @error('name') is-invalid @enderror" required autofocus>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Email -->
                                        <div class="mb-3">
                                            <label class="form-label">Enter email id</label>
                                            <input type="email" name="email" value="{{ old('email') }}"
                                                class="form-control @error('email') is-invalid @enderror" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Password -->
                                        <div class="mb-3 position-relative">
                                            <label class="form-label">Enter password</label>
                                            <input name="password" class="form-control fakepassword" type="password"
                                                required>
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Confirm Password -->
                                        <div class="mb-3">
                                            <label class="form-label">Confirm Password</label>
                                            <input type="password" name="password_confirmation" class="form-control"
                                                required>
                                        </div>

                                        <!-- Button -->
                                        <div><button type="submit" class="btn btn-primary w-100 mb-0">Sign up</button>
                                        </div>
                                    </form>

                                    <script>
                                        document.getElementById('registerForm')?.addEventListener('submit', function() {
                                            console.log('register form submit fired');
                                        });
                                    </script>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
