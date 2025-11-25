@extends('layouts.admin')

@section('content')
    <style>
        /* Print helpers */
        @media print {
            body * {
                visibility: hidden;
            }

            #printable,
            #printable * {
                visibility: visible;
            }

            #printable {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            /* Tùy chỉnh lề in (nếu muốn) */
            @page {
                margin: 10mm;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>

    <div id="printable" class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Checkout — Booking #{{ $booking->ma_tham_chieu }}</h3>
            <div class="d-print-none">
                <!-- Print button -->
                <button type="button" class="btn btn-outline-primary me-2" onclick="window.print()">
                    <i class="bi bi-printer"></i> In hoá đơn
                </button>
                <a href="{{ route('staff.bookings.show', $booking->id) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </a>
            </div>
        </div>

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

        {{-- Room lines --}}
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

        {{-- Totals card --}}
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

        {{-- Issued invoices (chờ thanh toán) --}}
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

                            <div class="d-flex gap-2 align-items-center d-print-none">
                                <form
                                    action="{{ route('staff.bookings.invoices.confirm', ['booking' => $booking->id, 'hoaDon' => $hd->id]) }}"
                                    method="POST"
                                    onsubmit="return confirm('Xác nhận đã thu tiền cho hoá đơn #{{ $hd->id }}? Sau khi xác nhận, hệ thống sẽ checkout và giải phóng phòng.')">
                                    @csrf
                                    <button class="btn btn-sm btn-success">
                                        <i class="bi bi-cash-stack"></i> Xác nhận đã thanh toán
                                    </button>
                                </form>
                                <a href="{{ route('staff.bookings.invoice.print', ['hoaDon' => $hd->id]) }}"
                                    class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-printer"></i> In hoá đơn
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mb-4 d-print-none">
                <a href="{{ route('staff.bookings.show', $booking->id) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </a>
            </div>
        @else
            <form action="{{ route('staff.bookings.checkout.process', $booking->id) }}" method="POST">
                @csrf

                {{-- If either early or late eligible -> hide mark_paid checkbox and force paid --}}
                @if ((!empty($earlyEligible) && $earlyEligible) || (!empty($lateEligible) && $lateEligible))
                    <input type="hidden" name="mark_paid" value="1">
                @else
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
                @endif

                {{-- Early checkout area --}}
                @if (!empty($earlyEligible) && $earlyEligible)
                    @php
                        $earlyNet = $earlyNet ?? $earlyRefundEstimate - ($extrasTotal ?? 0);
                        $earlyNetIsRefund = $earlyNetIsRefund ?? $earlyNet >= 0;
                        $earlyNetDisplay = $earlyNetDisplay ?? (int) round(abs($earlyNet), 0);
                    @endphp

                    <div class="alert alert-info mb-3">
                        <strong>Checkout sớm:</strong>
                        <div>Thời điểm hiện tại checkout sớm <strong>{{ $earlyDays }}</strong> ngày.</div>
                        <div>Tổng tiền 1 đêm (tất cả phòng): <strong>{{ number_format($dailyTotal, 0) }} ₫</strong></div>

                        <div class="mt-2">
                            @if ($earlyNetIsRefund)
                                <div>Ước tính <strong class="text-success">hoàn</strong> (đã trừ các khoản phát sinh):
                                    <strong class="text-success">{{ number_format($earlyNetDisplay, 0) }} ₫</strong>
                                </div>
                            @else
                                <div>Ước tính <strong class="text-danger">phải thu thêm</strong> (phát sinh > khoản hoàn):
                                    <strong class="text-danger">{{ number_format($earlyNetDisplay, 0) }} ₫</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <input type="hidden" name="early_checkout" value="1">
                @endif

                {{-- Late checkout area --}}
                @if (!empty($lateEligible) && $lateEligible)
                    @php
                        $lateNetDisplay =
                            $lateNetDisplay ?? (int) round(($lateFeeEstimate ?? 0) + ($extrasTotal ?? 0), 0);
                    @endphp

                    <div class="alert alert-warning mb-3">
                        <strong>Checkout muộn:</strong>
                        <div>
                            Đã quá giờ checkout chuẩn
                            <strong>
                                {{ $lateHoursFull ?? 0 }}
                                giờ{{ !empty($lateMinutesRemainder) && $lateMinutesRemainder ? ' ' . $lateMinutesRemainder . ' phút' : '' }}
                            </strong>
                            (tính từ
                            {{ $booking->ngay_tra_phong ? \Carbon\Carbon::parse($booking->ngay_tra_phong)->setTime(12, 0)->format('d/m/Y H:i') : '12:00' }}).
                        </div>
                        <div>Tổng tiền 1 đêm (tất cả phòng): <strong>{{ number_format($dailyTotal, 0) }} ₫</strong></div>
                        <div class="mt-2">Phí checkout muộn (đã cộng phát sinh):
                            <strong class="text-danger">{{ number_format($lateNetDisplay, 0) }} ₫</strong>
                            <div class="small text-muted"> (tính theo: {{ number_format($dailyTotal / 24 ?? 0, 0) }} ₫/giờ
                                × {{ number_format($lateHoursFloat ?? 0, 0) }} giờ)</div>
                        </div>
                    </div>
                @endif

                {{-- Buttons --}}
                <div class="d-flex gap-2 d-print-none">
                    <a href="{{ route('staff.bookings.show', $booking->id) }}" class="btn btn-outline-secondary btn">
                        <i class="bi bi-arrow-left me-1"></i> Hủy
                    </a>

                    @if (!empty($earlyEligible) && $earlyEligible)
                        <button type="submit" name="action" value="early_checkout" class="btn btn-warning btn"
                            onclick="return confirm('Xác nhận checkout sớm?')">
                            <i class="bi bi-box-arrow-right me-1"></i>
                            @if ($earlyNetIsRefund)
                                Xác nhận Checkout sớm (Hoàn: {{ number_format($earlyNetDisplay, 0) }} ₫)
                            @else
                                Xác nhận Checkout sớm (Thu thêm: {{ number_format($earlyNetDisplay, 0) }} ₫)
                            @endif
                        </button>
                    @elseif (!empty($lateEligible) && $lateEligible)
                        <button type="submit" name="action" value="late_checkout" class="btn btn-danger btn"
                            onclick="return confirm('Xác nhận checkout muộn?')">
                            <i class="bi bi-clock-history me-1"></i> Xác nhận Checkout muộn (Phí:
                            {{ number_format($lateNetDisplay, 0) }} ₫)
                        </button>
                    @else
                        <button type="submit" class="btn btn-danger btn"
                            onclick="return confirm('Xác nhận checkout? Sau khi checkout, các phòng sẽ được giải phóng.')">
                            <i class="bi bi-box-arrow-right me-1"></i> Xác nhận Checkout & Tạo hoá đơn
                        </button>
                    @endif
                </div>
            </form>
        @endif

    </div>
@endsection
