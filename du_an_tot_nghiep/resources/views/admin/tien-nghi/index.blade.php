@extends('layouts.admin')

@section('title', 'Quản lý dịch vụ')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-concierge-bell me-2"></i>Quản lý dịch vụ</h2>
        <a href="{{ route('admin.tien-nghi.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Thêm dịch vụ mới
        </a>
    </div>
    <form id="toggle-active-form" method="POST" class="d-none">
        @csrf @method('PATCH')
    </form>
    <form id="destroy-form" method="POST" class="d-none">
        @csrf @method('DELETE')
    </form>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Xóa dịch vụ</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Bạn có chắc chắn muốn xóa dịch vụ này?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger" id="confirmDeleteModalButton" form="destroy-form" formaction="">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($tienNghis->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>STT</th>
                                <th>Icon</th>
                                <th>Tên dịch vụ</th>
                                <th>Giá</th>
                                <th>Mô tả</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tienNghis as $tienNghi)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if ($tienNghi->icon && Storage::disk('public')->exists($tienNghi->icon))
                                            <img src="{{ Storage::url($tienNghi->icon) }}" alt="{{ $tienNghi->ten }}"
                                                class="rounded mx-auto d-block"
                                                style="width: 50px; height: 50px; object-fit: cover;">
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
                                        <div class="d-flex gap-1" role="group">
                                            <a href="{{ route('admin.tien-nghi.show', $tienNghi) }}"
                                                class="btn btn-sm btn-info" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.tien-nghi.edit', $tienNghi) }}"
                                                class="btn btn-sm btn-warning" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <button type="submit" form="toggle-active-form"
                                                formaction="{{ route('admin.tien-nghi.toggle-active', $tienNghi) }}"
                                                class="btn btn-sm {{ $tienNghi->active ? 'btn-secondary' : 'btn-success' }}"
                                                title="{{ $tienNghi->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                <i
                                                    class="fas {{ $tienNghi->active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                            </button>

                                            <button type="button" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                                class="btn btn-sm btn-danger" title="Xóa" onclick="confirmDelete('{{ route('admin.tien-nghi.destroy', $tienNghi) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <style>
                    .pagination-tien-nghi p.text-muted{
                        margin-bottom: 0 !important;
                    }

                    .pagination-tien-nghi ul.pagination{
                        margin-bottom: 0 !important;
                    }
                </style>
                <div class="pagination-tien-nghi">
                    {{ $tienNghis->links() }}
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));

            window.confirmDelete = function(action){
                const confirmDeleteModalButton = document.getElementById('confirmDeleteModalButton');
                confirmDeleteModalButton.setAttribute('formaction', action);
            }
        });
    </script>
@endsection
