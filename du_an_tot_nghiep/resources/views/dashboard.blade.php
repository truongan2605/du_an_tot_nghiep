@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container py-5">
    <div class="card">
        <div class="card-body">
            <h3>Dashboard</h3>
            <p>Bạn đã đăng nhập: <strong>{{ auth()->check() ? auth()->user()->name : 'không xác định' }}</strong></p>

            <p>
                <a href="{{ url('/tien-nghi') }}" class="btn btn-primary">Vào trang quản trị /tien-nghi</a>
            </p>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Logout</button>
            </form>
        </div>
    </div>
</div>
@endsection
