@csrf
<div class="mb-3">
    <label for="code">Mã voucher</label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $voucher->code ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="type">Loại</label>
    <select name="type" class="form-control" required>
        <option value="fixed" {{ (old('type', $voucher->type ?? '') == 'fixed') ? 'selected' : '' }}>Giảm tiền</option>
        <option value="percent" {{ (old('type', $voucher->type ?? '') == 'percent') ? 'selected' : '' }}>Giảm %</option>
    </select>
</div>

<div class="mb-3">
    <label for="value">Giá trị</label>
    <input type="number" step="0.01" name="value" class="form-control" value="{{ old('value', $voucher->value ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="qty">Số lượng</label>
    <input type="number" name="qty" class="form-control" value="{{ old('qty', $voucher->qty ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="usage_limit_per_user">Số lượt tối đa mỗi user</label>
    <input type="number" name="usage_limit_per_user" class="form-control" value="{{ old('usage_limit_per_user', $voucher->usage_limit_per_user ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="start_date">Ngày bắt đầu</label>
    <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $voucher->start_date ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="end_date">Ngày kết thúc</label>
    <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $voucher->end_date ?? '') }}" required>
</div>
