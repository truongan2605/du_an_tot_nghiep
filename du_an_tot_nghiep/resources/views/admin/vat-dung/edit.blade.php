@extends('layouts.admin')

@section('title', 'Chỉnh sửa vật dụng')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit me-2"></i>Chỉnh sửa vật dụng</h2>
        <a href="{{ route('admin.vat-dung.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.vat-dung.update', $vatDung) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="ten" class="form-label">Tên vật dụng <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('ten') is-invalid @enderror" id="ten"
                                name="ten" value="{{ old('ten', $vatDung->ten) }}" required>
                            @error('ten')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 row gx-2">
                            <div class="col-md-6">
                                <label for="gia" class="form-label">Giá (VND)</label>
                                <input type="text" class="form-control @error('gia') is-invalid @enderror"
                                id="gia" name="gia"
                                value="{{ old('gia', number_format($vatDung->gia, 0, ',', '.')) }}"
                                oninput="formatMoney(this)" required>
                                @error('gia')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Giá mặc định (dùng nếu không có giá override cho phòng).</div>
                            </div>

                            <div class="col-md-6">
                                <label for="loai" class="form-label">Loại</label>
                                @if($vatDung->loai === \App\Models\VatDung::LOAI_DICH_VU_KHAC)
                                    <input type="text" class="form-control" value="Dịch vụ khác" disabled>
                                    <input type="hidden" name="loai" value="dich_vu_khac">
                                    <div class="form-text text-warning">
                                        <i class="fas fa-info-circle"></i> Loại này được quản lý từ phần Quản lý dịch vụ và không thể thay đổi tại đây.
                                    </div>
                                @else
                                    <select name="loai" id="loai" class="form-select">
                                        <option value="do_dung"
                                            {{ old('loai', $vatDung->loai) === 'do_dung' ? 'selected' : '' }}>Đồ dùng (durable)
                                        </option>
                                        <option value="do_an" {{ old('loai', $vatDung->loai) === 'do_an' ? 'selected' : '' }}>
                                            Đồ ăn (tiêu thụ)</option>
                                    </select>
                                    <div class="form-text">Đổi loại nếu cần; chú ý ảnh hưởng tới luồng tính tiền.</div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="mo_ta" class="form-label">Mô tả</label>
                            <textarea class="form-control @error('mo_ta') is-invalid @enderror" id="mo_ta" name="mo_ta" rows="4">{{ old('mo_ta', $vatDung->mo_ta) }}</textarea>
                            @error('mo_ta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                                {{ old('active', $vatDung->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">Kích hoạt</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="icon" class="form-label">Icon</label>
                            <input type="file" class="form-control @error('icon') is-invalid @enderror" id="icon"
                                name="icon" accept="image/*">
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Chọn file để thay thế ảnh.</div>
                        </div>

                        @if ($vatDung->icon && Storage::disk('public')->exists($vatDung->icon))
                            <div class="mb-3">
                                <label class="form-label">Icon hiện tại:</label>
                                <div class="border rounded p-2 text-center">
                                    <img src="{{ Storage::url($vatDung->icon) }}" alt="{{ $vatDung->ten }}"
                                        class="img-fluid" style="max-height: 200px; object-fit: contain;">
                                </div>
                            </div>
                        @endif

                        <div class="preview-container" id="preview-container" style="display: none;">
                            <label class="form-label">Xem trước icon mới:</label>
                            <div class="border rounded p-2 text-center">
                                <img id="preview-image" src="" alt="Preview" class="img-fluid"
                                    style="max-height: 200px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('admin.vat-dung.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Hủy
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const iconInput = document.getElementById('icon');
        if (iconInput) {
            iconInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('preview-image').src = e.target.result;
                        document.getElementById('preview-container').style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    document.getElementById('preview-container').style.display = 'none';
                }
            });
        }

        function formatMoney(input) {
            let v = input.value.toLowerCase().replace(/\s+/g, '');

            if (v.endsWith('k')) {
                v = v.replace('k', '');
                v = parseInt(v || 0) * 1000;
            } else if (v.endsWith('m')) {
                v = v.replace('m', '');
                v = parseInt(v || 0) * 1000000;
            } else if (v.endsWith('b')) {
                v = v.replace('b', '');
                v = parseInt(v || 0) * 1000000000;
            } else {
                v = v.replace(/\D/g, '');
            }

            if (v.length > 12) v = v.substring(0, 12);

            if (v === "") {
                input.value = "";
                return;
            }

            input.value = Number(v).toLocaleString("vi-VN");
        }
    </script>
@endsection
