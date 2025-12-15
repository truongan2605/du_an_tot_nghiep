@extends('layouts.admin')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">Danh sách Voucher</h1>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.voucher.create') }}" class="btn btn-primary">+ Thêm voucher</a>
            </div>
        </div>

        {{-- Search & per-page --}}
        <div class="row mb-3 g-2">
            <div class="col-12 col-md-6">
                <form method="GET" action="{{ route('admin.voucher.index') }}" class="d-flex">
                    <input type="search" name="search" class="form-control me-2" placeholder="Tìm theo tên hoặc mã..."
                        value="{{ request('search') }}">
                    {{-- preserve per_page --}}
                    <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">
                    <button class="btn btn-outline-secondary" type="submit">Tìm</button>
                </form>
            </div>

            <div class="col-12 col-md-6 text-md-end">
                <form id="perPageForm" method="GET" action="{{ route('admin.voucher.index') }}"
                    class="d-inline-flex align-items-center">
                    {{-- preserve search param --}}
                    @if (request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    <label for="per_page" class="me-2 mb-0 small text-muted">Số / trang</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm" style="width:110px;">
                        @php $currentPer = (int) request('per_page', 15); @endphp
                        <option value="10" {{ $currentPer === 10 ? 'selected' : '' }}>10</option>
                        <option value="15" {{ $currentPer === 15 ? 'selected' : '' }}>15</option>
                        <option value="25" {{ $currentPer === 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $currentPer === 50 ? 'selected' : '' }}>50</option>
                    </select>
                </form>
            </div>
        </div>

        {{-- Nếu không có voucher --}}
        @if (method_exists($vouchers, 'total') && $vouchers->total() === 0)
            <div class="alert alert-info">Không có voucher nào.</div>
        @else
            {{-- Summary --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small text-muted">
                    Hiển thị
                    <strong>{{ $vouchers->firstItem() ?? 0 }} - {{ $vouchers->lastItem() ?? 0 }}</strong>
                    trên tổng <strong>{{ $vouchers->total() }}</strong> voucher
                </div>

                <div class="small text-muted">
                    Trang <strong>{{ $vouchers->currentPage() }}</strong> / <strong>{{ $vouchers->lastPage() }}</strong>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Mã</th>
                            <th>Loại</th>
                            <th>Giá trị</th>
                            <th>Điểm đổi</th>
                            <th>Số lượng</th>
                            <th>Số lượng còn lại</th>
                            {{-- <th>Lượt/Người</th> --}}
                            <th>Ngày bắt đầu</th>
                            <th>Ngày kết thúc</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vouchers as $voucher)
                            <tr>
                                <td style="min-width:200px;">{{ $voucher->name }}</td>
                                <td>{{ $voucher->code }}</td>
                                <td>{{ $voucher->type }}</td>
                                <td>{{ $voucher->gia_tri_hien_thi ?? $voucher->value }}</td>
                                <td>{{ $voucher->points_required ?? '-' }}</td>
                                <td>{{ $voucher->qty }}</td>
                                <td>{{ is_null($voucher->qty) ? '-' : max(0, $voucher->qty - ($voucher->users_count ?? 0)) }}
                                </td>
                                {{-- <td>{{ $voucher->usage_limit_per_user }}</td> --}}
                                <td>{{ optional($voucher->start_date)->format('Y-m-d') }}</td>
                                <td>{{ optional($voucher->end_date)->format('Y-m-d') }}</td>
                                <td style="white-space:nowrap;">
                                    <a href="{{ route('admin.voucher.show', $voucher->id) }}"
                                        class="btn btn-info btn-sm">Xem</a>
                                    <a href="{{ route('admin.voucher.edit', $voucher->id) }}"
                                        class="btn btn-warning btn-sm">Sửa</a>

                                    @if ($voucher->active)
                                        <form action="{{ route('admin.voucher.destroy', $voucher->id) }}" method="POST"
                                            style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm"
                                                onclick="return confirm('Bạn có chắc muốn vô hiệu hóa voucher này?')">
                                                Vô hiệu hóa
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-secondary btn-sm" disabled>Đã vô hiệu hóa</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination links --}}
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    {{-- show small info if desired --}}
                </div>
                <div>
                    {{ $vouchers->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('change', function(e) {
            const el = e.target;
            if (!el) return;
            if (el.id === 'per_page') {
                const form = document.getElementById('perPageForm');
                if (!form) return;
                let pageInput = form.querySelector('input[name="page"]');
                if (pageInput) pageInput.value = 1;
                else {
                    pageInput = document.createElement('input');
                    pageInput.type = 'hidden';
                    pageInput.name = 'page';
                    pageInput.value = '1';
                    form.appendChild(pageInput);
                }
                form.submit();
            }
        });
    </script>
@endpush
