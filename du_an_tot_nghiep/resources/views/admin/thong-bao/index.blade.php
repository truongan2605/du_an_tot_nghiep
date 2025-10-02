@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 m-0">Quản lý thông báo</h1>
        <a href="{{ route('admin.thong-bao.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i> Tạo thông báo
        </a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm kiếm..." class="form-control">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary">Lọc</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Người nhận</th>
                        <th>Kênh</th>
                        <th>Template</th>
                        <th>Trạng thái</th>
                        <th>Số lần thử</th>
                        <th>Cập nhật</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($thongBaos as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ optional($item->nguoiNhan)->name }} (ID: {{ $item->nguoi_nhan_id }})</td>
                            <td>{{ $item->kenh }}</td>
                            <td>{{ $item->ten_template }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $item->trang_thai }}</span>
                            </td>
                            <td>{{ $item->so_lan_thu ?? 0 }}</td>
                            <td>{{ $item->updated_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.thong-bao.show', $item) }}" class="btn btn-sm btn-link">Xem</a>
                                <a href="{{ route('admin.thong-bao.edit', $item) }}" class="btn btn-sm btn-link text-warning">Sửa</a>
                                <form action="{{ route('admin.thong-bao.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa thông báo này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger">Xóa</button>
                                </form>
                                <form action="{{ route('admin.thong-bao.toggle-active', $item) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-link">Đổi trạng thái</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $thongBaos->links() }}</div>
</div>
@endsection


