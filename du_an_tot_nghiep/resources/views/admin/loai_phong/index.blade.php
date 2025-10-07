@extends('layouts.admin')

@section('title', 'Danh sách Loại Phòng')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Danh sách Loại Phòng</h2>
            <a href="{{ route('admin.loai_phong.create') }}" class="btn btn-primary">+ Thêm loại phòng</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->has('error'))
            <div class="alert alert-danger">{{ $errors->first('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle" style="min-width:900px;">
                <thead class="table-dark">
                    <tr>
                        <th style="width:90px">Mã</th>
                        <th style="min-width:200px">Tên</th>
                        <th style="max-width:220px">Mô tả</th>
                        <th style="width:90px" class="text-center">Sức chứa</th>
                        <th style="width:90px" class="text-center">Giường</th>
                        <th style="width:140px" class="text-end">Giá mặc định</th>
                        <th style="width:120px" class="text-center">SL thực tế</th>
                        <th style="width:120px" class="text-center">Đang ở</th> 
                        <th style="min-width:180px">Tiện nghi</th>
                        <th style="width:220px" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loaiPhongs as $lp)
                        @php
                            $occupiedRooms = $lp->occupied_count ?? 0;
                            $amenities = $lp->tienNghis->pluck('ten')->toArray();
                            $amenitiesPreview = array_slice($amenities, 0, 3);
                        @endphp

                        <tr class="{{ $lp->active ? '' : 'table-secondary' }}">
                            <td class="align-middle fw-semibold">{{ $lp->ma }}</td>
                            <td class="align-middle">
                                <div class="fw-bold">{{ $lp->ten }}</div>
                                @if (!$lp->active)
                                    <small class="text-muted"><span class="badge bg-secondary">Vô hiệu</span></small>
                                @endif
                            </td>

                            <td class="align-middle text-truncate" style="max-width:220px;">
                                {{ \Illuminate\Support\Str::limit($lp->mo_ta ?? '-', 80) }}
                            </td>

                            <td class="align-middle text-center">{{ $lp->suc_chua }}</td>
                            <td class="align-middle text-center">{{ $lp->so_giuong }}</td>
                            <td class="align-middle text-end">{{ number_format($lp->gia_mac_dinh, 0, ',', '.') }} đ</td>
                            <td class="align-middle text-center">{{ $lp->so_luong_thuc_te }}</td>

                            <td class="align-middle text-center">
                                <span class="fw-bold text-danger">{{ $occupiedRooms }}</span>
                            </td>

                            <td class="align-middle">
                                @if (count($amenities) === 0)
                                    <span class="text-muted small"><em>Chưa có</em></span>
                                @else
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($amenitiesPreview as $a)
                                            <span class="badge bg-success small text-truncate"
                                                style="max-width:120px;">{{ $a }}</span>
                                        @endforeach
                                        @if (count($amenities) > count($amenitiesPreview))
                                            @php
                                                $remaining = count($amenities) - count($amenitiesPreview);
                                                $fullList = implode(', ', $amenities);
                                            @endphp
                                            <span class="badge bg-info small" data-bs-toggle="tooltip"
                                                title="{{ $fullList }}">
                                                +{{ $remaining }} thêm
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="align-middle text-center">
                                <a href="{{ route('admin.loai_phong.show', $lp->id) }}"
                                    class="btn btn-sm btn-info me-1">Xem</a>
                                <a href="{{ route('admin.loai_phong.edit', $lp->id) }}"
                                    class="btn btn-sm btn-warning me-1">Sửa</a>

                                @if ($lp->active)
                                    @if ($occupiedRooms > 0)
                                        <button class="btn btn-sm btn-secondary" disabled
                                            title="Có {{ $occupiedRooms }} phòng đang ở">
                                            Vô hiệu
                                        </button>
                                    @else
                                        <form action="{{ route('admin.loai_phong.disable', $lp->id) }}" method="POST"
                                            style="display:inline-block">
                                            @csrf
                                            <button class="btn btn-sm btn-secondary"
                                                onclick="return confirm('Vô hiệu hoá loại phòng này? Tất cả phòng thuộc loại sẽ chuyển sang trạng thái Bảo trì.')">
                                                Vô hiệu
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <form action="{{ route('admin.loai_phong.enable', $lp->id) }}" method="POST"
                                        style="display:inline-block">
                                        @csrf
                                        <button class="btn btn-sm btn-success"
                                            onclick="return confirm('Kích hoạt lại loại phòng này?')">
                                            Kích hoạt
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">Chưa có loại phòng nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                    new bootstrap.Tooltip(el);
                });
            }
        });
    </script>
@endsection
