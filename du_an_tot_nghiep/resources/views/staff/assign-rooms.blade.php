@extends('layouts.staff')

@section('title', 'Gán Phòng')

@section('content')
    <div class="p-4">
        <h2>Gán Phòng Cho Booking {{ $booking->ma_tham_chieu }}</h2>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('conflicts'))
            <div class="alert alert-warning">Conflicts: {{ implode(', ', session('conflicts')) }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form action="{{ route('staff.assign-rooms', $booking->id) }}" method="POST">
            @csrf
            <table class="table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Loại Phòng</th>
                        <th>Phòng Available</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->loaiPhong->ten ?? 'N/A' }}</td>
                            <td>
                                <select name="assignments[{{ $item->id }}][phong_id]" class="form-control">
                                    <option value="">Chưa gán</option>
                                    @foreach ($availableRooms as $room)
                                        @if ($room->loai_phong_id == $item->loai_phong_id && $room->trang_thai == 'trong')
                                            <option value="{{ $room->id }}">Phòng {{ $room->ma_phong }} (Tầng {{ $room->tang->ten }})</option>
                                        @endif
                                    @endforeach
                                </select>
                                <input type="hidden" name="assignments[{{ $item->id }}][dat_phong_item_id]" value="{{ $item->id }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Gán Phòng</button>
        </form>
    </div>
@endsection