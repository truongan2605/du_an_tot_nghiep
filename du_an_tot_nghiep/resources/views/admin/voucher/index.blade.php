@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="mb-4">Danh sách Voucher</h1>

    <a href="{{ route('voucher.create') }}" class="btn btn-primary mb-3">+ Thêm voucher</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Mã</th>
                <th>Loại</th>
                <th>Giá trị</th>
                <th>Ngày bắt đầu</th>
                <th>Ngày kết thúc</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vouchers as $voucher)
            <tr>
                <td>{{ $voucher->code }}</td>
                <td>{{ $voucher->type }}</td>
                <td>{{ $voucher->value }}</td>
                <td>{{ $voucher->start_date }}</td>
                <td>{{ $voucher->end_date }}</td>
                <td>
                    <a href="{{ route('voucher.show', $voucher->id) }}" class="btn btn-info btn-sm">Xem</a>
                    <a href="{{ route('voucher.edit', $voucher->id) }}" class="btn btn-warning btn-sm">Sửa</a>
                    <form action="{{ route('voucher.destroy', $voucher->id) }}" method="POST" style="display:inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa voucher này?')" class="btn btn-danger btn-sm">Xóa</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Chưa có voucher nào</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
