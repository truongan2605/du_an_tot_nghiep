@extends('layouts.app')

@section('title', 'Chi tiet phong - Detail Room')

@section('content')
    @php
        $gallery = $phong->images->values();
        $total = $gallery->count();
        $main = $gallery->get(0);

        $thumbsAll = $gallery->slice(1);
        $thumbs = $thumbsAll->take(4)->values();
        $thumbCount = $thumbs->count();
        $remaining = max(0, $total - 1 - $thumbCount);
    @endphp


    <!-- Main Title + Gallery START -->
    <section class="py-0 pt-sm-5">
        <div class="container position-relative">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-lg-flex justify-content-lg-between mb-1">
                        <div class="mb-2 mb-lg-0">
                            <h1 class="fs-2">
                                {{ $phong->loaiPhong->ten ?? ($phong->loaiPhong->ten_loai ?? '—') }}
                            </h1>
                            <p class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i> Tầng
                                {{ $phong->tang->so_tang ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gallery-layout">
                {{-- Main large image --}}
                <div class="main-tile">
                    @if ($main)
                        <a href="{{ Storage::url($main->image_path) }}" class="glightbox"
                            data-gallery="room-{{ $phong->id }}">
                            <img src="{{ Storage::url($main->image_path) }}" alt="{{ $phong->name ?? $phong->ma_phong }}"
                                loading="lazy">
                        </a>
                    @else
                        <img src="{{ $phong->firstImageUrl() }}" alt="{{ $phong->name ?? $phong->ma_phong }}"
                            loading="lazy">
                    @endif
                </div>

                @if ($thumbCount > 0)
                    @php
                        $tiles = [];
                        for ($i = 0; $i < 4; $i++) {
                            $tiles[$i] = $thumbs->get($i) ?? null;
                        }
                    @endphp

                    <div class="side-grid" role="list">
                        @foreach ($tiles as $i => $tile)
                            @php
                                $has = (bool) $tile;
                                $url = $has ? Storage::url($tile->image_path) : null;
                                $isFourth = $i === 3;
                            @endphp

                            <div class="thumb-tile" role="listitem" aria-label="thumbnail {{ $i + 1 }}">
                                @if ($has)
                                    <a href="{{ $url }}" class="thumb-link glightbox"
                                        data-gallery="room-{{ $phong->id }}">
                                        <img src="{{ $url }}" alt="{{ $phong->name ?? $phong->ma_phong }}"
                                            loading="lazy">
                                    </a>
                                @else
                                    <div class="thumb-empty" aria-hidden="true"></div>
                                @endif

                                @if ($isFourth && $remaining > 0)
                                    @php
                                        $overlayTarget =
                                            $url ??
                                            Storage::url(
                                                $gallery->slice(1 + $thumbCount)->first()->image_path ??
                                                    ($main ? $main->image_path : null),
                                            );
                                    @endphp

                                    @if ($overlayTarget)
                                        <a href="{{ $overlayTarget }}" class="thumb-link overlay-anchor glightbox"
                                            data-gallery="room-{{ $phong->id }}"></a>
                                    @endif

                                    <div class="overlay view-all" aria-hidden="true">
                                        <div>
                                    <div class="fw-bold">Xem tất cả</div>
                                    <div class="small">+{{ $remaining }} Ảnh</div>
                                        </div>
                                    </div>

                                    @foreach ($gallery->slice(1 + $thumbCount) as $more)
                                        <a href="{{ Storage::url($more->image_path) }}" class="d-none glightbox"
                                            data-gallery="room-{{ $phong->id }}"></a>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>

                @endif
            </div>
        </div>
    </section>
    <!-- Main Title + Gallery END -->

    <!-- About & Main content -->
    <section class="pt-0">
        <div class="container" data-sticky-container>
            <div class="row g-4 g-xl-5">
                <!-- Left content -->
                <div class="col-xl-7 order-1">
                    <div class="vstack gap-5">
                        <!-- About hotel -->
                        <div class="card bg-transparent">
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <h3 class="mb-0">Về khách sạn này</h3>
                            </div>
                            <div class="card-body pt-4 p-0">
                                <h5 class="fw-light mb-4">Điểm nổi bật chính</h5>

                                @php
                                    $desc = $phong->mo_ta ?? '';
                                    $limit = 400;
                                @endphp

                                @if (strlen($desc) <= $limit)
                                    <p class="mb-0">{!! nl2br(e($desc ?: 'Không có mô tả cho phòng này.')) !!}</p>
                                @else
                                    <p class="mb-0">{!! nl2br(e(\Illuminate\Support\Str::limit($desc, $limit))) !!}</p>

                                    <div class="collapse" id="collapseContent">
                                        <p class="my-3">{!! nl2br(e(\Illuminate\Support\Str::substr($desc, $limit))) !!}</p>
                                    </div>

                                    <a class="p-0 mb-4 mt-2 btn-more d-flex align-items-center collapsed"
                                        data-bs-toggle="collapse" href="#collapseContent" role="button"
                                        aria-expanded="false" aria-controls="collapseContent">
                                        Xem <span class="see-more ms-1">thêm</span><span class="see-less ms-1">ít hơn</span><i
                                            class="fa-solid fa-angle-down ms-2"></i>
                                    </a>
                                @endif

                            </div>
                        </div>

                        <div class="card bg-transparent">
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <h3 class="card-title mb-0">Dịch vụ</h3>
                            </div>
                            <div class="card-body pt-4 p-0">
                                <div class="row g-4">
                                    @if ($phong->tienNghis && $phong->tienNghis->count())
                                        @foreach ($phong->tienNghis->chunk(2) as $chunk)
                                            @foreach ($chunk as $tn)
                                                <div class="col-sm-6">
                                                    <h6><i
                                                            class="{{ $tn->icon ?? 'fa-solid fa-check' }} me-2"></i>{{ $tn->ten }}
                                                    </h6>
                                                    @if ($tn->mo_ta)
                                                        <p class="small mb-0">{{ Str::limit($tn->mo_ta, 120) }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endforeach
                                    @else
                                        <div class="col-12">
                                            <p class="mb-0">Không có dịch vụ nào được liệt kê cho phòng này.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Beds & Bedding -->
                        <div class="card bg-transparent">
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <h3 class="card-title mb-0">Giường & Chăn gối</h3>
                            </div>
                            <div class="card-body pt-4 p-0">
                                <div class="mb-3">
                                    <strong>Tổng số giường:</strong>
                                    <span>{{ $totalBeds }}</span>

                                </div>

                                @if ($bedSummary && $bedSummary->count())
                                    <div class="row g-3">
                                        @foreach ($bedSummary as $b)
                                            <div class="col-12">
                                                <div class="d-flex align-items-center gap-3">
                                                    @if (!empty($b['icon']))
                                                        <div class="me-2">
                                                            <i class="{{ $b['icon'] }} fs-4"></i>
                                                        </div>
                                                    @endif
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="mb-0">{{ $b['name'] }}</h6>
                                                                @if (!empty($b['capacity']))
                                                                    <small class="text-muted">Sức chứa:
                                                                        {{ $b['capacity'] }}</small>
                                                                @endif
                                                            </div>
                                                            <div class="text-end">
                                                                <div class="fw-bold">Số lượng giường: {{ $b['quantity'] }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mb-0">Phòng này sử dụng cấu hình giường mặc định.</p>
                                @endif
                            </div>
                        </div>

                        <div class="card-body pt-4 p-0">
                            <div class="vstack gap-4">
                                @if ($related && $related->count())
                                    @foreach ($related as $r)
                                        <div class="card shadow p-3">
                                            <div class="row g-4">
                                                <div class="col-md-5 position-relative">
                                                    <div
                                                        class="tiny-slider arrow-round arrow-xs arrow-dark overflow-hidden rounded-2">
                                                        <div class="tiny-slider-inner" data-autoplay="true"
                                                            data-arrow="true" data-dots="false" data-items="1">
                                                            @php $imgs = $r->images->take(4); @endphp
                                                            @if ($imgs->count())
                                                                @foreach ($imgs as $im)
                                                                    <div><img src="{{ Storage::url($im->image_path) }}"
                                                                            class="rounded-2"
                                                                            alt="{{ $r->name ?? $r->ma_phong }}"></div>
                                                                @endforeach
                                                            @else
                                                                <div><img src="{{ $r->firstImageUrl() }}"
                                                                        class="rounded-2"
                                                                        alt="{{ $r->name ?? $r->ma_phong }}"></div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="col-md-7">
                                                    <div class="card-body d-flex flex-column h-100 p-0">
                                                        <h5 class="card-title"><a
                                                                href="{{ route('rooms.show', $r->id) }}">{{ $r->name ?? $r->ma_phong }}</a>
                                                        </h5>

                                                        <ul class="nav nav-divider mb-2">
                                                            @foreach ($r->tienNghis->take(3) as $tn)
                                                                <li class="nav-item">{{ $tn->ten }}</li>
                                                            @endforeach
                                                            <li class="nav-item">
                                                                <a href="{{ route('rooms.show', $r->id) }}"
                                                                    class="mb-0 text-primary">Xem thêm+</a>
                                                            </li>
                                                        </ul>

                                                        {{-- price --}}
                                                        <div
                                                            class="d-sm-flex justify-content-sm-between align-items-center mt-auto">
                                                            <div class="d-flex align-items-center">
                                                                <h5 class="fw-bold mb-0 me-1">
                                                                    {{ number_format($r->gia_cuoi_cung, 0, ',', '.') }} VND
                                                                </h5>
                                                                <span class="mb-0 me-2">/ngày</span>
                                                            </div>
                                                            <div class="mt-3 mt-sm-0">
                                                                <a href="{{ route('rooms.show', $r->id) }}"
                                                                    class="btn btn-sm btn-primary mb-0">Chọn phòng</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="p-3">Không tìm thấy lựa chọn phòng khác cho loại này.</div>
                                @endif
                            </div>
                        </div>
                    </div>

<h3 class="mt-4 mb-3">Đánh giá của khách hàng</h3>

<div id="reviews-wrapper">

    @forelse($phong->danhGias as $index => $dg)
    <div class="review-box" data-index="{{ $index }}">

        <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="fw-semibold">
                <i class="fa fa-user-circle me-1"></i> {{ $dg->user->name }}
            </span>
            <span class="text-muted small">{{ $dg->created_at->format('d/m/Y') }}</span>
        </div>

        @if($dg->rating)
        <div class="review-stars mb-2">
            @for($i = 1; $i <= $dg->rating; $i++)
                <i class="fa-solid fa-star text-warning"></i>
            @endfor
        </div>
        @endif

        <p class="mb-1">{{ $dg->noi_dung }}</p>

        @foreach($dg->replies as $reply)
        <div class="review-reply mt-2">
            <strong><i class="fa fa-user-shield"></i> Admin:</strong>
            <p class="mb-1">{{ $reply->noi_dung }}</p>
            <small class="text-muted">{{ $reply->created_at->format('d/m/Y') }}</small>
        </div>
        @endforeach

    </div>
    @empty
        <p class="text-muted">Chưa có đánh giá nào.</p>
    @endforelse
</div>

<button id="toggle-all" class="btn btn-outline-primary mt-3" style="display:none;">
    Xem tất cả đánh giá
</button>







                    <!-- Hotel Policies (kept as-is) -->
                    <div class="card bg-transparent">
                        <div class="card-header border-bottom bg-transparent px-0 pt-0">
                            <h3 class="mb-0">Chính Sách Trong Khách Sạn</h3>
                        </div>
                        <div class="card-body pt-4 p-0">
                            <ul class="list-group list-group-borderless mb-2">
                                <li class="list-group-item d-flex"><i class="bi bi-check-circle-fill me-2"></i>Được phép uống rượu và hút thuốc trong phạm vi được kiểm soát tại khu vực phòng nhưng vui lòng không gây bừa bộn hoặc ồn ào trong phòng</li>
                                <li class="list-group-item d-flex"><i class="bi bi-check-circle-fill me-2"></i>Ma túy và các sản phẩm bất hợp pháp gây say bị cấm và không được mang vào nhà hoặc tiêu thụ.
                                </li>
                                <li class="list-group-item d-flex"><i class="bi bi-x-circle-fill me-2"></i>Đối với bất kỳ bản cập nhật nào, khách hàng sẽ phải trả phí hủy/sửa đổi áp dụng</li>
                            </ul>

                            <ul class="list-group list-group-borderless mb-2">
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>Check-in: 2:00 pm</li>
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>Check out: 12:00 am</li>
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>Tự làm thủ tục nhận phòng với nhân viên tòa khách sạn</li>
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>Thú Cưng</li>
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>Được sử dụng thuốc lá</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Right side content (aside) -->
                <aside class="col-xl-5 order-xl-2">
                    <div data-sticky data-margin-top="100" data-sticky-for="1199">
                        <div class="card card-body border">
                            <div class="d-sm-flex justify-content-sm-between align-items-center mb-3">
                                <div>
                                    <span>Giá bắt đầu từ</span>
                                    <h4 class="card-title mb-0">{{ number_format($phong->gia_cuoi_cung, 0, ',', '.') }}
                                        VND
                                    </h4>
                                </div>

                                <div>
                                    <h6 class="fw-normal mb-0">1 phòng mỗi đêm</h6>
                                    <small>+ thuế & phí</small>
                                </div>
                            </div>

                            {{-- Rating box (uses avgRating) --}}
                            @php
                                $avg = isset($avgRating) ? floatval($avgRating) : 0.0;
                                $fullStars = floor($avg);
                                $hasHalf = $avg - $fullStars >= 0.5;
                                $emptyStars = 5 - $fullStars - ($hasHalf ? 1 : 0);
                            @endphp

                            <ul class="list-inline mb-2">
                                <li class="list-inline-item me-2 h6 fw-light mb-0">{{ number_format($avg, 1) }}</li>
                                @for ($i = 0; $i < $fullStars; $i++)
                                    <li class="list-inline-item me-0 small"><i class="fa-solid fa-star text-warning"></i>
                                    </li>
                                @endfor
                                @if ($hasHalf)
                                    <li class="list-inline-item me-0 small"><i
                                            class="fa-solid fa-star-half-alt text-warning"></i></li>
                                @endif
                                @for ($i = 0; $i < $emptyStars; $i++)
                                    <li class="list-inline-item me-0 small"><i class="fa-solid fa-star text-muted"></i>
                                    </li>
                                @endfor
                            </ul>

                            <div class="d-grid">
                                <a href="{{ route('account.booking.create', $phong) }}"
                                    class="btn btn-lg btn-primary-soft mb-0">Đặt phòng ngay</a>
                            </div>

                            @auth
                                <div class="d-grid mb-2" style="margin-top: 12px ">
                                    <button id="detail-wishlist-btn" type="button" class="btn btn-outline-danger btn-lg"
                                        data-phong-id="{{ $phong->id }}"
                                        aria-pressed="{{ $isWished ? 'true' : 'false' }}"
                                        aria-label="{{ $isWished ? 'Xóa khỏi danh sách yêu thích' : 'Thêm vào danh sách yêu thích' }}">
                                        <i
                                            class="{{ $isWished ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart' }}"></i>
                                        <span class="wl-label ms-2">{{ $isWished ? 'Đã lưu' : 'Thêm vào yêu thích' }}</span>
                                    </button>
                                </div>
                            @else
                                <div class="d-grid" style="margin-top: 15px">
                                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">Đăng nhập để
                                        yêu thích</a>
                                </div>
                            @endauth

                        </div>
                    </div>
                </aside>
            </div>
        </div>
        </div>
    </section>
    <!-- About hotel END -->

@endsection

@push('styles')
    <style>
        .gallery-layout {
            --main-height: 320px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            grid-template-rows: calc(var(--main-height) / 2) calc(var(--main-height) / 2);
            gap: 1rem;
            align-items: stretch;
        }

        .main-tile {
            grid-row: 1 / span 2;
            width: 100%;
            height: var(--main-height);
            overflow: hidden;
            border-radius: 0.6rem;
            background: #f3f3f3;
        }

        .main-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 0.6rem;
        }

        .side-grid {
            grid-column: 2;
            grid-row: 1 / span 2;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 0.8rem;
            align-items: stretch;
            margin-bottom: 15px;
        }

        .thumb-tile {
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
            background: #eee;
            min-height: 0;
            display: flex;
            align-items: stretch;
            justify-content: center;
        }

        .thumb-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .thumb-empty {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
        }

        .overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            pointer-events: none;
            background: linear-gradient(0deg, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.15));
            text-align: center;
            border-radius: 0.5rem;
        }

        .overlay-anchor {
            position: absolute;
            inset: 0;
            z-index: 3;
            display: block;
            pointer-events: auto;
        }

        .thumb-link {
            display: block;
            width: 100%;
            height: 100%;
        }

        @media (max-width: 991px) {
            .gallery-layout {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }

            .main-tile {
                grid-row: auto;
                height: auto;
                aspect-ratio: 16/9;
            }

            .side-grid {
                grid-column: 1;
                grid-row: auto;
                grid-template-columns: repeat(4, 1fr);
                grid-auto-rows: 100px;
                overflow-x: auto;
                gap: 0.6rem;
            }

            .thumb-tile {
                min-width: 110px;
                flex-shrink: 0;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('template/stackbros/assets/vendor/glightbox/js/glightbox.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof GLightbox !== 'undefined') {
                GLightbox({
                    selector: '.glightbox',
                    touchNavigation: true,
                    loop: true,
                    autoplayVideos: false
                });
            }
        });
    </script>
@endpush


<style>
/* Review container */
.review-box {
    padding: 15px 0;
    border-bottom: 1px solid #eaeaea;
}
.review-reply {
    background: #f8f9fa;
    border-left: 3px solid #0d6efd;
    padding: 10px 12px;
    border-radius: 6px;
    margin-top: 8px;
    font-size: 14px;
}


</style>



<script>
document.addEventListener('DOMContentLoaded', function() {

    const reviews = document.querySelectorAll('.review-box');
    const toggleBtn = document.getElementById('toggle-all');

    if (reviews.length > 3) {
        // Ẩn tất cả review sau review thứ 3
        reviews.forEach((box, i) => {
            if (i > 2) box.style.display = 'none';
        });
        toggleBtn.style.display = 'block';
    }

    let expanded = false;

    toggleBtn.addEventListener('click', function() {
        expanded = !expanded;

        reviews.forEach((box, i) => {
            if (!expanded && i > 2) box.style.display = 'none';
            else box.style.display = 'block';
        });

        toggleBtn.textContent = expanded 
            ? 'Thu gọn đánh giá'
            : 'Xem tất cả đánh giá';
    });
});
</script>



