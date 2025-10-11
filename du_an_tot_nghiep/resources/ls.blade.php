{{-- resources/views/list-room.blade.php --}}
@extends('layouts.app')

@section('title', 'Danh sách phòng - Booking')

@section('content')
<section class="pt-4 pb-5">
    <div class="container">
        <div class="row mb-4 text-center">
            <h1 class="fw-bold">Danh sách phòng</h1>
            <p class="text-muted">Khám phá những căn phòng tuyệt vời dành cho bạn</p>
        </div>

        @if(isset($phongs) && $phongs->count())
            <div class="row g-4">
                @foreach($phongs as $phong)
                    <div class="col-lg-4 col-md-6">
                        <div class="card shadow-sm border-0 h-100">
                            {{-- Ảnh phòng --}}
                            @php
                                $image = $phong->images->first()->duong_dan ?? 'template/stackbros/assets/images/default-room.jpg';
                            @endphp
                            <img src="{{ asset($image) }}" class="card-img-top" alt="{{ $phong->name }}">

                            <div class="card-body">
                                <h5 class="card-title mb-2">{{ $phong->name ?? $phong->ma_phong }}</h5>
                                <p class="text-muted small mb-2">
                                    Loại phòng: {{ $phong->loaiPhong->ten ?? ($phong->loaiPhong->ten_loai ?? '—') }} <br>
                                    Tầng: {{ $phong->tang->so_tang ?? '—' }}
                                </p>

                                <p class="fw-semibold mb-2 text-primary">
                                    <i class="bi bi-cash-stack me-1"></i>
                                    {{ number_format($phong->gia_mac_dinh ?? 0) }} VNĐ / đêm
                                </p>

                                <a href="{{ route('rooms.show', $phong->id) }}" class="btn btn-outline-primary w-100">
                                    Xem chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Phân trang --}}
            <div class="mt-4 d-flex justify-content-center">
                {{ $phongs->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <img src="{{ asset('template/stackbros/assets/images/no-data.svg') }}" alt="Không có phòng" width="200" class="mb-3">
                <h5>Hiện chưa có phòng nào được đăng.</h5>
            </div>
        @endif
    </div>
</section>
@endsection
