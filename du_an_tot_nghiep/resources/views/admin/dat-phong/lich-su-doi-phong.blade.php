@extends('layouts.admin')

@section('title', 'Lịch sử đổi phòng')

@section('content')

<h4 class="mb-3">
    Lịch sử đổi phòng — Booking #{{ $booking->id }}
</h4>

@if($lichSuDoiPhong->isEmpty())
    <div class="alert alert-info">
        Chưa có lịch sử đổi phòng
    </div>
@else
<table class="table table-bordered">
    <thead class="table-light">
        <tr>
            <th>Thời gian</th>
            <th>Phòng cũ</th>
            <th>Phòng mới</th>
            <th>Giá cũ</th>
            <th>Giá mới</th>
            <th>Số đêm</th>
            <th>Người đổi</th>
        </tr>
    </thead>
    <tbody>
      @foreach($lichSuDoiPhong as $ls)
<tr>
    <td>{{ $ls->created_at->format('d/m/Y H:i') }}</td>

    <td>
        {{ optional(\App\Models\Phong::find($ls->phong_cu_id))->name }}
    </td>

    <td>
        {{ optional(\App\Models\Phong::find($ls->phong_moi_id))->name }}
    </td>

    <td>{{ number_format($ls->gia_cu) }}đ</td>
    <td>{{ number_format($ls->gia_moi) }}đ</td>
    <td>{{ $ls->so_dem }}</td>
    <td>{{ $ls->nguoi_thuc_hien }}</td>
</tr>
@endforeach

    </tbody>
</table>

@endif

<a href="{{ route('staff.bookings.show', $booking->id) }}"
   class="btn btn-secondary mt-3">
    Quay lại booking
</a>

@endsection
