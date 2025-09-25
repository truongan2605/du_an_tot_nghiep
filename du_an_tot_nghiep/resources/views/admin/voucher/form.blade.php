<div class="mb-3">
    <label for="code" class="form-label">Mã voucher</label>
    <input type="text" name="code" id="code" class="form-control"
           value="{{ old('code', $voucher->code ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="type" class="form-label">Loại</label>
    <select name="type" id="type" class="form-select" required>
        <option value="fixed" {{ old('type', $voucher->type ?? '') == 'fixed' ? 'selected' : '' }}>Giảm số tiền cố định</option>
        <option value="percent" {{ old('type', $voucher->type ?? '') == 'percent' ? 'selected' : '' }}>Giảm phần trăm</option>
    </select>
</div>

<div class="mb-3">
    <label for="value" class="form-label">Giá trị</label>
    <input type="number" name="value" id="value" class="form-control"
           value="{{ old('value', $voucher->value ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="start_date" class="form-label">Ngày bắt đầu</label>
    <input type="date" name="start_date" id="start_date" class="form-control"
           value="{{ old('start_date', $voucher->start_date ?? '') }}" required>
</div>

<div class="mb-3">
    <label for="end_date" class="form-label">Ngày kết thúc</label>
    <input type="date" name="end_date" id="end_date" class="form-control"
           value="{{ old('end_date', $voucher->end_date ?? '') }}" required>
</div>
