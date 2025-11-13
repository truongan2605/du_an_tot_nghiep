@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Danh sách Voucher</h1>
    <a href="{{ route('admin.voucher.create') }}" class="btn btn-primary mb-3">+ Thêm voucher</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tên</th>
                <th>Mã</th>
                <th>Loại</th>
                <th>Giá trị</th>
                <th>Số lượng</th>
                <th>Lượt/Người</th>
                <th>Ngày bắt đầu</th>
                <th>Ngày kết thúc</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vouchers as $voucher)
            <tr>
                <td>{{ $voucher->name }}</td>
                <td>{{ $voucher->code }}</td>
                <td>{{ $voucher->type }}</td>
                <td>{{ $voucher->value }}</td>
                <td>{{ $voucher->qty }}</td>
                <td>{{ $voucher->usage_limit_per_user }}</td>
                <td>{{ $voucher->start_date }}</td>
                <td>{{ $voucher->end_date }}</td>
                <td>
                    <a href="{{ route('admin.voucher.show', $voucher->id) }}" class="btn btn-info btn-sm">Xem</a>
                    <a href="{{ route('admin.voucher.edit', $voucher->id) }}" class="btn btn-warning btn-sm">Sửa</a>
                    <form action="{{ route('admin.voucher.destroy', $voucher->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa voucher này?')">Xóa</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
