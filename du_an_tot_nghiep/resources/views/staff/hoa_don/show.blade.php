@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Hoá đơn #{{ $hoaDon->id }} @if($hoaDon->so_hoa_don) — {{ $hoaDon->so_hoa_don }} @endif</h3>
        <div>
            <a href="{{ route('staff.invoices.index') }}" class="btn btn-outline-secondary">Quay lại</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div><strong>Booking:</strong>
                @if ($hoaDon->datPhong)
                    <a href="{{ route('staff.bookings.show', $hoaDon->datPhong->id) }}">
                        #{{ $hoaDon->datPhong->ma_tham_chieu ?? $hoaDon->datPhong->id }}
                    </a>
                @else
                    —
                @endif
            </div>
            <div><strong>Khách:</strong> {{ $hoaDon->datPhong->nguoiDung->name ?? ($hoaDon->datPhong->contact_name ?? '—') }}</div>
            <div><strong>Trạng thái:</strong> {{ $hoaDon->trang_thai }}</div>
            <div><strong>Ngày tạo:</strong> {{ optional($hoaDon->created_at)->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <h5>Chi tiết mục</h5>
    <div class="table-responsive mb-3">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Phòng</th>
                    <th>Loại</th>
                    <th class="text-end">Số lượng</th>
                    <th class="text-end">Đơn giá</th>
                    <th class="text-end">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($hoaDon->hoaDonItems as $it)
                    <tr>
                        <td>{{ $it->name }}</td>
                        <td>{{ $it->phong->ma_phong ?? ($it->phong_id ? 'Phòng #' . $it->phong_id : '—') }}</td>
                        <td>{{ $it->loaiPhong->ten ?? ($it->vatDung->ten ?? '—') }}</td>
                        <td class="text-end">{{ $it->quantity }}</td>
                        <td class="text-end">{{ number_format($it->unit_price ?? 0, 0) }} ₫</td>
                        <td class="text-end">{{ number_format($it->amount ?? 0, 0) }} ₫</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-end">Tổng</th>
                    <th class="text-end">{{ number_format($hoaDon->tong_thuc_thu ?? 0, 0) }} ₫</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mb-4">
        @if ($hoaDon->trang_thai !== 'da_thanh_toan')
            <form action="{{ route('staff.bookings.invoices.confirm', ['booking' => $hoaDon->dat_phong_id, 'hoaDon' => $hoaDon->id]) }}" method="POST" onsubmit="return confirm('Xác nhận đã thu tiền cho hoá đơn #{{ $hoaDon->id }}?')">
                @csrf
                <button class="btn btn-success"><i class="bi bi-cash-stack"></i> Xác nhận đã thanh toán</button>
            </form>
        @endif
    </div>
</div>
@endsection
