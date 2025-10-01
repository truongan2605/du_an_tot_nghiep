@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
    <div class="auth-card">
        <div class="bg-mode rounded-3 overflow-hidden p-4 p-sm-5">
            <div class="text-center mb-3">
                <a href="{{ url('/') }}">
                    <img class="mb-3 h-50px" src="{{ asset('template/stackbros/assets/images/logo-icon.svg') }}" alt="logo">
                </a>
                <h1 class="mb-2 h4">Reset your password</h1>
                <p class="mb-4 small text-muted">Enter a new password for your account.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="mt-2 text-start" method="POST" action="{{ route('password.store') }}">
                @csrf

                {{-- token --}}
                <input type="hidden" name="token" value="{{ $token ?? (request()->route('token') ?? request('token')) }}">

                {{-- Email --}}
                <div class="mb-3">
                    <label class="form-label small">Email address</label>
                    <input type="email" name="email" value="{{ old('email', request('email')) }}" class="form-control" required>
                </div>

                {{-- New password --}}
                <div class="mb-3">
                    <label class="form-label small">New password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                {{-- Confirm --}}
                <div class="mb-3">
                    <label class="form-label small">Confirm new password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('login') }}" class="text-muted small">Back to Sign in</a>
                    <button type="submit" class="btn btn-primary">Set new password</button>
                </div>
            </form>
        </div>
    </div>
@endsection
