@extends('layouts.auth')

@section('title', 'Sign-up')

@section('content')
    <section class="vh-xxl-100">
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

                            <!-- Form -->
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
                                        <div class="mb-3">
                                            <label class="form-label">Enter password</label>
                                            <div class="input-group">
                                                <input id="password" name="password"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    type="password" required>
                                                <button type="button" class="btn btn-outline-secondary"
                                                    id="togglePassword">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Confirm Password -->
                                        <div class="mb-3">
                                            <label class="form-label">Confirm Password</label>
                                            <div class="input-group">
                                                <input id="password_confirmation" type="password"
                                                    name="password_confirmation" class="form-control" required>
                                                <button type="button" class="btn btn-outline-secondary"
                                                    id="toggleConfirmPassword">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Button -->
                                        <div>
                                            <button type="submit" class="btn btn-primary w-100 mb-0">Sign up</button>
                                        </div>

                                        <div class="position-relative my-4">
                                            <hr>
                                            <p
                                                class="small bg-mode position-absolute top-50 start-50 translate-middle px-2">
                                                Or sign in with</p>
                                        </div>

                                        <!-- Google and facebook button -->
                                        <div class="vstack gap-3">
                                            <a href="{{ route('auth.google') }}" class="btn btn-light mb-0">
                                                <i class="fab fa-fw fa-google text-google-icon me-2"></i>Sign in with Google
                                            </a>
                                        </div>
                                    </form>

                                    <!-- Script -->
                                    <script>
                                        function togglePassword(inputId, buttonId) {
                                            const input = document.getElementById(inputId);
                                            const button = document.getElementById(buttonId);
                                            const icon = button.querySelector("i");

                                            button.addEventListener('click', function() {
                                                if (input.type === "password") {
                                                    input.type = "text";
                                                    icon.classList.remove("bi-eye");
                                                    icon.classList.add("bi-eye-slash");
                                                } else {
                                                    input.type = "password";
                                                    icon.classList.remove("bi-eye-slash");
                                                    icon.classList.add("bi-eye");
                                                }
                                            });
                                        }

                                        togglePassword("password", "togglePassword");
                                        togglePassword("password_confirmation", "toggleConfirmPassword");
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
