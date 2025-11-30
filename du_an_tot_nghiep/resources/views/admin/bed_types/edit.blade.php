@extends('layouts.admin')

@section('title', 'Sửa loại giường')

@section('content')
    <div class="container">
        <h2>Sửa loại giường</h2>

        <form action="{{ route('admin.bed-types.update', $bedType->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Tên</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $bedType->name) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Giới hạn người</label>
                <input type="number" name="capacity" class="form-control" min="1"
                    value="{{ old('capacity', $bedType->capacity) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Giá (VND / đêm)</label>
                <input type="text" id="price" name="price" class="form-control"
                    value="{{ number_format(old('price', $bedType->price), 0, ',', '.') }}" oninput="formatMoney(this)"
                    maxlength="20" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control">{{ old('description', $bedType->description) }}</textarea>
            </div>

            <button class="btn btn-primary" type="submit">Cập nhật</button>
            <a href="{{ route('admin.bed-types.index') }}" class="btn btn-secondary">Hủy</a>
        </form>
        <script>
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

                if (v.length > 12) {
                    v = v.substring(0, 12);
                }

                if (v === "") {
                    input.value = "";
                    return;
                }

                input.value = Number(v).toLocaleString("vi-VN");
            }
        </script>
    </div>
@endsection
