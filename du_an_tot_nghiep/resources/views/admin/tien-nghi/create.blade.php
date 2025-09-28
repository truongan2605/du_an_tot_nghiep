@extends('layouts.admin')

@section('title', 'Thêm tiện nghi mới')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-plus me-2"></i>Thêm tiện nghi mới</h2>
    <a href="{{ route('admin.tien-nghi.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Quay lại
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.tien-nghi.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="ten" class="form-label">Tên tiện nghi <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('ten') is-invalid @enderror" 
                               id="ten" 
                               name="ten" 
                               value="{{ old('ten') }}" 
                               required>
                        @error('ten')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="mo_ta" class="form-label">Mô tả</label>
                        <textarea class="form-control @error('mo_ta') is-invalid @enderror" 
                                  id="mo_ta" 
                                  name="mo_ta" 
                                  rows="4">{{ old('mo_ta') }}</textarea>
                        @error('mo_ta')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="active" 
                                   name="active" 
                                   value="1" 
                                   {{ old('active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Kích hoạt tiện nghi
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="icon" class="form-label">Icon</label>
                        <input type="file" 
                               class="form-control @error('icon') is-invalid @enderror" 
                               id="icon" 
                               name="icon" 
                               accept="image/*">
                        @error('icon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Chọn file ảnh (JPG, PNG, GIF, SVG). Kích thước tối đa: 2MB
                        </div>
                    </div>

                    <div class="preview-container" id="preview-container" style="display: none;">
                        <label class="form-label">Xem trước:</label>
                        <div class="border rounded p-2 text-center">
                            <img id="preview-image" src="" alt="Preview" class="img-fluid" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.tien-nghi.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Hủy
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Lưu tiện nghi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('icon').addEventListener('change', function(e) {
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
</script>
@endsection

