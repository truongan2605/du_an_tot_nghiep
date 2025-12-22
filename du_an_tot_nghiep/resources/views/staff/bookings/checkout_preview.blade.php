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
                        <th class="text-center">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roomLines as $index => $line)
                        @php
                            $weekdayNights = ($line['nights'] ?? 0) - ($line['weekend_nights'] ?? 0);
                            $basePrice = $line['base_price'] ?? 0;
                            $extraCharge = $line['extra_charge'] ?? 0;
                            $pricePerNight = $basePrice + $extraCharge;
                            $weekdayTotal = $pricePerNight * $weekdayNights;
                            $weekendBaseTotal = $basePrice * ($line['weekend_nights'] ?? 0);
                            $weekendSurcharge = $line['weekend_surcharge'] ?? 0;
                            $weekendExtraTotal = $extraCharge * ($line['weekend_nights'] ?? 0);
                            $weekendTotal = $weekendBaseTotal + $weekendSurcharge + $weekendExtraTotal;
                            $lineTotalPerRoom = $weekdayTotal + $weekendTotal;
                            $qty = $line['qty'] ?? 1;
                            $lineTotal = $lineTotalPerRoom * $qty;
                            $voucherPerRoom = $line['voucher_per_room'] ?? 0;
                            $lineTotalAfterVoucher = $lineTotal - $voucherPerRoom;
                        @endphp
                        <tr>
                            <td><strong>{{ $line['ma_phong'] ?? 'Phòng #' . $line['phong_id'] }}</strong></td>
                            <td>{{ $line['loai'] }}</td>
                            <td>{{ number_format($basePrice) }}₫</td>
                            <td>
                                @if ($extraCharge > 0)
                                    <span class="text-warning">+{{ number_format($extraCharge) }}₫</span>
                                    <div class="small text-muted">
                                        @if (($line['extra_adults'] ?? 0) > 0)
                                            {{ $line['extra_adults'] }} NL × 150,000₫
                                        @endif
                                        @if (($line['extra_children'] ?? 0) > 0)
                                            {{ ($line['extra_adults'] ?? 0) > 0 ? ' + ' : '' }}{{ $line['extra_children'] }}
                                            TE × 60,000₫
                                        @endif
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if (($line['weekend_nights'] ?? 0) > 0)
                                    <span class="text-danger">+{{ number_format($weekendSurcharge) }}₫</span>
                                    <div class="small text-muted">
                                        {{ $line['weekend_nights'] }} đêm × 10%
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if ($voucherPerRoom > 0)
                                    <span class="text-success">-{{ number_format($voucherPerRoom) }}₫</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $line['nights'] }}</td>
                            <td class="text-end">
                                <strong>{{ number_format($lineTotalAfterVoucher) }}₫</strong>
                                @if ($voucherPerRoom > 0)
                                    <div class="small text-muted text-decoration-line-through">
                                        {{ number_format($lineTotal) }}₫
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="collapse"
                                    data-bs-target="#calculationDetail{{ $index }}" aria-expanded="false">
                                    <i class="bi bi-calculator"></i> Xem
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" class="p-0 border-0">
                                <div class="collapse" id="calculationDetail{{ $index }}">
                                    <div class="card card-body bg-light mb-2">
                                        <h6 class="mb-3"><i class="bi bi-calculator me-2"></i>Chi tiết tính tiền cho
                                            {{ $line['ma_phong'] ?? 'Phòng #' . $line['phong_id'] }}</h6>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="border rounded p-3">
                                                    <h6 class="text-primary mb-2">1. Giá cơ bản</h6>
                                                    <div class="small">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span>Giá gốc/đêm:</span>
                                                            <strong>{{ number_format($basePrice) }}₫</strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span>Số lượng phòng:</span>
                                                            <strong>{{ $qty }} phòng</strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span>Tổng đêm:</span>
                                                            <strong>{{ $line['nights'] }} đêm</strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span>Trong đó:</span>
                                                            <span class="text-muted">{{ $weekdayNights }} ngày thường +
                                                                {{ $line['weekend_nights'] ?? 0 }} cuối tuần</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="border rounded p-3">
                                                    <h6 class="text-warning mb-2">2. Phụ thu</h6>
                                                    <div class="small">
                                                        @if (($line['extra_adults'] ?? 0) > 0)
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span>Người lớn thêm ({{ $line['extra_adults'] }}
                                                                    người):</span>
                                                                <strong>{{ number_format($line['extra_adults'] * 150000) }}₫</strong>
                                                            </div>
                                                        @endif
                                                        @if (($line['extra_children'] ?? 0) > 0)
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span>Trẻ em thêm ({{ $line['extra_children'] }}
                                                                    trẻ):</span>
                                                                <strong>{{ number_format($line['extra_children'] * 60000) }}₫</strong>
                                                            </div>
                                                        @endif
                                                        @if ($extraCharge > 0)
                                                            <div
                                                                class="d-flex justify-content-between mt-2 pt-2 border-top">
                                                                <span><strong>Tổng phụ thu/đêm:</strong></span>
                                                                <strong
                                                                    class="text-warning">+{{ number_format($extraCharge) }}₫</strong>
                                                            </div>
                                                        @else
                                                            <div class="text-muted">Không có phụ thu</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="border rounded p-3">
                                                    <h6 class="text-info mb-2">3. Tính theo ngày thường</h6>
                                                    <div class="small">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span>Giá/đêm (gốc + phụ thu):</span>
                                                            <strong>{{ number_format($pricePerNight) }}₫</strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span>Số đêm ngày thường:</span>
                                                            <strong>{{ $weekdayNights }} đêm</strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                                            <span><strong>Thành tiền ngày thường:</strong></span>
                                                            <strong>{{ number_format($weekdayTotal) }}₫</strong>
                                                        </div>
                                                        <div class="text-muted mt-1" style="font-size: 11px;">
                                                            = {{ number_format($pricePerNight) }}₫ × {{ $weekdayNights }}
                                                            đêm
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="border rounded p-3">
                                                    <h6 class="text-danger mb-2">4. Tính theo cuối tuần</h6>
                                                    <div class="small">
                                                        @if (($line['weekend_nights'] ?? 0) > 0)
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span>Giá gốc × {{ $line['weekend_nights'] }} đêm:</span>
                                                                <strong>{{ number_format($weekendBaseTotal) }}₫</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span>Phụ thu cuối tuần:</span>
                                                                <strong>{{ number_format($weekendExtraTotal) }}₫</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span>Phụ thu 10% cuối tuần:</span>
                                                                <strong
                                                                    class="text-danger">+{{ number_format($weekendSurcharge) }}₫</strong>
                                                            </div>
                                                            <div
                                                                class="d-flex justify-content-between mt-2 pt-2 border-top">
                                                                <span><strong>Thành tiền cuối tuần:</strong></span>
                                                                <strong>{{ number_format($weekendTotal) }}₫</strong>
                                                            </div>
                                                            <div class="text-muted mt-1" style="font-size: 11px;">
                                                                = ({{ number_format($basePrice) }}₫ +
                                                                {{ number_format($extraCharge) }}₫) ×
                                                                {{ $line['weekend_nights'] }} đêm + 10% phụ thu
                                                            </div>
                                                        @else
                                                            <div class="text-muted">Không có đêm cuối tuần</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="border rounded p-3 bg-white">
                                                    <h6 class="mb-3">5. Tổng hợp</h6>
                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <span>Ngày thường ({{ $weekdayNights }} đêm):</span>
                                                                <strong>{{ number_format($weekdayTotal) }}₫</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <span>Cuối tuần ({{ $line['weekend_nights'] ?? 0 }}
                                                                    đêm):</span>
                                                                <strong>{{ number_format($weekendTotal) }}₫</strong>
                                                            </div>
                                                            <div
                                                                class="d-flex justify-content-between mb-2 pt-2 border-top">
                                                                <span><strong>Tổng 1 phòng:</strong></span>
                                                                <strong>{{ number_format($lineTotalPerRoom) }}₫</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <span>Số lượng: {{ $qty }} phòng</span>
                                                                <span class="text-muted">× {{ $qty }}</span>
                                                            </div>
                                                            <div
                                                                class="d-flex justify-content-between mb-2 pt-2 border-top">
                                                                <span><strong>Tổng trước voucher:</strong></span>
                                                                <strong>{{ number_format($lineTotal) }}₫</strong>
                                                            </div>
                                                            @if ($voucherPerRoom > 0)
                                                                <div class="d-flex justify-content-between mb-2">
                                                                    <span class="text-success">Voucher giảm giá:</span>
                                                                    <strong
                                                                        class="text-success">-{{ number_format($voucherPerRoom) }}₫</strong>
                                                                </div>
                                                            @endif
                                                            <div
                                                                class="d-flex justify-content-between pt-2 border-top fw-bold fs-5">
                                                                <span class="text-primary">Tổng sau voucher:</span>
                                                                <strong
                                                                    class="text-primary">{{ number_format($lineTotalAfterVoucher) }}₫</strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="bg-light p-2 rounded">
                                                                <small class="text-muted">
                                                                    <strong>Công thức:</strong><br>
                                                                    Ngày thường = (Giá gốc + Phụ thu) × Số đêm<br>
                                                                    Cuối tuần = (Giá gốc + Phụ thu) × Số đêm + 10% giá
                                                                    gốc<br>
                                                                    Tổng = Ngày thường + Cuối tuần<br>
                                                                    Thành tiền = Tổng × Số lượng - Voucher
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Extras --}}
        {{-- <h5>Các khoản phát sinh (dịch vụ / sự cố)</h5>
        <ul class="list-group mb-3">
            @forelse ($extrasItems as $ei)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>{{ $ei['name'] }} <small class="text-muted">x{{ $ei['quantity'] }}</small></div>
                    <div class="text-end">{{ number_format($ei['amount'], 0) }} ₫</div>
                </li>
            @empty
                <li class="list-group-item text-muted">Không có phát sinh.</li>
            @endforelse
        </ul> --}}
        {{-- ✅ LỊCH SỬ ĐỔI PHÒNG --}}
        @if (!empty($changeRoomHistory) && $changeRoomHistory->count() > 0)
            <h5>Lịch sử đổi phòng</h5>
            <div class="card mb-3 border-info">
                <div class="card-body">
                    @foreach ($changeRoomHistory as $history)
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <strong>
                                    #{{ $history->phongCu->ma_phong ?? '' }}
                                    <i class="bi bi-arrow-right mx-2"></i>
                                    #{{ $history->phongMoi->ma_phong ?? '' }}
                                </strong>
                                <div class="small text-muted">
                                    {{ $history->created_at->format('d/m/Y H:i') }}
                                    @if ($history->loai == 'nang_cap')
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
                                @if ($diff > 0)
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

                    @if (abs($totalRoomChangeDiff ?? 0) > 0)
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

                @if (abs($totalRoomChangeDiff ?? 0) > 0)
                    <div class="d-flex justify-content-between">
                        <div>Chênh lệch đổi phòng</div>
                        <div class="{{ ($totalRoomChangeDiff ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                            {{ ($totalRoomChangeDiff ?? 0) > 0 ? '+' : '' }}
                            {{ number_format($totalRoomChangeDiff ?? 0, 0) }} ₫
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
                    <div>
                        {{ number_format(($roomSnapshot ?? $roomsTotal) + ($totalRoomChangeDiff ?? 0) + ($extrasTotal ?? 0) - ($discount ?? 0), 0) }}
                        ₫</div>
                </div>

                <div class="d-flex justify-content-between">
                    <div>Đã thu trước</div>
                    <div>- {{ number_format($roomsTotal ?? 0, 0) }} ₫</div>
                </div>

                <div class="d-flex justify-content-between fw-semibold border-top pt-2 mt-2">
                    <div>Còn phải thanh toán</div>
                    <div
                        class="{{ ($actualAmountToPay ?? 0) > 0 ? 'text-danger' : (($actualAmountToPay ?? 0) < 0 ? 'text-success' : '') }}">
                        {{ number_format($actualAmountToPay ?? 0, 0) }} ₫
                        @if (($actualAmountToPay ?? 0) < 0)
                            <small>(Hoàn lại)</small>
                        @endif
                    </div>
                </div>

                {{-- Chi tiết cách tính --}}
                <div class="mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="collapse"
                        data-bs-target="#totalCalculationDetail" aria-expanded="false">
                        <i class="bi bi-calculator me-1"></i> Xem chi tiết cách tính
                    </button>
                    <div class="collapse mt-2" id="totalCalculationDetail">
                        <div class="card card-body bg-light">
                            <h6 class="mb-3"><i class="bi bi-calculator me-2"></i>Chi tiết tính toán tổng thanh toán
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <h6 class="text-primary mb-2"> Tổng phòng ban đầu</h6>
                                        <div class="small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Giá phòng ban đầu:</span>
                                                <strong>{{ number_format($roomSnapshot ?? $roomsTotal, 0) }} ₫</strong>
                                            </div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                (Giá tại thời điểm đặt phòng)
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if (abs($totalRoomChangeDiff ?? 0) > 0)
                                    <div class="col-md-6">
                                        <div class="border rounded p-3">
                                            <h6
                                                class="{{ ($totalRoomChangeDiff ?? 0) > 0 ? 'text-danger' : 'text-success' }} mb-2">
                                                Chênh lệch đổi phòng
                                            </h6>
                                            <div class="small">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span>Tổng chênh lệch:</span>
                                                    <strong
                                                        class="{{ ($totalRoomChangeDiff ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ ($totalRoomChangeDiff ?? 0) > 0 ? '+' : '' }}{{ number_format($totalRoomChangeDiff ?? 0, 0) }}
                                                        ₫
                                                    </strong>
                                                </div>
                                                @if (!empty($changeRoomHistory) && $changeRoomHistory->count() > 0)
                                                    <div class="text-muted mt-2" style="font-size: 11px;">
                                                        @foreach ($changeRoomHistory as $hist)
                                                            <div>
                                                                {{ $hist->phongCu->ma_phong ?? '' }} →
                                                                {{ $hist->phongMoi->ma_phong ?? '' }}:
                                                                {{ number_format($hist->gia_moi - $hist->gia_cu, 0) }} ₫
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <h6 class="text-warning mb-2"> Phát sinh (chưa thanh toán)</h6>
                                        <div class="small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Tổng phát sinh:</span>
                                                <strong>{{ number_format($extrasTotal ?? 0, 0) }} ₫</strong>
                                            </div>
                                            @if (!empty($extrasItems) && count($extrasItems) > 0)
                                                <div class="text-muted mt-2" style="font-size: 11px;">
                                                    @foreach ($extrasItems as $ei)
                                                        <div>
                                                            {{ $ei['name'] }} (x{{ $ei['quantity'] }}):
                                                            {{ number_format($ei['amount'], 0) }} ₫
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-muted" style="font-size: 11px;">Không có phát sinh</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <h6 class="text-success mb-2">4. Giảm giá</h6>
                                        <div class="small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Tổng giảm giá:</span>
                                                <strong class="text-success">- {{ number_format($discount ?? 0, 0) }}
                                                    ₫</strong>
                                            </div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                @if (($discount ?? 0) > 0)
                                                    (Voucher/khuyến mãi)
                                                @else
                                                    Không có giảm giá
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}

                                <div class="col-md-12">
                                    <div class="border rounded p-3 bg-white">
                                        <h6 class="mb-3">Tổng hợp</h6>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Tổng phòng (ban đầu):</span>
                                                    <strong>{{ number_format($roomSnapshot ?? $roomsTotal, 0) }} ₫</strong>
                                                </div>
                                                @if (abs($totalRoomChangeDiff ?? 0) > 0)
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Chênh lệch đổi phòng:</span>
                                                        <strong
                                                            class="{{ ($totalRoomChangeDiff ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                                            {{ ($totalRoomChangeDiff ?? 0) > 0 ? '+' : '' }}{{ number_format($totalRoomChangeDiff ?? 0, 0) }}
                                                            ₫
                                                        </strong>
                                                    </div>
                                                @endif
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Phát sinh:</span>
                                                    <strong>+ {{ number_format($extrasTotal ?? 0, 0) }} ₫</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Giảm giá:</span>
                                                    <strong class="text-success">- {{ number_format($discount ?? 0, 0) }}
                                                        ₫</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2 pt-2 border-top">
                                                    <span><strong>Tổng:</strong></span>
                                                    <strong>{{ number_format(($roomSnapshot ?? $roomsTotal) + ($totalRoomChangeDiff ?? 0) + ($extrasTotal ?? 0) - ($discount ?? 0), 0) }}
                                                        ₫</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Đã thu trước:</span>
                                                    <strong>- {{ number_format($roomsTotal ?? 0, 0) }} ₫</strong>
                                                </div>
                                                <div class="d-flex justify-content-between pt-2 border-top fw-bold fs-5">
                                                    <span class="text-primary">Còn phải thanh toán:</span>
                                                    <strong
                                                        class="{{ ($actualAmountToPay ?? 0) > 0 ? 'text-danger' : (($actualAmountToPay ?? 0) < 0 ? 'text-success' : '') }}">
                                                        {{ number_format($actualAmountToPay ?? 0, 0) }} ₫
                                                    </strong>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="bg-light p-2 rounded">
                                                    <small class="text-muted">
                                                        <strong>Công thức:</strong><br>
                                                        Tổng = Tổng phòng + Chênh lệch đổi phòng + Phát sinh - Giảm giá<br>
                                                        Còn phải thanh toán = Tổng - Đã thu trước<br><br>
                                                        <strong>Trong trường hợp này:</strong><br>
                                                        = {{ number_format($roomSnapshot ?? $roomsTotal, 0) }}
                                                        @if (abs($totalRoomChangeDiff ?? 0) > 0)
                                                            {{ ($totalRoomChangeDiff ?? 0) > 0 ? '+' : '' }}{{ number_format($totalRoomChangeDiff ?? 0, 0) }}
                                                        @endif
                                                        + {{ number_format($extrasTotal ?? 0, 0) }}
                                                        @if (($discount ?? 0) > 0)
                                                            - {{ number_format($discount ?? 0, 0) }}
                                                        @endif
                                                        - {{ number_format($roomsTotal ?? 0, 0) }}<br>
                                                        = {{ number_format($actualAmountToPay ?? 0, 0) }} ₫
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                <div class="small text-muted">Số tiền: {{ number_format($hd->tong_thuc_thu, 0) }} ₫ —
                                    Trạng
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
                            <input class="form-check-input" type="checkbox" id="mark_paid" name="mark_paid"
                                value="1">
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

                            <div class="small text-muted mt-2 border-top pt-2">
                                <strong>Chi tiết tính toán:</strong><br>
                                @php
                                    $earlyRefundCalc = round(0.5 * $dailyTotal * ($earlyDays ?? 0), 0);
                                    $calculatedEarlyNet =
                                        $earlyRefundCalc - ($extrasTotal ?? 0) - ($totalRoomChangeDiff ?? 0);
                                @endphp
                                • Khoản hoàn checkout sớm: 50% × {{ number_format($dailyTotal, 0) }} ₫/đêm ×
                                {{ $earlyDays ?? 0 }} ngày = <strong>{{ number_format($earlyRefundCalc, 0) }}
                                    ₫</strong><br>
                                @if (($extrasTotal ?? 0) > 0)
                                    • Trừ phát sinh: <strong>- {{ number_format($extrasTotal ?? 0, 0) }} ₫</strong><br>
                                @endif
                                @if (($totalRoomChangeDiff ?? 0) != 0)
                                    • Trừ chênh lệch đổi phòng: <strong>-
                                        {{ number_format($totalRoomChangeDiff ?? 0, 0) }} ₫</strong><br>
                                @endif
                                • <strong>Kết quả: {{ number_format($earlyRefundCalc, 0) }} ₫
                                    @if (($extrasTotal ?? 0) > 0)
                                        - {{ number_format($extrasTotal ?? 0, 0) }} ₫
                                    @endif
                                    @if (($totalRoomChangeDiff ?? 0) != 0)
                                        - {{ number_format($totalRoomChangeDiff ?? 0, 0) }} ₫
                                    @endif
                                    = {{ number_format($calculatedEarlyNet, 0) }} ₫
                                </strong>
                                @if (abs($calculatedEarlyNet - $earlyNetDisplay) > 0)
                                    <br><span class="text-warning">(Làm tròn: {{ number_format($calculatedEarlyNet, 0) }}
                                        ₫ → {{ number_format($earlyNetDisplay, 0) }} ₫)</span>
                                @endif
                            </div>
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
                            <div class="small text-muted mt-1">
                                <strong>Chi tiết tính toán:</strong><br>
                                @php
                                    $perHour = $dailyTotal / 24.0;
                                    $lateFeeOnly = round($perHour * ($lateHoursFull ?? 0), 0);
                                    $calculatedTotal = $lateFeeOnly + ($extrasTotal ?? 0) + ($totalRoomChangeDiff ?? 0);
                                @endphp
                                • Phí checkout muộn: {{ number_format(round($perHour, 0), 0) }} ₫/giờ ×
                                {{ $lateHoursFull ?? 0 }} giờ = <strong>{{ number_format($lateFeeOnly, 0) }}
                                    ₫</strong><br>
                                @if (($extrasTotal ?? 0) > 0)
                                    • Phát sinh: <strong>{{ number_format($extrasTotal ?? 0, 0) }} ₫</strong><br>
                                @endif
                                @if (($totalRoomChangeDiff ?? 0) != 0)
                                    • Chênh lệch đổi phòng: <strong>{{ number_format($totalRoomChangeDiff ?? 0, 0) }}
                                        ₫</strong><br>
                                @endif
                                • <strong>Tổng: {{ number_format($lateFeeOnly, 0) }} ₫
                                    @if (($extrasTotal ?? 0) > 0)
                                        + {{ number_format($extrasTotal ?? 0, 0) }} ₫
                                    @endif
                                    @if (($totalRoomChangeDiff ?? 0) != 0)
                                        + {{ number_format($totalRoomChangeDiff ?? 0, 0) }} ₫
                                    @endif
                                    = {{ number_format($calculatedTotal, 0) }} ₫
                                </strong>
                                @if (abs($calculatedTotal - $lateNetDisplay) > 0)
                                    <br><span class="text-warning">(Làm tròn: {{ number_format($calculatedTotal, 0) }} ₫
                                        → {{ number_format($lateNetDisplay, 0) }} ₫)</span>
                                @endif
                            </div>
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
                                {{ $blockingNextBookingStart
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
                    // Normal checkout (on time) needs payment if there are extras or room change diff
                    elseif (empty($earlyEligible) && empty($lateEligible)) {
                        // Tính tổng số tiền cần thu (bao gồm cả chênh lệch đổi phòng)
                        $totalAmountToPay = ($extrasTotal ?? 0) + ($totalRoomChangeDiff ?? 0);
                        if ($totalAmountToPay > 0) {
                            $needPayment = true;
                            $paymentAmount = $totalAmountToPay;
                        }
                    }
                @endphp

                @if ($needPayment && $paymentAmount > 0)
                    <div class="card mb-3 border-primary">
                        <div class="card-header bg-primary bg-opacity-10">
                            <strong><i class="bi bi-credit-card me-2"></i>Phương thức thanh toán</strong>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                Số tiền cần thu: <strong class="text-danger">{{ number_format($paymentAmount, 0) }}
                                    ₫</strong>
                            </div>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="payment_method" id="pay_cash"
                                    value="cash" checked>
                                <label class="btn btn-outline-dark" for="pay_cash">
                                    <i class="bi bi-cash-stack"></i> Tiền mặt
                                </label>

                                <input type="radio" class="btn-check" name="payment_method" id="pay_vnpay"
                                    value="vnpay">
                                <label class="btn btn-outline-primary" for="pay_vnpay">
                                    <i class="bi bi-credit-card"></i> VNPay
                                </label>

                                <input type="radio" class="btn-check" name="payment_method" id="pay_momo"
                                    value="momo">
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
                const form = document.querySelector(
                    'form[action="{{ route('staff.bookings.checkout.process', $booking->id) }}"]');
                if (!form) return;

                const checkoutButtons = form.querySelectorAll('button[type="submit"]');

                checkoutButtons.forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        const paymentMethod = document.querySelector(
                            'input[name="payment_method"]:checked')?.value;

                        if (paymentMethod && paymentMethod !== 'cash') {
                            e.preventDefault();

                            const amount = {{ $paymentAmount ?? 0 }};
                            const action = btn.value || 'checkout';

                            console.log('=== Payment Init ===', {
                                paymentMethod,
                                action,
                                amount
                            });

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
                                        alert('Lỗi: ' + (data.message ||
                                            'Không thể tạo thanh toán'));
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
