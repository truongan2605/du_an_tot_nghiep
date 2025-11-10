@extends('layouts.app')

@section('title', 'Booking Detail')

@section('content')
    <section class="py-3">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    {{-- Booking Header --}}
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-0 p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            {{-- Left: Booking Info --}}
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-receipt fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-dark">{{ $booking->ma_tham_chieu }}</h5>
                                    <div class="text-muted small">Created: {{ optional($booking->created_at)->format('d M Y H:i') }}</div>
                                </div>
                            </div>

                            {{-- Right: Status + Actions --}}
                            <div class="text-end d-flex flex-column align-items-end gap-2">
                                @php
                                    $statusMap = [
                                        'dang_cho' => ['label' => 'Pending', 'class' => 'bg-warning text-dark border-warning'],
                                        'dang_cho_xac_nhan' => ['label' => 'Pending', 'class' => 'bg-warning text-dark border-warning'],
                                         'dang_su_dung' => ['label' => 'Đang Sử Dụng', 'class' => 'bg-warning text-dark border-warning'],
                                        'da_xac_nhan' => ['label' => 'Confirmed', 'class' => 'bg-primary text-white border-primary'],
                                        'da_huy' => ['label' => 'Cancelled', 'class' => 'bg-danger text-white border-danger'],
                                        'hoan_thanh' => ['label' => 'Completed', 'class' => 'bg-success text-white border-success'],
                                    ];
                                    $s = $statusMap[$booking->trang_thai] ?? [
                                        'label' => ucfirst(str_replace('_', ' ', $booking->trang_thai)),
                                        'class' => 'bg-secondary text-white border-secondary',
                                    ];
                                @endphp

                                {{-- Status Badge --}}
                                <span class="badge px-3 py-2 fs-6 fw-semibold {{ $s['class'] }}">{{ $s['label'] }}</span>

                                {{-- Action Buttons --}}
                                <div class="d-flex gap-1">
                                    <a href="{{ route('account.booking.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                                        <i class="bi bi-arrow-left me-1"></i> Back
                                    </a>

                                    @if (in_array($booking->trang_thai, ['dang_cho', 'dang_cho_xac_nhan', 'da_xac_nhan']))
                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn hủy đặt phòng này không?')">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm px-3">
                                                <i class="bi bi-x-circle me-1"></i> Cancel
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Booking Body --}}
                        <div class="card-body p-4">
                            {{-- Dates & Total --}}
                            <div class="row mb-4 g-3">
                                <div class="col-md-4">
                                    <div class="text-center text-md-start">
                                        <div class="text-muted small mb-1"><i class="bi bi-calendar-check me-1"></i> Check-in</div>
                                        <div class="h6 mb-0 fw-bold">{{ optional($booking->ngay_nhan_phong)->format('D, d M Y') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center text-md-start">
                                        <div class="text-muted small mb-1"><i class="bi bi-calendar-x me-1"></i> Check-out</div>
                                        <div class="h6 mb-0 fw-bold">{{ optional($booking->ngay_tra_phong)->format('D, d M Y') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center text-md-end">
                                        <div class="text-muted small mb-1"><i class="bi bi-currency-dollar me-1"></i> Total</div>
                                        <div class="h5 mb-0 fw-bold text-primary">{{ number_format($booking->snapshot_total ?? ($booking->tong_tien ?? 0), 0, ',', '.') }} VND</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Rooms Table --}}
                            <div class="mb-4">
                                <h6 class="mb-3 d-flex align-items-center"><i class="bi bi-door-open-fill me-2 text-primary"></i> Rooms</h6>
                                @if ($booking->datPhongItems && $booking->datPhongItems->count())
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Room</th>
                                                    <th class="text-end">Price/Night</th>
                                                    <th class="text-end">Nights</th>
                                                    <th class="text-end">Subtotal</th>
                                                    <th class="text-end">Deposit (20%)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($booking->datPhongItems as $it)
                                                    @php
                                                        $roomName = $it->phong->name ?? ($it->loai_phong->name ?? 'Room ' . ($it->phong_id ?? 'N/A'));
                                                        $pricePer = $it->gia_tren_dem ?? 0;
                                                        $nights = $it->so_dem ?? 1;
                                                        $qty = $it->so_luong ?? 1;
                                                        $subtotal = $it->tong_item ?? $pricePer * $nights * $qty;
                                                        $deposit_amount = (float) ($it->datPhong->deposit_amount ?? 0);
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <i class="bi bi-door-closed-fill text-muted me-2"></i>
                                                                <div>
                                                                    <strong class="text-dark">{{ $roomName }}</strong>
                                                                    <div class="small text-muted">{{ $qty }} room{{ $qty > 1 ? 's' : '' }}</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-end text-muted small">{{ number_format($pricePer, 0, ',', '.') }} VND</td>
                                                        <td class="text-end fw-semibold">{{ $nights }}</td>
                                                        <td class="text-end fw-semibold text-primary">{{ number_format($subtotal, 0, ',', '.') }} VND</td>
                                                        <td class="text-end text-success fw-semibold">{{ number_format($deposit_amount, 0, ',', '.') }} VND</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info d-flex align-items-center py-2">
                                        <i class="bi bi-info-circle me-2"></i> No room items recorded.
                                    </div>
                                @endif
                            </div>

                            {{-- Addons --}}
                            @if ($booking->datPhongAddons && $booking->datPhongAddons->count())
                                <div class="mb-4">
                                    <h6 class="mb-3 d-flex align-items-center"><i class="bi bi-plus-circle-fill me-2 text-info"></i> Add-ons</h6>
                                    <div class="row g-2">
                                        @foreach ($booking->datPhongAddons as $a)
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-tag-fill text-info me-2"></i>
                                                        <span class="fw-semibold">{{ $a->name ?? 'Addon' }}</span>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-bold text-primary">{{ number_format($a->price ?? 0, 0, ',', '.') }} VND</div>
                                                        <small class="text-muted">Qty: {{ $a->qty ?? 1 }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Overview --}}
                            <hr class="my-4">
                            <h6 class="mb-3 d-flex align-items-center"><i class="bi bi-list-check me-2 text-success"></i> Overview</h6>
                            <div class="row g-3 mb-4">
                                @php
                                    $adults = $meta['adults_input'] ?? ($meta['computed_adults'] ?? ($meta['guests_adults'] ?? 0));
                                    $children = $meta['children_input'] ?? ($meta['chargeable_children'] ?? 0);
                                    $nights = $meta['nights'] ?? ($booking->datPhongItems->first()->so_dem ?? 1);
                                    $total = $booking->snapshot_total ?? ($booking->tong_tien ?? 0);
                                @endphp
                                <div class="col-md-3 col-6">
                                    <div class="card border-0 bg-light h-100 text-center p-3">
                                        <i class="bi bi-people-fill text-primary fs-2 mb-2 d-block"></i>
                                        <div class="small text-muted">Adults</div>
                                        <div class="h6 fw-bold text-dark">{{ $adults }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="card border-0 bg-light h-100 text-center p-3">
                                        <i class="bi bi-person-bounding-box text-info fs-2 mb-2 d-block"></i>
                                        <div class="small text-muted">Children</div>
                                        <div class="h6 fw-bold text-dark">{{ $children }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="card border-0 bg-light h-100 text-center p-3">
                                        <i class="bi bi-calendar3-event text-warning fs-2 mb-2 d-block"></i>
                                        <div class="small text-muted">Nights</div>
                                        <div class="h6 fw-bold text-dark">{{ $nights }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="card border-0 bg-light h-100 text-center p-3">
                                        <i class="bi bi-currency-dollar text-success fs-2 mb-2 d-block"></i>
                                        <div class="small text-muted">Total</div>
                                        <div class="h6 fw-bold text-primary">{{ number_format($total, 0, ',', '.') }} VND</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Refund Policy --}}
                            <hr class="my-4">
                            <h6 class="mb-3 d-flex align-items-center"><i class="bi bi-shield-check me-2 text-secondary"></i> Refund Policy</h6>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="alert alert-success border-0 p-3">
                                        <i class="bi bi-check-circle-fill me-2"></i>
                                        <strong>Within 24 hours:</strong> 100% deposit refund.
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="alert alert-warning border-0 p-3">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <strong>After 24 hours:</strong> 50% deposit refund.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .card { border-radius: 12px; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.03); }
        .badge { min-width: 100px; }
        @media (max-width: 768px) {
            .card-header { flex-direction: column; align-items: stretch !important; text-align: center; }
            .card-header .text-end { text-align: center !important; }
        }
    </style>
@endpush