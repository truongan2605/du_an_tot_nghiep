{{-- @extends('layouts.admin')

@section('title', 'Setup đồ ăn cho phòng')

@section('content')
<div class="card">
    <div class="card-body">
        Hiển thị lỗi validate (server)
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @include('admin.phong._food_setup_form', [
            'phong' => $phong,
            'datPhong' => $datPhong,
            'doAnList' => $doAnList,
            'existingReservations' => $existingReservations
        ])
    </div>
</div>
@endsection --}}
