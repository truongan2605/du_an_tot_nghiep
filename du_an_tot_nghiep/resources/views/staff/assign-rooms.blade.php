@extends('layouts.staff')
@section('content')
    <h2>Gán Phòng cho Booking #{{ $booking->id }}</h2>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('conflicts'))
        <div class="alert alert-warning">{{ implode(', ', session('conflicts')) }}</div>
    @endif
    <form action="{{ route('staff.assign-rooms.post', $booking->id) }}" method="POST">
        @csrf
        <table class="table">
            <thead><tr><th>ID Item</th><th>Loại Phòng</th><th>Chọn Phòng</th></tr></thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->loaiPhong->ten ?? 'N/A' }}</td>
                        <td>
                            <select name="assignments[{{ $item->id }}][phong_id]" class="form-control" required>
                                <option value="">Chưa gán</option>
                                @foreach ($availableRooms as $room)
                                    <option value="{{ $room->id }}"
                                            {{ old("assignments.{$item->id}.phong_id") == $room->id ? 'selected' : '' }}
                                            {{ $room->trang_thai === 'da_dat' ? 'disabled' : '' }}>
                                        Phòng {{ $room->ma_phong }} (Tầng {{ $room->tang->ten }})
                                        - {{ $room->trang_thai === 'da_dat' ? 'Đã gán' : 'Trống' }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="assignments[{{ $item->id }}][dat_phong_item_id]" value="{{ $item->id }}">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button type="submit" class="btn btn-success">Gán Phòng</button>
    </form>
@endsection