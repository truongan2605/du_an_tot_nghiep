@extends('layouts.app')

@section('title', 'Booking Detail')

@section('content')
    <section class="pt-3">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card border mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0">{{ $booking->ma_tham_chieu }}</h4>
                                <div class="text-muted small">Created:
                                    {{ optional($booking->created_at)->format('d M Y H:i') }}</div>
                            </div>

                            <div class="text-end">
                                @php
                                    $statusMap = [
                                        'dang_cho' => ['label' => 'Pending', 'class' => 'text-warning'],
                                        'da_xac_nhan' => ['label' => 'Confirmed', 'class' => 'text-primary'],
                                        'da_huy' => ['label' => 'Cancelled', 'class' => 'text-danger'],
                                        'hoan_thanh' => ['label' => 'Completed', 'class' => 'text-success'],
                                    ];
                                    $s = $statusMap[$booking->trang_thai] ?? [
                                        'label' => ucfirst(str_replace('_', ' ', $booking->trang_thai)),
                                        'class' => 'text-secondary',
                                    ];
                                @endphp
                                <div class="d-flex flex-column align-items-end">
                                    <div class="fw-bold" style="font-size:1.25rem;">
                                        <span class="{{ $s['class'] }}">{{ $s['label'] }}</span>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('account.booking.index') }}"
                                            class="btn btn-outline-secondary">Back to bookings</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Check in</strong>
                                    <div>{{ optional($booking->ngay_nhan_phong)->format('D, d M Y') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <strong>Check out</strong>
                                    <div>{{ optional($booking->ngay_tra_phong)->format('D, d M Y') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <strong>Total</strong>
                                    <div>
                                        {{ number_format($booking->snapshot_total ?? ($booking->tong_tien ?? 0), 0, ',', '.') }}
                                        VND</div>
                                </div>
                            </div>

                            <h5 class="mb-2">Rooms</h5>

                            @if ($booking->datPhongItems && $booking->datPhongItems->count())
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0 table-no-bottom">
                                        <thead>
                                            <tr>
                                                <th>Room name</th>
                                                <th class="text-end">Price/night</th>
                                                <th class="text-end">Nights</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($booking->datPhongItems as $it)
                                                @php
                                                    $roomName =
                                                        $it->phong && isset($it->phong->name)
                                                            ? $it->phong->name
                                                            : ($it->loai_phong && isset($it->loai_phong->name)
                                                                ? $it->loai_phong->name
                                                                : 'Room ' . ($it->phong_id ?? 'N/A'));
                                                    $pricePer = $it->gia_tren_dem ?? 0;
                                                    $nights = $it->so_dem ?? 1;
                                                    $qty = $it->so_luong ?? 1;
                                                    $subtotal = $it->tong_item ?? $pricePer * $nights * $qty;
                                                @endphp
                                                <tr>
                                                    <td class="align-middle">
                                                        <i class="bi bi-door-open-fill me-2"></i>
                                                        <strong>{{ $roomName }}</strong>
                                                        <div class="small text-muted">{{ $qty }} x room(s)</div>
                                                    </td>
                                                    <td class="text-end align-middle">
                                                        {{ number_format($pricePer, 0, ',', '.') }}</td>
                                                    <td class="text-end align-middle">{{ $nights }}</td>
                                                    <td class="text-end align-middle">
                                                        {{ number_format($subtotal, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">No room items recorded.</div>
                            @endif

                            <div class="my-4"></div> {{-- Do not delete this div --}}

                            {{-- Addons --}}
                            @if ($booking->datPhongAddons && $booking->datPhongAddons->count())
                                <ul class="list-unstyled">
                                    @foreach ($booking->datPhongAddons as $a)
                                        <li>
                                            <i class="bi bi-plus-circle me-2"></i>
                                            {{ $a->name ?? 'Addon' }} — {{ number_format($a->price ?? 0, 0, ',', '.') }}
                                            VND
                                            <small class="text-muted"> (qty: {{ $a->qty ?? 1 }})</small>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-muted small">No addons recorded.</div>
                            @endif

                            <hr>

                            <h5 class="mb-2">Overview</h5>
                            <div class="row g-3 overview">
                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-people-fill icon-uniform me-3" aria-hidden="true"></i>
                                            <div>
                                                <div class="small text-muted">Adults</div>
                                                <div class="fw-bold">
                                                    {{ $meta['adults_input'] ?? ($meta['computed_adults'] ?? ($meta['guests_adults'] ?? 0)) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-child icon-uniform me-3" aria-hidden="true"></i>
                                            <div>
                                                <div class="small text-muted">Children</div>
                                                <div class="fw-bold">
                                                    {{ $meta['children_input'] ?? ($meta['chargeable_children'] ?? 0) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-check icon-uniform me-3" aria-hidden="true"></i>
                                            <div>
                                                <div class="small text-muted">Nights</div>
                                                <div class="fw-bold">
                                                    {{ $meta['nights'] ?? ($booking->datPhongItems->first()->so_dem ?? 1) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="p-3 border rounded">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-currency-dollar icon-uniform me-3" aria-hidden="true"></i>
                                            <div>
                                                <div class="small text-muted">Total</div>
                                                <div class="fw-bold">
                                                    {{ number_format($booking->snapshot_total ?? ($booking->tong_tien ?? 0), 0, ',', '.') }}
                                                    VND</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>



                        </div>
                    </div>
                </div>
                <!-- Main column END -->
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <style>
        .icon-uniform {
            font-size: 1.975rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.8rem;
            height: 1.8rem;
        }

        .icon-uniform svg {
            width: 1em;
            height: 1em;
        }

        .icon-uniform.fa-solid {
            font-weight: 900;
        }

        @media (max-width: 576px) {
            .icon-uniform {
                font-size: 1.125rem;
                width: 1.5rem;
                height: 1.5rem;
            }
        }

        .table-no-bottom tbody tr:last-child td {
            border-bottom: 0 !important;
        }
    </style>
@endpush
