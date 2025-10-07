@extends('layouts.admin')

@section('title', 'Quản lý dịch vụ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-concierge-bell me-2"></i>Quản lý dịch vụ</h2>
    <a href="{{ route('admin.tien-nghi.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Thêm dịch vụ mới
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($tienNghis->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Tên dịch vụ</th>
                            <th>giá</th>
                            <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tienNghis as $tienNghi)
                        <tr>
                            <td>{{ $tienNghi->id }}</td>
                            <td>
                                @if($tienNghi->icon && Storage::disk('public')->exists($tienNghi->icon))
                                    <img src="{{ Storage::url($tienNghi->icon) }}" 
                                         alt="{{ $tienNghi->ten }}" 
                                         class="img-thumbnail" 
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <i class="fas fa-image text-muted" style="font-size: 20px;"></i>
                                @endif
                            </td>
                            <td>{{ $tienNghi->ten }}</td>
                            <td>{{ number_format($tienNghi->gia, 0, ',', '.') }}</td>
                            <td>{{ Str::limit($tienNghi->mo_ta, 50) }}</td>
                            <td>
                                <span class="badge {{ $tienNghi->active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $tienNghi->active ? 'Hoạt động' : 'Không hoạt động' }}
                                </span>
                            </td>
                            <td>{{ $tienNghi->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.tien-nghi.show', $tienNghi) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.tien-nghi.edit', $tienNghi) }}" 
                                       class="btn btn-sm btn-warning" 
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.tien-nghi.toggle-active', $tienNghi) }}" 
                                          method="POST" 
                                          class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="btn btn-sm {{ $tienNghi->active ? 'btn-secondary' : 'btn-success' }}"
                                                title="{{ $tienNghi->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                            <i class="fas {{ $tienNghi->active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.tien-nghi.destroy', $tienNghi) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa dịch vụ này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-danger" 
                                                title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted">
                    Hiển thị {{ $tienNghis->firstItem() }}–{{ $tienNghis->lastItem() }} trong tổng {{ $tienNghis->total() }} dịch vụ
                </div>
                <div>
                    {{ $tienNghis->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-concierge-bell fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Chưa có dịch vụ nào</h4>
                <p class="text-muted">Hãy thêm dịch vụ đầu tiên để bắt đầu quản lý.</p>
                <a href="{{ route('admin.tien-nghi.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Thêm dịch vụ mới
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

