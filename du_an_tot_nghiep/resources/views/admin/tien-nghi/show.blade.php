@extends('layouts.admin')

@section('title', 'Chi tiết tiện nghi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-eye me-2"></i>Chi tiết tiện nghi</h2>
    <div>
        <a href="{{ route('admin.tien-nghi.edit', $tienNghi) }}" class="btn btn-warning">
            <i class="fas fa-edit me-2"></i>Chỉnh sửa
        </a>
        <a href="{{ route('admin.tien-nghi.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Thông tin tiện nghi</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td width="150"><strong>ID:</strong></td>
                        <td>{{ $tienNghi->id }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tên tiện nghi:</strong></td>
                        <td>{{ $tienNghi->ten }}</td>
                    </tr>
                    <tr>
                        <td><strong>Mô tả:</strong></td>
                        <td>{{ $tienNghi->mo_ta ?: 'Không có mô tả' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Trạng thái:</strong></td>
                        <td>
                            <span class="badge {{ $tienNghi->active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $tienNghi->active ? 'Hoạt động' : 'Không hoạt động' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Ngày tạo:</strong></td>
                        <td>{{ $tienNghi->created_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Cập nhật lần cuối:</strong></td>
                        <td>{{ $tienNghi->updated_at->format('d/m/Y H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Icon</h5>
            </div>
            <div class="card-body text-center">
                @if($tienNghi->icon)
                    <img src="{{ asset('storage/' . $tienNghi->icon) }}" 
                         alt="{{ $tienNghi->ten }}" 
                         class="img-fluid rounded" 
                         style="max-height: 300px;">
                @else
                    <div class="text-muted py-5">
                        <i class="fas fa-image fa-3x mb-3"></i>
                        <p>Chưa có icon</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Thao tác</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.tien-nghi.edit', $tienNghi) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Chỉnh sửa
                    </a>
                    
                    <form action="{{ route('admin.tien-nghi.toggle-active', $tienNghi) }}" method="POST" class="d-grid">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                class="btn {{ $tienNghi->active ? 'btn-secondary' : 'btn-success' }}">
                            <i class="fas {{ $tienNghi->active ? 'fa-toggle-off' : 'fa-toggle-on' }} me-2"></i>
                            {{ $tienNghi->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                        </button>
                    </form>
                    
                    <form action="{{ route('admin.tien-nghi.destroy', $tienNghi) }}" 
                           method="POST" 
                          onsubmit="return confirm('Bạn có chắc chắn muốn xóa tiện nghi này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash me-2"></i>Xóa
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
