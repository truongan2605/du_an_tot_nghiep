@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h3 class="mb-4 text-center">Danh sách Voucher</h3>

    <form method="GET" class="mb-3 d-flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo mã..." class="form-control w-50">
        <select name="filter" class="form-select w-25">
            <option value="">Tất cả</option>
            <option value="valid" {{ request('filter')=='valid' ? 'selected' : '' }}>Còn hạn</option>
            <option value="expired" {{ request('filter')=='expired' ? 'selected' : '' }}>Hết hạn</option>
        </select>
        <button class="btn btn-primary">Lọc</button>
    </form>

    <div class="row">
        @foreach($vouchers as $voucher)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold">{{ $voucher->code }}</h5>
                    <p>Loại: {{ $voucher->type == 'percent' ? $voucher->value . '%' : number_format($voucher->value) . 'đ' }}</p>
                    <p>Hạn: {{ $voucher->start_date }} - {{ $voucher->end_date }}</p>
                    <button class="btn btn-outline-success btn-sm copy-btn" data-code="{{ $voucher->code }}">
                        Lấy mã
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{ $vouchers->links() }}
</div>

<script>
    
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.code);
        btn.innerText = 'Đã sao chép!';
        setTimeout(() => btn.innerText = 'Lấy mã', 2000);
    });
});


</script>

@endsection
