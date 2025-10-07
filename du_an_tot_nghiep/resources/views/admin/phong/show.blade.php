@extends('layouts.admin')

@section('title', 'Chi tiết phòng')

@section('content')
    <div class="container-fluid">
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
                                    @if($phong->loaiPhong)
                                        <div class="small text-muted">Giá loại: <strong>{{ number_format($phong->loaiPhong->gia_mac_dinh ?? 0, 0, ',', '.') }} đ</strong></div>
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

                            $boSung = $roomAmenitiesAll->reject(function($item) use ($typeAmenities) {
                                return $typeAmenities->contains('id', $item->id);
                            });
                            $boSungSum = $boSung->sum('gia');

                            $allIds = array_values(array_unique(array_merge(
                                $typeAmenities->pluck('id')->toArray(),
                                $roomAmenitiesAll->pluck('id')->toArray()
                            )));
                            $allAmenitiesSum = \App\Models\TienNghi::whereIn('id', $allIds)->sum('gia');

                            $totalDisplay = $phong->tong_gia;
                        @endphp

                        <div class="card mt-3">
                            <div class="card-header">Phân tích giá</div>
                            <div class="card-body p-2">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td>Giá loại phòng</td>
                                        <td class="text-end"><strong>{{ number_format($typePrice,0,',','.') }} đ</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Tổng tiện nghi mặc định</td>
                                        <td class="text-end">{{ number_format($typeAmenitiesSum,0,',','.') }} đ</td>
                                    </tr>
                                    <tr>
                                        <td>Tổng tiện nghi bổ sung</td>
                                        <td class="text-end">{{ number_format($boSungSum,0,',','.') }} đ</td>
                                    </tr>
                                    <tr class="table-active">
                                        <td>Tổng tiện nghi</td>
                                        <td class="text-end">{{ number_format($allAmenitiesSum,0,',','.') }} đ</td>
                                    </tr>
                                    <tr>
                                        <th>Tổng giá phòng (hiện tại)</th>
                                        <th class="text-end text-success">{{ number_format($totalDisplay,0,',','.') }} đ</th>
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
                    <h5 class="fw-bold">Tiện nghi</h5>
                    <div class="row">
                        @php
                            $tienMacDinh = $phong->loaiPhong?->tienNghis ?? collect();
                            $tienPhongAll = $phong->tienNghis ?? collect();
                            $tienBoSung = $tienPhongAll->reject(function ($item) use ($tienMacDinh) {
                                return $tienMacDinh->contains('id', $item->id);
                            });
                        @endphp

                        <!-- Tiện nghi mặc định -->
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">Tiện nghi mặc định</div>
                                <div class="card-body">
                                    @if ($tienMacDinh->count())
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($tienMacDinh as $tn)
                                                <li>
                                                    ✔ {{ $tn->ten }}
                                                    <small class="text-muted"> — {{ number_format($tn->gia ?? 0,0,',','.') }} đ</small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p><em>Không có tiện nghi mặc định</em></p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Tiện nghi bổ sung -->
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">Tiện nghi bổ sung</div>
                                <div class="card-body">
                                    @if ($tienBoSung->count())
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($tienBoSung as $tn)
                                                <li>
                                                    ➕ {{ $tn->ten }}
                                                    <small class="text-muted"> — {{ number_format($tn->gia ?? 0,0,',','.') }} đ</small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p><em>Chưa có tiện nghi bổ sung</em></p>
                                    @endif
                                </div>
                            </div>
                        </div>
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
