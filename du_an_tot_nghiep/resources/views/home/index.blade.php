@extends('layouts.app')

@section('title', 'Featured Hotels')

@section('content')
<div class="container">
    <h2 class="mb-4">Featured Hotel</h2>

    @if($featuredHotel)
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">{{ $featuredHotel->name }}</h4>
                <p class="card-text">{{ $featuredHotel->description }}</p>
            </div>
        </div>

        <h3>Phòng trong khách sạn</h3>
        <div class="row">
            @foreach($featuredHotel->phongs as $phong)
                <div class="col-md-4">
                    <div class="card mb-3">
                        <img src="{{ asset('storage/' . $phong->image) }}" class="card-img-top" alt="{{ $phong->name }}">
                        <div class="card-body">
                            <h5 class="card-title">{{ $phong->name }}</h5>
                            <p class="card-text">Giá: {{ number_format($phong->price) }} VNĐ / đêm</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p>Chưa có khách sạn nổi bật.</p>
    @endif
</div>
@endsection