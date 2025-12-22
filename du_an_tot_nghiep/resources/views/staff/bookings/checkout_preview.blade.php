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

        {{-- Pending Payment Warning --}}
        @if (!empty($pendingPayment))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Có thanh toán online đang chờ!</strong> 
                Phát hiện giao dịch {{ strtoupper($pendingPayment->nha_cung_cap) }} chưa hoàn tất 
                ({{ number_format($pendingPayment->so_tien, 0) }}₫, 
                {{ $pendingPayment->created_at->diffForHumans() }}).
                Nếu tạo thanh toán mới, giao dịch cũ sẽ tự động hủy.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Room lines --}}
   <h5>Chi tiết phòng</h5>
<h5>Chi tiết phòng</h5>
<div class="table-responsive mb-3">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Phòng</th>
                <th>Loại</th>
                <th>Giá gốc/đêm</th>
                <th>Phụ thu/đêm</th>
                <th>Weekend</th>
                <th>Voucher</th>
                <th>Số đêm</th>
                <th class="text-end">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roomLines as $line)
                <tr>
                    <td><strong>{{ $line['ma_phong'] ?? 'Phòng #' . $line['phong_id'] }}</strong></td>
                    <td>{{ $line['loai'] }}</td>
                    <td>{{ number_format($line['base_price'] ?? 0) }}₫</td>
                    <td>
                        @if(($line['extra_charge'] ?? 0) > 0)
                            <span class="text-warning">+{{ number_format($line['extra_charge']) }}₫</span>
                            <div class="small text-muted">
                                @if(($line['extra_adults'] ?? 0) > 0)
                                    {{ $line['extra_adults'] }} NL
                                @endif
                                @if(($line['extra_children'] ?? 0) > 0)
                                    {{ ($line['extra_adults'] ?? 0) > 0 ? ', ' : '' }}{{ $line['extra_children'] }} TE
                                @endif
                            </div>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if(($line['weekend_surcharge'] ?? 0) > 0)
                            <span class="text-danger">+{{ number_format($line['weekend_surcharge']) }}₫</span>
                            <div class="small text-muted">{{ $line['weekend_nights'] ?? 0 }} đêm</div>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if(($line['voucher_per_room'] ?? 0) > 0)
                            <span class="text-success">-{{ number_format($line['voucher_per_room']) }}₫</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $line['nights'] }}</td>
                    <td class="text-end">
                        <strong>{{ number_format($line['line_total_after_voucher'] ?? 0) }}₫</strong>
                        @if(($line['voucher_per_room'] ?? 0) > 0)
                            <div class="small text-muted text-decoration-line-through">
                                {{ number_format($line['line_total']) }}₫
                            </div>
                        @endif
                    </td>
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
{{-- ✅ LỊCH SỬ ĐỔI PHÒNG --}}
@if(!empty($changeRoomHistory) && $changeRoomHistory->count() > 0)
<h5>Lịch sử đổi phòng</h5>
<div class="card mb-3 border-info">
    <div class="card-body">
        @foreach($changeRoomHistory as $history)
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
            <div>
                <strong>
                    #{{ $history->phongCu->ma_phong ?? '' }} 
                    <i class="bi bi-arrow-right mx-2"></i> 
                    #{{ $history->phongMoi->ma_phong ?? '' }}
                </strong>
                <div class="small text-muted">
                    {{ $history->created_at->format('d/m/Y H:i') }}
                    @if($history->loai == 'nang_cap')
                        <span class="badge bg-success ms-2">Nâng cấp</span>
                    @elseif($history->loai == 'ha_cap')
                        <span class="badge bg-warning text-dark ms-2">Hạ cấp</span>
                    @else
                        <span class="badge bg-secondary ms-2">Ngang bằng</span>
                    @endif
                </div>
            </div>
            <div class="text-end">
                @php
                    $diff = $history->gia_moi - $history->gia_cu;
                @endphp
                @if($diff > 0)
                    <span class="text-danger fw-bold">+{{ number_format($diff) }}₫</span>
                    <div class="small text-muted">Tăng</div>
                @elseif($diff < 0)
                    <span class="text-success fw-bold">{{ number_format($diff) }}₫</span>
                    <div class="small text-muted">Giảm (hoàn)</div>
                @else
                    <span class="text-muted">0₫</span>
                @endif
            </div>
        </div>
        @endforeach
        
        @if(abs($totalRoomChangeDiff ?? 0) > 0)
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <strong>Tổng chênh lệch từ đổi phòng:</strong>
            <strong class="{{ ($totalRoomChangeDiff ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                {{ ($totalRoomChangeDiff ?? 0) > 0 ? '+' : '' }}{{ number_format($totalRoomChangeDiff ?? 0) }}₫
            </strong>
        </div>
        @endif
    </div>
