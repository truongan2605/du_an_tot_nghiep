@extends('layouts.admin')

@section('title', 'Chi tiết phòng')

@section('content')
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-lg border-0 rounded-3">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Chi tiết phòng: {{ $phong->ma_phong }}</h4>
                <a href="{{ route('admin.phong.index') }}" class="btn btn-light btn-sm">← Quay lại</a>
            </div>

            <div class="card-body">
                <div class="row">
                    <!-- Thông tin chính -->
                    <div class="col-md-6">
                        <h5 class="fw-bold">Thông tin chung</h5>
                        <table class="table table-bordered align-middle">
                            <tr>
                                <th>Mã phòng</th>
                                <td>{{ $phong->ma_phong }}</td>
                            </tr>
                            <tr>
                                <th>Tên phòng</th>
                                <td>{{ $phong->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Mô tả</th>
                                <td>{!! nl2br(e($phong->mo_ta ?? '-')) !!}</td>
                            </tr>

                            <tr>
                                <th>Loại phòng</th>
                                <td>
                                    {{ $phong->loaiPhong?->ten ?? '-' }}
                                    @if ($phong->loaiPhong)
                                        <div class="small text-muted">Giá loại:
                                            <strong>{{ number_format($phong->loaiPhong->gia_mac_dinh ?? 0, 0, ',', '.') }}
                                                đ</strong>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Tầng</th>
                                <td>{{ $phong->tang?->ten }}</td>
                            </tr>
                            <tr>
                                <th>Sức chứa</th>
                                <td>{{ $phong->suc_chua }} người</td>
                            </tr>
                            <tr>
                                <th>Số giường</th>
                                <td>{{ $phong->so_giuong }}</td>
                            </tr>
                            <tr>
                                <th>Giá mặc định (phòng)</th>
                                <td>{{ number_format($phong->gia_mac_dinh, 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <th>Trạng thái</th>
                                <td>
                                    @php
                                        $map = [
                                            'khong_su_dung' => ['label' => 'Không sử dụng', 'class' => 'secondary'],
                                            'trong' => ['label' => 'Trống', 'class' => 'success'],
                                            'dang_o' => ['label' => 'Đang ở', 'class' => 'primary'],
                                            'bao_tri' => ['label' => 'Bảo trì', 'class' => 'warning'],
                                        ];
                                        $st = $map[$phong->trang_thai] ?? [
                                            'label' => $phong->trang_thai,
                                            'class' => 'secondary',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $st['class'] }}">{{ $st['label'] }}</span>
                                </td>
                            </tr>

                        </table>

                        @php
                            $typePrice = (float) ($phong->loaiPhong->gia_mac_dinh ?? 0);
                            $typeAmenities = $phong->loaiPhong?->tienNghis ?? collect();
                            $roomAmenitiesAll = $phong->tienNghis ?? collect();

                            $typeAmenitiesSum = $typeAmenities->sum('gia');

                            $boSung = $roomAmenitiesAll->reject(function ($item) use ($typeAmenities) {
                                return $typeAmenities->contains('id', $item->id);
                            });
                            $boSungSum = $boSung->sum('gia');

                            $allIds = array_values(
                                array_unique(
                                    array_merge(
                                        $typeAmenities->pluck('id')->toArray(),
                                        $roomAmenitiesAll->pluck('id')->toArray(),
                                    ),
                                ),
                            );
                            $allAmenitiesSum = \App\Models\TienNghi::whereIn('id', $allIds)->sum('gia');

                            $bedTotal = 0;
                            if ($phong->relationLoaded('bedTypes') || $phong->bedTypes()->exists()) {
                                $bts = $phong->bedTypes()->get();
                                foreach ($bts as $bt) {
                                    $qty = (int) ($bt->pivot->quantity ?? 0);
                                    $pricePer =
                                        $bt->pivot->price !== null
                                            ? (float) $bt->pivot->price
                                            : (float) ($bt->price ?? 0);
                                    $bedTotal += $qty * $pricePer;
                                }
                            }

                            $totalDisplay = $phong->tong_gia;
                        @endphp


                        <div class="mt-3">
                            <h5 class="fw-bold">Cấu hình giường</h5>
                            @if ($phong->bedTypes->count())
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Loại giường</th>
                                            <th class="text-center">Số lượng</th>
                                            <th class="text-center">Sức chứa/giường</th>
                                            <th class="text-end">Giá/giường</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($phong->bedTypes as $bt)
                                            <tr>
                                                <td>{{ $bt->name }}</td>
                                                <td class="text-center">{{ $bt->pivot->quantity ?? 0 }}</td>
                                                <td class="text-center">{{ $bt->capacity }}</td>
                                                <td class="text-end">
                                                    {{ number_format($bt->pivot->price ?? ($bt->price ?? 0), 0, ',', '.') }}
                                                    đ
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p><em>Không có cấu hình giường</em></p>
                            @endif
                        </div>


                        <div class="card mt-3">
                            <div class="card-header">Phân tích giá</div>
                            <div class="card-body p-2">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td>Giá loại phòng</td>
                                        <td class="text-end"><strong>{{ number_format($typePrice, 0, ',', '.') }}
                                                đ</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Tổng dịch vụ mặc định</td>
                                        <td class="text-end">{{ number_format($typeAmenitiesSum, 0, ',', '.') }} đ</td>
                                    </tr>
                                    <tr class="table-active">
                                        <td>Tổng giá giường</td>
                                        <td class="text-end">{{ number_format($bedTotal, 0, ',', '.') }} đ</td>
                                    </tr>

                                    <tr>
                                        <th>Tổng giá phòng (hiện tại)</th>
                                        <th class="text-end text-success">{{ number_format($totalDisplay, 0, ',', '.') }} đ
                                        </th>
                                    </tr>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- Ảnh -->
                    <div class="col-md-6">
                        <h5 class="fw-bold">Hình ảnh</h5>
                        @if ($phong->images->count())
                            <div class="row g-2">
                                @foreach ($phong->images as $img)
                                    <div class="col-6 col-md-4">
                                        <div class="border rounded shadow-sm">
                                            <img src="{{ asset('storage/' . $img->image_path) }}" class="img-fluid rounded"
                                                style="object-fit: contain; max-height: 180px; width: 100%;">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p><em>Chưa có ảnh</em></p>
                        @endif
                    </div>
                </div>

                <!-- Tiện nghi -->
                <div class="mt-4">
                    <h5 class="fw-bold">Dịch vụ</h5>

                    <div class="row">
                        <div class="col-12">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">Dịch vụ</div>
                                <div class="card-body">
                                    @if ($tienNghiLoaiPhong->count())
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($tienNghiLoaiPhong as $tn)
                                                @php
                                                    $giaHienThi = $tn->pivot->price ?? ($tn->gia ?? 0);
                                                    $coGiaRieng =
                                                        $tn->pivot->price !== null && $tn->pivot->price != $tn->gia;
                                                @endphp
                                                <li>
                                                    {{ $tn->ten }}
                                                    <small class="text-muted"> —
                                                        @if ($coGiaRieng)
                                                            <del>{{ number_format($tn->gia, 0, ',', '.') }} đ</del>
                                                            <span class="text-success fw-bold">
                                                                → {{ number_format($giaHienThi, 0, ',', '.') }} đ
                                                            </span>
                                                            <span class="badge bg-success text-white ms-1">giá riêng</span>
                                                        @else
                                                            <span
                                                                class="text-success">{{ number_format($giaHienThi, 0, ',', '.') }}
                                                                đ</span>
                                                        @endif
                                                    </small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p><em>Không có dịch vụ mặc định</em></p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vật dụng -->
                <div class="mt-4">
                    <h5 class="fw-bold">Vật dụng</h5>
                    <div class="row gy-3">
                        <!-- Đồ vật (do_dung) — danh sách theo loại phòng -->
                        <div class="col-md-6">
                            <div class="card border-dark">
                                <div
                                    class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                    <div>Đồ vật</div>
                                    <a href="{{ route('admin.phong.vatdung.instances.index', ['phong' => $phong->id]) }}"
                                        class="btn btn-sm btn-light">Quản lý chi tiết</a>
                                </div>
                                <div class="card-body">
                                    @if ($vatDungLoaiPhongDoDung->count())
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($vatDungLoaiPhongDoDung as $vd)
                                                @php
                                                    $inst = $instancesMap[$vd->id] ?? null;
                                                    $instCount = $inst['count'] ?? 0;
                                                    $activeBooking = $activeDatPhong ?? null;
                                                    $canOperate =
                                                        $activeBooking &&
                                                        in_array($activeBooking->trang_thai, [
                                                            'da_dat',
                                                            'dang_su_dung',
                                                            'da_xac_nhan',
                                                            'dang_cho_xac_nhan',
                                                        ]);
                                                @endphp

                                                <li class="mb-2">
                                                    ✔ {{ $vd->ten }}
                                                    <small class="text-muted"> —
                                                        {{ number_format($vd->gia ?? 0, 0, ',', '.') }} đ</small>
                                                    @if ($instCount > 0)
                                                        <span class="badge bg-info ms-2">Số lượng:
                                                            {{ $instCount }}</span>
                                                    @else
                                                        <span class="text-muted ms-2">Không có bản thể</span>
                                                    @endif


                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p><em>Không có đồ vật mặc định cho loại phòng này.</em></p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Đồ ăn (do_an) trong phòng + số lượng hiện tại -->
                        {{-- <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">Đồ ăn</div>
                                <div class="card-body">
                                    @if ($vatPhongDoAn->count())
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Tên</th>
                                                    <th class="text-center">Vật phẩm ban đầu</th>
                                                    <th class="text-center">Vật phẩm đã tiêu thụ (hóa đơn)</th>
                                                    <th class="text-center">Còn lại</th>
                                                    <th class="text-center">Đánh dấu đã tiêu thụ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($vatPhongDoAn as $vd)
                                                    @php
                                                        $vid = (int) $vd->id;
                                                        $initial = (int) ($initialReservations[$vid] ?? 0);
                                                        $consumedByInvoice = (int) ($hoaDonItemsGrouped[$vid] ?? 0);
                                                        $remaining = max(0, $initial - $consumedByInvoice);
                                                    @endphp
                                                    <tr>
                                                        @php
                                                            $vid = (int) $vd->id;
                                                            $initial = (int) ($initialReservations[$vid] ?? 0); // from controller
                                                            $consumedByInvoice = (int) ($hoaDonItemsGrouped[$vid] ?? 0); // from controller
                                                            $remaining = max(0, $initial - $consumedByInvoice);
                                                        @endphp
                                                        <td>{{ $vd->ten }}</td>
                                                        <td class="text-center">{{ $initial }}</td>
                                                        <td class="text-center">{{ $consumedByInvoice }}</td>
                                                        <td class="text-center">{{ $remaining }}</td>
                                                        <td class="text-center">
                                                            @if (!empty($activeDatPhong))
                                                                <form
                                                                    action="{{ route('admin.phong.consumptions.store_and_bill', ['phong' => $phong->id]) }}"
                                                                    method="POST" class="d-inline consume-form">
                                                                    @csrf
                                                                    <input type="hidden" name="dat_phong_id"
                                                                        value="{{ $activeDatPhong->id }}">
                                                                    <input type="hidden" name="phong_id"
                                                                        value="{{ $phong->id }}">
                                                                    <input type="hidden" name="vat_dung_id"
                                                                        value="{{ $vd->id }}">
                                                                    <div class="input-group input-group-sm">
                                                                        <input type="number" name="quantity"
                                                                            min="1" max="{{ $remaining }}"
                                                                            class="form-control form-control-sm qty-to-consume"
                                                                            placeholder="Số lượng" style="width:90px;"
                                                                            value="{{ $remaining > 0 ? 1 : 0 }}"
                                                                            {{ $remaining <= 0 ? 'disabled' : '' }} />
                                                                        <input type="hidden" name="unit_price"
                                                                            value="{{ $vd->gia ?? 0 }}">
                                                                        <button type="submit"
                                                                            class="btn btn-sm btn-success ms-1"
                                                                            {{ $remaining <= 0 ? 'disabled' : '' }}
                                                                            onclick="return confirm('Xác nhận tiêu thụ và thêm vào hoá đơn?')">
                                                                            Xác nhận
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            @else
                                                                <button class="btn btn-sm btn-secondary" disabled>Không có
                                                                    booking</button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <p><em>Không có đồ ăn được cấu hình cho phòng này.</em></p>
                                    @endif
                                </div>
                            </div>
                        </div> --}}

                    </div>
                </div>

                <div class="mt-3 text-end">
                    <h5>Tổng giá phòng:
                        <span class="text-success fw-bold">
                            {{ number_format($phong->tong_gia, 0, ',', '.') }} VNĐ
                        </span>
                    </h5>
                </div>
            </div> <!-- card-body -->
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.consume-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const qtyInput = form.querySelector('.qty-to-consume');
                    if (!qtyInput || qtyInput.disabled) {
                        e.preventDefault();
                        alert('Không có hàng để tiêu thụ.');
                        return;
                    }
                    const val = parseInt(qtyInput.value || '0', 10);
                    const maxv = parseInt(qtyInput.getAttribute('max') || '0', 10);
                    if (isNaN(val) || val <= 0) {
                        e.preventDefault();
                        alert('Vui lòng nhập số lượng lớn hơn 0.');
                        return;
                    }
                    if (val > maxv) {
                        e.preventDefault();
                        alert('Số lượng vượt quá phần còn lại (' + maxv + ').');
                        return;
                    }
                    // allow submit
                });
            });
        });
    </script>
@endpush
