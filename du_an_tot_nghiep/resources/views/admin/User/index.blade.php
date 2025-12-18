@extends('layouts.admin')

@section('title', 'Quản Lý Khách Hàng')

@section('content')
<div class="card shadow-sm border-0 rounded">
    <div class="card-header bg-primary text-white px-4 py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Danh Sách Khách Hàng</h5>
            <small class="opacity-75">Quản lý và theo dõi thông tin khách hàng</small>
        </div>
        <a href="{{ route('admin.user.create') }}" class="btn btn-light btn-sm">
            <i class="fas fa-plus me-1"></i>Thêm Mới
        </a>
    </div>

    <div class="card-body p-0">
        <!-- Search and Filters -->
        <div class="p-3 border-bottom">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm theo tên hoặc email..." onkeyup="filterTable()">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="statusFilter" onchange="filterTable()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Không hoạt động</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <span class="text-muted small" id="recordCount">Tổng: {{ $users->count() ?? 0 }} khách hàng</span>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="customerTable">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="border-0 small fw-semibold text-uppercase text-muted ps-4">ID</th>
                        <th class="border-0 small fw-semibold text-uppercase text-muted">Tên</th>
                        <th class="border-0 small fw-semibold text-uppercase text-muted">Email</th>
                        <th class="border-0 small fw-semibold text-uppercase text-muted">SĐT</th>
                        <th class="border-0 small fw-semibold text-uppercase text-muted">Trạng Thái</th>
                        <th class="border-0 small fw-semibold text-uppercase text-muted pe-4 text-end">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}" data-status="{{ $user->is_disabled ? 'inactive' : 'active' }}">
                            <td class="ps-4 small text-muted">{{ $user->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-1 me-2">
                                        <i class="fas fa-user text-primary fs-6"></i>
                                    </div>
                                    <span class="fw-semibold">{{ $user->name }}</span>
                                </td>
                            <td class="small"><i class="fas fa-envelope text-muted me-1"></i>{{ $user->email }}</td>
                            <td class="small">{{ $user->so_dien_thoai ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $user->is_disabled ? 'bg-danger' : 'bg-success' }}">
                                    {{ $user->is_disabled ? 'Đã vô hiệu hóa' : 'Hoạt động' }}
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.user.show', $user) }}" class="btn btn-outline-info" title="Chi Tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.user.edit', $user) }}" class="btn btn-outline-warning" title="Chỉnh Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.user.toggle', $user) }}" method="POST" style="display:inline;" class="d-inline me-1">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn {{ $user->is_disabled ? 'btn-outline-success' : 'btn-outline-danger' }}" title="{{ $user->is_disabled ? 'Kích Hoạt' : 'Vô Hiệu Hóa' }}" onclick="return confirm('Xác nhận {{ $user->is_disabled ? 'kích hoạt' : 'vô hiệu hóa' }} tài khoản?')">
                                            <i class="fas {{ $user->is_disabled ? 'fa-check' : 'fa-ban' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-users fa-3x mb-3 opacity-50"></i>
                                    <h6 class="mb-1">Chưa có khách hàng nào</h6>
                                    <p class="mb-0">Bắt đầu bằng cách thêm khách hàng mới.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination (if available) -->
        @if(isset($users) && method_exists($users, 'links'))
            <div class="p-3 border-top bg-light">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    function filterTable() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const table = document.getElementById('customerTable');
        const rows = table.getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 1; i < rows.length; i++) { // Skip header
            const row = rows[i];
            const name = row.getAttribute('data-name') || '';
            const email = row.getAttribute('data-email') || '';
            const status = row.getAttribute('data-status') || '';
            const matchesSearch = name.includes(input) || email.includes(input);
            const matchesStatus = !statusFilter || status === statusFilter;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }

        document.getElementById('recordCount').textContent = `Tổng: ${visibleCount} khách hàng`;
    }
</script>
@endsection