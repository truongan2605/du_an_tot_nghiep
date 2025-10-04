@extends('layouts.staff')
@section('content')
    <h2>Quản Lý Tình Trạng Phòng</h2>
    <table class="table">
        <thead><tr><th>Mã Phòng</th><th>Tầng</th><th>Trạng Thái</th><th>Khách Hàng</th></tr></thead>
        <tbody>
            @foreach ($rooms as $room)
                <tr>
                    <td>{{ $room->phong->ma_phong }}</td>
                    <td>{{ $room->phong->tang->ten }}</td>
                    <td>{{ $room->trang_thai }}</td>
                    <td>{{ $room->datPhongItem->datPhong->user->name ?? 'Chưa xác định' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $rooms->links() }}
@endsection