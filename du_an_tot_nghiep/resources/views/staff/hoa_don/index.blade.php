@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Hóa đơn</h3>
        <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">Quay về Dashboard</a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Tìm theo Số hoá đơn / Booking / ID">
        </div>
        <div class="col-auto">
            <select name="status" class="form-select">
                <option value="">-- Trạng thái --</option>
                <option value="da_xuat" {{ (isset($status) && $status=='da_xuat') ? 'selected' : '' }}>Đã xuất (chờ thanh toán)</option>
                <option value="da_thanh_toan" {{ (isset($status) && $status=='da_thanh_toan') ? 'selected' : '' }}>Đã thanh toán</option>
                <option value="draft" {{ (isset($status) && $status=='draft') ? 'selected' : '' }}>Tạo</option>
            </select>
        </div>
        <div class="col-auto">
            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="form-control" />
        </div>
        <div class="col-auto">
            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="form-control" />
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">Lọc</button>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('staff.invoices.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Số hoá đơn</th>
                            <th>Booking</th>
                            <th>Khách</th>
                            <th>Tổng thực thu</th>
                            <th>Đơn vị</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $hd)
                            <tr>
                                <td>{{ $hd->id }}</td>
                                <td>{{ $hd->so_hoa_don ?? '—' }}</td>
                                <td>
                                    @if ($hd->datPhong)
                                        <a href="{{ route('staff.bookings.show', $hd->datPhong->id) }}">#{{ $hd->datPhong->ma_tham_chieu ?? $hd->datPhong->id }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $hd->datPhong->nguoiDung->name ?? ($hd->datPhong->contact_name ?? '—') }}</td>
                                <td>{{ number_format($hd->tong_thuc_thu, 0) }} ₫</td>
                                <td>{{ $hd->don_vi ?? 'VND' }}</td>
                                <td>
                                    @if ($hd->trang_thai === 'da_thanh_toan')
                                        <span class="badge bg-success">Đã thanh toán</span>
                                    @elseif ($hd->trang_thai === 'da_xuat')
                                        <span class="badge bg-warning text-dark">Chờ thanh toán</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $hd->trang_thai }}</span>
                                    @endif
                                </td>
                                <td>{{ optional($hd->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('staff.invoices.show', $hd->id) }}" class="btn btn-sm btn-outline-primary">Xem</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Không có hoá đơn nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">Hiển thị {{ $invoices->count() }} / {{ $invoices->total() }} hoá đơn</div>
                <div>{{ $invoices->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
