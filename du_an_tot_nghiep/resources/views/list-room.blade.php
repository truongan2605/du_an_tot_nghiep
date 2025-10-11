@extends('layouts.app')

@section('title','Danh sách phòng')

@section('content')
<section class="pt-4 pb-5">
  <div class="container">
    <div class="row g-4">
      {{-- ==== SIDEBAR LỌC ==== --}}
      <aside class="col-lg-3">
        <form method="GET" action="{{ route('list-room.index') }}" class="card border-0 shadow-sm">
          <div class="card-body">
            {{-- Hotel Type --}}
            <h6 class="fw-bold mb-3">Loại phòng</h6>
            <ul class="list-unstyled">
              <li><input type="radio" name="loai_phong_id" value=""> Tất cả</li>
              @foreach($loaiPhongs as $loaiPhong)
                <li>
                  <input type="radio" name="loai_phong_id" value="{{ $loaiPhong->id }}"
                         {{ request('loai_phong_id')==$loaiPhong->id?'checked':'' }}>
                  {{ $loaiPhong->ten_loai_phong ?? $loaiPhong->ten }}
                </li>
              @endforeach
            </ul>

            <hr>
            {{-- Price Range --}}
            <h6 class="fw-bold mb-3">Khoảng giá</h6>
            <ul class="list-unstyled">
              <li><input type="radio" name="gia_khoang" value="1"> Dưới 500.000 ₫</li>
              <li><input type="radio" name="gia_khoang" value="2"> 500 – 1 triệu</li>
              <li><input type="radio" name="gia_khoang" value="3"> 1 – 1.5 triệu</li>
              <li><input type="radio" name="gia_khoang" value="4"> Trên 1.5 triệu</li>
            </ul>

            <hr>
            {{-- Rating Star --}}
            <h6 class="fw-bold mb-3">Đánh giá sao</h6>
            <div class="d-flex flex-wrap gap-2">
              @for($i=1;$i<=5;$i++)
                <label class="border rounded px-2 py-1">
                  <input type="radio" hidden name="star" value="{{ $i }}">
                  @for($j=1;$j<=$i;$j++)
                    <i class="bi bi-star-fill text-warning"></i>
                  @endfor
                </label>
              @endfor
            </div>

            <hr>
            {{-- Amenities --}}
            <h6 class="fw-bold mb-3">Tiện nghi</h6>
            <ul class="list-unstyled">
                @foreach($tienNghis as $tienNghi)
                    <li>
                        <input type="checkbox"
                            name="amenities[]"
                            value="{{ $tienNghi->id }}"
                            {{ in_array($tienNghi->id, request()->input('amenities', [])) ? 'checked' : '' }}>
                        <label>{{ $tienNghi->ten }}</label>
                    </li>
                @endforeach
            </ul>

            <button type="submit" class="btn btn-primary w-100 mt-3">Lọc kết quả</button>
          </div>
        </form>
      </aside>

      {{-- ==== DANH SÁCH PHÒNG ==== --}}
      <div class="col-lg-9">
        <div class="row g-4">
                @forelse($phongs as $phong)
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 room-card position-relative hover-shadow transition-all">

            {{-- ========== ẢNH PHÒNG / CAROUSEL ========== --}}
            <div class="row g-0 align-items-center">
                <div class="col-md-5 position-relative">
                    @if($phong->images->count() > 1)
                        <div id="carouselRoom{{ $phong->id }}" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                @foreach($phong->images as $key => $img)
                                    <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                                        <img src="{{ asset($img->duong_dan) }}"
                                             class="w-100 h-100 object-fit-cover"
                                             alt="{{ $phong->name }}">
                                    </div>
                                @endforeach
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselRoom{{ $phong->id }}" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon bg-dark rounded-circle p-2"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carouselRoom{{ $phong->id }}" data-bs-slide="next">
                                <span class="carousel-control-next-icon bg-dark rounded-circle p-2"></span>
                            </button>
                        </div>
                    @else
                        <img src="{{ asset($phong->firstImageUrl() ?? 'template/stackbros/assets/images/default-room.jpg') }}"
                             class="w-100 h-100 object-fit-cover"
                             alt="{{ $phong->name }}">
                    @endif

                    {{-- Badge Giảm giá nếu có --}}
                    @if(isset($phong->khuyen_mai) && $phong->khuyen_mai > 0)
                        <span class="badge bg-danger position-absolute top-0 start-0 m-3 px-3 py-2 fs-6 shadow-sm">
                            -{{ $phong->khuyen_mai }}%
                        </span>
                    @endif

                    {{-- Badge trạng thái phòng --}}
                    @if($phong->trang_thai == 'trong')
                        <span class="badge bg-success position-absolute bottom-0 start-0 m-3 px-3 py-2 shadow-sm">Phòng trống</span>
                    @elseif($phong->trang_thai == 'dang_o')
                        <span class="badge bg-warning text-dark position-absolute bottom-0 start-0 m-3 px-3 py-2 shadow-sm">Đang ở</span>
                    @endif
                </div>

                {{-- ========== THÔNG TIN PHÒNG ========== --}}
                <div class="col-md-7">
                    <div class="card-body py-4 px-4">
                        {{-- Đánh giá sao --}}
                        <div class="d-flex align-items-center mb-2">
                            @for($i=1; $i<=5; $i++)
                                <i class="bi bi-star{{ $i <= ($phong->so_sao ?? 4) ? '-fill text-warning' : '' }} me-1"></i>
                            @endfor
                        </div>

                        {{-- Tên phòng --}}
                        <h5 class="fw-bold mb-1">{{ $phong->name ?? $phong->ma_phong }}</h5>

                        {{-- Mô tả hoặc vị trí --}}
                        <p class="text-muted mb-2">
                            <i class="bi bi-geo-alt me-1"></i>
                            {{ $phong->mo_ta ?? 'Địa điểm đang cập nhật' }}
                        </p>

                        {{-- Tiện nghi --}}
                        <div class="small text-muted mb-2">
                            @if($phong->tienNghis && $phong->tienNghis->count())
                                @foreach($phong->tienNghis->take(3) as $tiennghi)
                                    {{ $tiennghi->ten_tien_nghi }} &bull;
                                @endforeach
                                @if($phong->tienNghis->count() > 3)
                                    <a href="#" class="text-decoration-none">More+</a>
                                @endif
                            @else
                                <span>Chưa có tiện nghi</span>
                            @endif
                        </div>

                        {{-- Ưu đãi --}}
                        <ul class="list-unstyled small mb-3 text-success">
                            <li><i class="bi bi-check-circle me-2"></i>Miễn phí huỷ phòng</li>
                            <li><i class="bi bi-check-circle me-2"></i>Bữa sáng miễn phí</li>
                        </ul>

                        {{-- Giá & Nút chọn phòng --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="fw-bold text-primary mb-0">
                                    {{ number_format($phong->gia_mac_dinh, 0, ',', '.') }} VNĐ
                                    <span class="small fw-normal text-muted">/đêm</span>
                                </h4>
                                @if($phong->gia_goc ?? false)
                                    <small class="text-decoration-line-through text-muted">
                                        {{ number_format($phong->gia_goc, 0, ',', '.') }} VNĐ
                                    </small>
                                @endif
                            </div>
                            <a href="{{ route('rooms.show', $phong->id) }}"
                               class="btn btn-dark rounded-pill px-4 py-2 transition-all">
                               Chọn phòng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="text-center py-5">
        <img src="{{ asset('template/stackbros/assets/images/no-data.svg') }}" alt="No rooms" width="180">
        <p class="mt-3 mb-0">Không tìm thấy phòng nào phù hợp.</p>
    </div>
@endforelse

            </div>
        <div class="mt-4 d-flex justify-content-center">{{ $phongs->links() }}</div>
      </div>
    </div>
  </div>
</section>
@endsection
