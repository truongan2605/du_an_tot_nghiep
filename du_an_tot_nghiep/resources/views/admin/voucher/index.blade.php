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
                <th>Số lượng còn lại</th>
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
                <td>{{ max(0, $voucher->qty - ($voucher->users_count ?? 0)) }}</td>
                <td>{{ $voucher->usage_limit_per_user }}</td>
                <td>{{ $voucher->start_date }}</td>
                <td>{{ $voucher->end_date }}</td>
                <td>
                    <a href="{{ route('admin.voucher.show', $voucher->id) }}" class="btn btn-info btn-sm">Xem</a>
                    <a href="{{ route('admin.voucher.edit', $voucher->id) }}" class="btn btn-warning btn-sm">Sửa</a>

                    @if($voucher->active)
                        {{-- Nút vô hiệu hóa (gọi destroy, nhưng controller chỉ set active = 0) --}}
                        <form action="{{ route('admin.voucher.destroy', $voucher->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm"
                                onclick="return confirm('Bạn có chắc muốn vô hiệu hóa voucher này?')">
                                Vô hiệu hóa
                            </button>
                        </form>
                    @else
                        {{-- Đã bị vô hiệu hóa, chỉ hiển thị trạng thái --}}
                        <button class="btn btn-secondary btn-sm" disabled>Đã vô hiệu hóa</button>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
