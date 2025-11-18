@extends('layouts.admin')

@section('content')
    <div class="container py-4">
        <h3>Checkout — Booking #{{ $booking->ma_tham_chieu }}</h3>

        <div class="card mb-3">
            <div class="card-body">
                <div><strong>Khách hàng:</strong> {{ $booking->nguoiDung->name ?? ($booking->contact_name ?? '—') }}</div>
                <div><strong>Địa chỉ lưu trú:</strong> {{ $address }}</div>
                <div><strong>Check-in:</strong>
                    {{ optional($booking->checked_in_at)->format('d/m/Y H:i') ?? (optional($booking->ngay_nhan_phong)->format('d/m/Y') ?? '—') }}
                </div>
                <div><strong>Check-out dự kiến:</strong> {{ optional($booking->ngay_tra_phong)->format('d/m/Y') ?? '—' }}
                </div>
                <div><strong>Số khách:</strong> {{ $booking->so_khach ?? '—' }}</div>
            </div>
        </div>

        {{-- Room lines (unchanged) --}}
        <h5>Chi tiết phòng</h5>
        <div class="table-responsive mb-3">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Phòng</th>
                        <th>Loại</th>
                        <th>Giá/đêm</th>
                        <th>Số lượng</th>
                        <th>Đêm</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roomLines as $line)
                        <tr>
                            <td>{{ $line['ma_phong'] ?? 'Phòng #' . $line['phong_id'] }}</td>
                            <td>{{ $line['loai'] }}</td>
                            <td>{{ number_format($line['unit_price'] ?? 0) }} ₫</td>
                            <td style="padding-left: 25px">{{ $line['qty'] }}</td>
                            <td style="padding-left: 22px">{{ $line['nights'] }}</td>
                            <td>{{ number_format($line['line_total'] ?? 0) }} ₫</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Extras --}}
        <h5>Các khoản phát sinh (dịch vụ / sự cố)</h5>
        <ul class="list-group mb-3">
            @forelse ($extrasItems as $ei)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>{{ $ei['name'] }} <small class="text-muted">x{{ $ei['quantity'] }}</small></div>
                    <div class="text-end">{{ number_format($ei['amount'], 0) }} ₫</div>
                </li>
            @empty
                <li class="list-group-item text-muted">Không có phát sinh.</li>
            @endforelse
        </ul>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>Tổng phòng</div>
                    <div>{{ number_format($roomSnapshot ?? $roomsTotal, 0) }} ₫</div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>Phát sinh (chưa thanh toán)</div>
                    <div>{{ number_format($extrasTotal ?? 0, 0) }} ₫</div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>Giảm giá</div>
                    <div>- {{ number_format($discount ?? 0, 0) }} ₫</div>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <div>Tổng</div>
                    <div>{{ number_format(($roomSnapshot ?? $roomsTotal) + ($extrasTotal ?? 0) - ($discount ?? 0), 0) }}
                        ₫</div>
                </div>

                <div class="d-flex justify-content-between">
                    <div>Đã thu trước</div>
                    <div>- {{ number_format($roomsTotal ?? 0, 0) }} ₫</div>
                </div>

                <div class="d-flex justify-content-between fw-semibold">
                    <div>Còn phải thanh toán (chỉ phát sinh)</div>
                    <div>{{ number_format($amountToPayNow ?? ($extrasTotal ?? 0), 0) }} ₫</div>
                </div>
            </div>
        </div>


        @php
            $issuedInvoices = \App\Models\HoaDon::where('dat_phong_id', $booking->id)
                ->where('trang_thai', 'da_xuat')
                ->orderByDesc('id')
                ->get();
        @endphp

        @if ($issuedInvoices->isNotEmpty())
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Hoá đơn chờ thanh toán</h6>

                    @foreach ($issuedInvoices as $hd)
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <strong>#{{ $hd->id }}</strong>
                                <div class="small text-muted">Số tiền: {{ number_format($hd->tong_thuc_thu, 0) }} ₫ — Trạng
                                    thái: <span class="badge bg-warning text-dark">{{ $hd->trang_thai }}</span></div>
                            </div>

                            <div class="d-flex gap-2 align-items-center">

                                <form
                                    action="{{ route('staff.bookings.invoices.confirm', ['booking' => $booking->id, 'hoaDon' => $hd->id]) }}"
                                    method="POST"
                                    onsubmit="return confirm('Xác nhận đã thu tiền cho hoá đơn #{{ $hd->id }}? Sau khi xác nhận, hệ thống sẽ checkout và giải phóng phòng.')">
                                    @csrf
                                    <button class="btn btn-sm btn-success">
                                        <i class="bi bi-cash-stack"></i> Xác nhận đã thanh toán
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mb-4">
                <a href="{{ route('staff.bookings.show', $booking->id) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </a>
            </div>
        @else
            <form action="{{ route('staff.bookings.checkout.process', $booking->id) }}" method="POST">
                @csrf

                @if (($extrasTotal ?? 0) > 0)
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="mark_paid" name="mark_paid" value="1">
                        <label class="form-check-label" for="mark_paid">
                            Đánh dấu tất cả hoá đơn liên quan là <strong>đã thanh toán</strong> (nếu bạn đã thu tiền)
                        </label>
                    </div>
                @else
                    <input type="hidden" name="mark_paid" value="1">
                @endif

                <div class="d-flex gap-2">
                    <a href="{{ route('staff.bookings.show', $booking->id) }}" class="btn btn-outline-secondary btn">
                        <i class="bi bi-arrow-left me-1"></i> Hủy
                    </a>

                    <button type="submit" class="btn btn-danger btn"
                        onclick="return confirm('Xác nhận checkout? Sau khi checkout, các phòng sẽ được giải phóng và các dat_phong_item sẽ bị xóa.')">
                        <i class="bi bi-box-arrow-right me-1"></i> Xác nhận Checkout & Tạo hoá đơn
                    </button>
                </div>
            </form>
        @endif

    </div>
@endsection