</div>
@endif

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
            <div>Tổng phòng (ban đầu)</div>
            <div>{{ number_format($roomSnapshot ?? $roomsTotal, 0) }} ₫</div>
        </div>
        
        @if(abs($totalRoomChangeDiff ?? 0) > 0)
        <div class="d-flex justify-content-between">
            <div>Chênh lệch đổi phòng</div>
            <div class="{{ ($totalRoomChangeDiff ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                {{ ($totalRoomChangeDiff ?? 0) > 0 ? '+' : '' }} {{ number_format($totalRoomChangeDiff ?? 0, 0) }} ₫
            </div>
        </div>
        @endif
        
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
            <div>{{ number_format(($roomSnapshot ?? $roomsTotal) + ($totalRoomChangeDiff ?? 0) + ($extrasTotal ?? 0) - ($discount ?? 0), 0) }} ₫</div>
        </div>

        <div class="d-flex justify-content-between">
            <div>Đã thu trước</div>
            <div>- {{ number_format($roomsTotal ?? 0, 0) }} ₫</div>
        </div>

        <div class="d-flex justify-content-between fw-semibold">
            <div>Còn phải thanh toán</div>
            <div class="{{ ($actualAmountToPay ?? 0) > 0 ? 'text-danger' : (($actualAmountToPay ?? 0) < 0 ? 'text-success' : '') }}">
                {{ number_format($actualAmountToPay ?? 0, 0) }} ₫
                @if(($actualAmountToPay ?? 0) < 0)
                    <small>(Hoàn lại)</small>
                @endif
            </div>
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

                @if (!empty($lateEligible) && $lateEligible && !empty($blockingNextBooking))
                    <div class="alert alert-warning mb-3">
                        <div class="mt-2">
                            <strong>Lưu ý:</strong> Có booking kế tiếp cho cùng phòng
                            ({{ $blockingNextBooking->ma_tham_chieu ?? '#' . $blockingNextBooking->id }})
                            dự kiến check-in ngày
                            <strong>
                                {{
                                    $blockingNextBookingStart
                                        ? $blockingNextBookingStart->format('d/m/Y')
                                        : \Carbon\Carbon::parse($blockingNextBooking->ngay_nhan_phong ?? now())->format('d/m/Y') }}
                            </strong>.
                            Bạn <strong>không thể</strong> cho khách ở muộn nếu muốn giữ phòng cho booking sau đó — cân nhắc
                            chuyển phòng hoặc hẹn khách.
                            <div class="mt-1">
                                <a href="{{ route('staff.bookings.show', $blockingNextBooking->id) }}"
                                    class="btn btn-sm btn-outline-secondary">Mở booking kế tiếp</a>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Payment Method (when need to collect fees) --}}
                @php
                    $needPayment = false;
                    $paymentAmount = 0;

                    // Late checkout always needs payment
                    if (!empty($lateEligible) && $lateEligible) {
                        $needPayment = true;
                        $paymentAmount = $lateNetDisplay;
                    }
                    // Early checkout needs payment if customer owes
                    elseif (!empty($earlyEligible) && $earlyEligible && !$earlyNetIsRefund) {
                        $needPayment = true;
                        $paymentAmount = $earlyNetDisplay;
                    }
                    // Normal checkout (on time) needs payment if there are extras
                    elseif (empty($earlyEligible) && empty($lateEligible) && ($extrasTotal ?? 0) > 0) {
                        $needPayment = true;
                        $paymentAmount = $extrasTotal;
                    }
                @endphp

                @if ($needPayment && $paymentAmount > 0)
                    <div class="card mb-3 border-primary">
                        <div class="card-header bg-primary bg-opacity-10">
                            <strong><i class="bi bi-credit-card me-2"></i>Phương thức thanh toán</strong>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                Số tiền cần thu: <strong class="text-danger">{{ number_format($paymentAmount, 0) }} ₫</strong>
                            </div>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="cash" checked>
                                <label class="btn btn-outline-dark" for="pay_cash">
                                    <i class="bi bi-cash-stack"></i> Tiền mặt
                                </label>
                                
                                <input type="radio" class="btn-check" name="payment_method" id="pay_vnpay" value="vnpay">
                                <label class="btn btn-outline-primary" for="pay_vnpay">
                                    <i class="bi bi-credit-card"></i> VNPay
                                </label>
                                
                                <input type="radio" class="btn-check" name="payment_method" id="pay_momo" value="momo">
                                <label class="btn btn-outline-danger" for="pay_momo">
                                    <i class="bi bi-wallet2"></i> MoMo
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle"></i> Thanh toán online sẽ chuyển đến trang VNPay/MoMo
                            </small>
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

    @if ($needPayment ?? false)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="{{ route('staff.bookings.checkout.process', $booking->id) }}"]');
            if (!form) return;

            const checkoutButtons = form.querySelectorAll('button[type="submit"]');
            
            checkoutButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
                    
                    if (paymentMethod && paymentMethod !== 'cash') {
                        e.preventDefault();
                        
                        const amount = {{ $paymentAmount ?? 0 }};
                        const action = btn.value || 'checkout';
                        
                        console.log('=== Payment Init ===', {paymentMethod, action, amount});
                        
                        // Call API to get payment URL
                        fetch('{{ route('staff.checkout.pay-online', $booking->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                payment_method: paymentMethod,
                                action: action,
                                amount: amount
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            console.log('=== Payment Response ===', data);
                            
                            if (data.success && data.payment_url) {
                                console.log('REDIRECTING TO:', data.payment_url);
                                window.location.href = data.payment_url;
                            } else {
                                console.error('NO PAYMENT URL!', data);
                                alert('Lỗi: ' + (data.message || 'Không thể tạo thanh toán'));
                            }
                        })
                        .catch(err => {
                            console.error('FETCH ERROR:', err);
                            alert('Có lỗi xảy ra, vui lòng thử lại');
                        });
                    }
                    // If cash, let form submit normally
                });
            });
        });
    </script>
    @endif
@endsection
