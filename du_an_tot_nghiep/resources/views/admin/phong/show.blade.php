@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <div class="card shadow-lg border-0 rounded-3">
        {{-- Header --}}
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Chi tiết phòng: {{ $phong->ma_phong }}</h3>
        </div>

        {{-- Body --}}
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Loại phòng:</strong> {{ $phong->loaiPhong->ten ?? 'Chưa có' }}</p>
                    <p><strong>Tầng:</strong> {{ $phong->tang->ten ?? 'Chưa có' }}</p>
                    <p><strong>Sức chứa:</strong> {{ $phong->suc_chua }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Số giường:</strong> {{ $phong->so_giuong }}</p>
                    <p><strong>Giá mặc định:</strong> 
                        <span class="text-success fw-bold">{{ number_format($phong->gia_mac_dinh, 0, ',', '.') }} VND</span>
                    </p>
                    <p>
                        <strong>Trạng thái:</strong>
                        @if($phong->trang_thai === 'available')
                            <span class="badge bg-success">Còn phòng</span>
                        @elseif($phong->trang_thai === 'unavailable')
                            <span class="badge bg-danger">Hết phòng</span>
                        @else
                            <span class="badge bg-secondary">{{ $phong->trang_thai }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="mb-3">
    <strong>Tiện nghi:</strong>
    <ul>
        @forelse($phong->tienNghis as $tn)
            <li><i class="{{ $tn->icon }}"></i> {{ $tn->ten }}</li>
        @empty
            <li>Chưa có tiện nghi</li>
        @endforelse
    </ul>
</div>


           {{-- Hình ảnh --}}
<div class="mb-3">
    <h5>Hình ảnh:</h5>
    <div class="d-flex flex-wrap">
        @forelse($phong->images as $image)
            <div class="me-2 mb-2">
                <img src="{{ asset('storage/'.$image->image_path) }}" 
                     alt="Ảnh phòng" 
                     class="img-thumbnail rounded"
                     style="max-width: 300px; max-height: 200px;">
            </div>
        @empty
            <p class="text-muted">Chưa có ảnh</p>
        @endforelse
    </div>


        {{-- Footer --}}
        <div class="card-footer text-end">
            <a href="{{ route('admin.phong.index') }}" class="btn btn-secondary">
                ← Quay lại danh sách
            </a>
            <a href="{{ route('admin.phong.edit', $phong->id) }}" class="btn btn-primary">
                ✏️ Sửa
            </a>
            <form action="{{ route('admin.phong.destroy', $phong->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Bạn có chắc muốn xóa phòng này không?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    🗑 Xóa
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
