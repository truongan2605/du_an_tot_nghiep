@csrf
<div class="mb-3">
    <label for="name">Tên voucher</label>
    <input type="text" name="name" id="name" class="form-control"
           value="{{ old('name', $voucher->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="code">Mã voucher</label>
    <input type="text" name="code" id="code" class="form-control"
           value="{{ old('code', $voucher->code ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="type">Loại</label>
    <select name="type" id="type" class="form-control" required>
        <option value="fixed"   {{ old('type', $voucher->type ?? '') == 'fixed' ? 'selected' : '' }}>Giảm tiền</option>
        <option value="percent" {{ old('type', $voucher->type ?? '') == 'percent' ? 'selected' : '' }}>Giảm %</option>
    </select>
</div>

<div class="mb-3">
    <label for="value">Giá trị</label>
    <input type="number" step="0.01" name="value" id="value" class="form-control"
           value="{{ old('value', $voucher->value ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="qty">Số lượng</label>
    <input type="number" name="qty" id="qty" class="form-control"
           value="{{ old('qty', $voucher->qty ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="usage_limit_per_user">Số lượt tối đa mỗi user</label>
    <input type="number" name="usage_limit_per_user" id="usage_limit_per_user" class="form-control"
           value="{{ old('usage_limit_per_user', $voucher->usage_limit_per_user ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="start_date">Ngày bắt đầu</label>
    <input type="date" name="start_date" id="start_date" class="form-control"
           value="{{ old(
                'start_date',
                isset($voucher) && $voucher->start_date
                    ? \Illuminate\Support\Carbon::parse($voucher->start_date)->format('Y-m-d')
                    : ''
           ) }}" required>
</div>

<div class="mb-3">
    <label for="end_date">Ngày kết thúc</label>
    <input type="date" name="end_date" id="end_date" class="form-control"
           value="{{ old(
                'end_date',
                isset($voucher) && $voucher->end_date
                    ? \Illuminate\Support\Carbon::parse($voucher->end_date)->format('Y-m-d')
                    : ''
           ) }}" required>
</div>

<div class="mb-3">
    <label for="active">Trạng thái</label>
    <div class="form-check">
        {{-- Hidden để khi bỏ tick thì vẫn gửi giá trị 0 --}}
        <input type="hidden" name="active" value="0">
        <input type="checkbox"
               name="active"
               id="active"
               class="form-check-input"
               value="1"
               {{ old('active', isset($voucher) ? $voucher->active : 1) ? 'checked' : '' }}>
        <label class="form-check-label" for="active">Đang hoạt động</label>
    </div>
</div>
