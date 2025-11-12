@extends('layouts.admin')

@section('title', 'Thêm vật dụng mới')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus me-2"></i>Thêm vật dụng mới</h2>
        <a href="{{ route('admin.vat-dung.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.vat-dung.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="ten" class="form-label">Tên vật dụng <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('ten') is-invalid @enderror" id="ten"
                                name="ten" value="{{ old('ten') }}" required>
                            @error('ten')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 row gx-2">
                            <div class="col-md-6">
                                <label for="gia" class="form-label">Giá (VND)</label>
                                <input type="number" step="0.01" class="form-control @error('gia') is-invalid @enderror"
                                    id="gia" name="gia" value="{{ old('gia') }}">
                                @error('gia')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Để trống nếu không áp giá mặc định.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="loai" class="form-label">Loại</label>
                                <select name="loai" id="loai" class="form-select">
                                    <option value="do_dung" {{ old('loai') === 'do_dung' ? 'selected' : '' }}>Đồ dùng
                                    </option>
                                    <option value="do_an" {{ old('loai') === 'do_an' ? 'selected' : '' }}>Dịch vụ tiêu thụ</option>
                                </select>
                                <div class="form-text">Chọn "Dịch vụ tiêu thụ" nếu tính theo số lượng tiêu thụ.</div>
                                <div class="form-text">Chọn "Đồ dùng" nếu cần theo dõi trạng thái (mất/hỏng).</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="mo_ta" class="form-label">Mô tả</label>
                            <textarea class="form-control @error('mo_ta') is-invalid @enderror" id="mo_ta" name="mo_ta" rows="4">{{ old('mo_ta') }}</textarea>
                            @error('mo_ta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                                {{ old('active', true) ? 'checked' : '' }}>
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
                            <div class="form-text">JPG, PNG, WEBP. Kích thước tối đa: 2MB.</div>
                        </div>

                        <div class="preview-container" id="preview-container" style="display: none;">
                            <label class="form-label">Xem trước:</label>
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
                        <i class="fas fa-save me-2"></i>Lưu
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
    </script>
@endsection
