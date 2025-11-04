@extends('layouts.admin')

@section('title', 'Quản lý vật dụng trong phòng')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-concierge-bell me-2"></i>Quản lý vật dụng trong phòng</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.vat-dung.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Thêm vật dụng mới
            </a>
            <form action="{{ route('admin.vat-dung.index') }}" method="GET" class="d-flex">
                <input type="text" name="keyword" class="form-control me-2" placeholder="Nhập tên vật dụng cần tìm..."
                    value="{{ request('keyword') }}">
                <button type="submit" class="btn btn-outline-primary">Tìm</button>
            </form>
        </div>
    </div>

    {{-- Flash messages and validation errors --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-warning">
            <strong>Có lỗi:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Informational notice about delete rules --}}
    <div class="alert alert-info">
        <h6 class="mb-1">Chú ý khi xóa vật dụng</h6>
        <ul class="mb-0">
            <li><strong>Đồ dùng</strong> (ví dụ: TV, tủ lạnh, bàn ghế): chỉ được <em>xóa hoàn toàn</em> khi <strong>không có</strong> bất kỳ <em>Loại phòng</em> nào chứa vật dụng này. Nếu còn liên kết với loại phòng, vui lòng gỡ khỏi loại phòng trước.</li>
            <li><strong>Đồ ăn</strong> (tiêu thụ theo số lượng): nếu đã có <em>lịch sử tiêu thụ</em> (bản ghi trong lịch sử tiêu thụ), hệ thống sẽ <em>giữ lại lịch sử</em> và tự động <strong>đánh dấu vật dụng là không hoạt động</strong> (tên có hậu tố "(đã xóa)"). Nếu không có lịch sử, vật dụng sẽ bị xóa hoàn toàn.</li>
        </ul>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($vatdungs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Icon</th>
                                <th>Tên</th>
                                <th>Loại</th>
                                <th>Giá</th>
                                <th>Mô tả</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vatdungs as $vatdung)
                                <tr>
                                    <td>{{ $vatdung->id }}</td>
                                    <td>
                                        @if ($vatdung->icon && Storage::disk('public')->exists($vatdung->icon))
                                            <img src="{{ Storage::url($vatdung->icon) }}" alt="{{ $vatdung->ten }}"
                                                class="img-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <i class="fas fa-box text-muted" style="font-size: 20px;"></i>
                                        @endif
                                    </td>
                                    <td>{{ $vatdung->ten }}</td>
                                    <td>
                                        @if ($vatdung->loai === \App\Models\VatDung::LOAI_DO_AN)
                                            <span class="badge bg-info">Đồ ăn</span>
                                        @else
                                            <span class="badge bg-secondary">Đồ dùng</span>
                                        @endif
                                    </td>
                                    <td>{{ $vatdung->gia !== null ? number_format($vatdung->gia, 0, ',', '.') : '-' }}</td>
                                    <td>{{ Str::limit($vatdung->mo_ta, 50) }}</td>
                                    <td>
                                        <span class="badge {{ $vatdung->active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $vatdung->active ? 'Hoạt động' : 'Không hoạt động' }}
                                        </span>
                                    </td>
                                    <td>{{ $vatdung->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.vat-dung.show', $vatdung) }}"
                                                class="btn btn-sm btn-info" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.vat-dung.edit', $vatdung) }}"
                                                class="btn btn-sm btn-warning" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.vat-dung.toggle-active', $vatdung) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="btn btn-sm {{ $vatdung->active ? 'btn-secondary' : 'btn-success' }}"
                                                    title="{{ $vatdung->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                    <i class="fas {{ $vatdung->active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.vat-dung.destroy', $vatdung) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Bạn có chắc chắn muốn xóa vật dụng này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Xóa">
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
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                    <div class="text-muted">
                        Hiển thị {{ $vatdungs->firstItem() }}–{{ $vatdungs->lastItem() }} trong tổng
                        {{ $vatdungs->total() }} vật dụng
                    </div>
                    <div>
                        {{ $vatdungs->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-concierge-bell fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Chưa có vật dụng nào</h4>
                    <p class="text-muted">Hãy thêm vật dụng để bắt đầu quản lý.</p>
                    <a href="{{ route('admin.vat-dung.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Thêm vật dụng mới
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
