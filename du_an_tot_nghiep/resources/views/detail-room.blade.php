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
                                {{ $phong->name ?? $phong->ma_phong }}
                                <small class="text-muted"> -
                                    {{ $phong->loaiPhong->ten ?? ($phong->loaiPhong->ten_loai ?? '—') }}</small>
                            </h1>
                            <p class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i> Floor
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
                                            <div class="fw-bold">View all</div>
                                            <div class="small">+{{ $remaining }} Images</div>
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
                                <h3 class="mb-0">About This Hotel</h3>
                            </div>
                            <div class="card-body pt-4 p-0">
                                <h5 class="fw-light mb-4">Main Highlights</h5>

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
                                        See <span class="see-more ms-1">more</span><span class="see-less ms-1">less</span><i
                                            class="fa-solid fa-angle-down ms-2"></i>
                                    </a>
                                @endif

                            </div>
                        </div>

                        <div class="card bg-transparent">
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <h3 class="card-title mb-0">Amenities</h3>
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
                                            <p class="mb-0">No amenities listed for this room.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Beds & Bedding -->
                        <div class="card bg-transparent">
                            <div class="card-header border-bottom bg-transparent px-0 pt-0">
                                <h3 class="card-title mb-0">Beds & Bedding</h3>
                            </div>
                            <div class="card-body pt-4 p-0">
                                <div class="mb-3">
                                    <strong>Total beds:</strong>
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
                                                                    <small class="text-muted">Capacity:
                                                                        {{ $b['capacity'] }}</small>
                                                                @endif
                                                            </div>
                                                            <div class="text-end">
                                                                <div class="fw-bold">Number of beds: {{ $b['quantity'] }}
                                                                </div>
                                                                @if (!empty($b['price']))
                                                                    <small
                                                                        class="text-muted">{{ number_format($b['price'], 0, ',', '.') }}
                                                                        VND / each</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mb-0">This room uses default bedding configuration.</p>
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
                                                                    class="mb-0 text-primary">More+</a>
                                                            </li>
                                                        </ul>

                                                        {{-- price --}}
                                                        <div
                                                            class="d-sm-flex justify-content-sm-between align-items-center mt-auto">
                                                            <div class="d-flex align-items-center">
                                                                <h5 class="fw-bold mb-0 me-1">
                                                                    {{ number_format($r->gia_mac_dinh, 0, ',', '.') }} VND
                                                                </h5>
                                                                <span class="mb-0 me-2">/day</span>
                                                            </div>
                                                            <div class="mt-3 mt-sm-0">
                                                                <a href="{{ route('rooms.show', $r->id) }}"
                                                                    class="btn btn-sm btn-primary mb-0">Select Room</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="p-3">No other room options found for this type.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Customer Review  -->
                    <div class="card bg-transparent">
                        <div class="card-header border-bottom bg-transparent px-0 pt-0">
                            <h3 class="card-title mb-0">Customer Review</h3>
                        </div>

                        <div class="card-body pt-4 p-0">
                            <div class="card bg-light p-4 mb-4">
                                <div class="row g-4 align-items-center">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            @php
                                                $avg = isset($avgRating) ? floatval($avgRating) : 0.0;
                                                $reviewCount = isset($reviews) ? $reviews->count() : 0;
                                            @endphp
                                            <h2 class="mb-0">{{ number_format($avg, 1) }}</h2>
                                            <p class="mb-2">Based on {{ $reviewCount }}
                                                review{{ $reviewCount !== 1 ? 's' : '' }}</p>

                                            {{-- star rendering --}}
                                            <ul class="list-inline mb-0">
                                                @php
                                                    $fullStars = floor($avg);
                                                    $hasHalf = $avg - $fullStars >= 0.5;
                                                    $emptyStars = 5 - $fullStars - ($hasHalf ? 1 : 0);
                                                @endphp

                                                @for ($i = 0; $i < $fullStars; $i++)
                                                    <li class="list-inline-item me-0"><i
                                                            class="fa-solid fa-star text-warning"></i></li>
                                                @endfor
                                                @if ($hasHalf)
                                                    <li class="list-inline-item me-0"><i
                                                            class="fa-solid fa-star-half-alt text-warning"></i></li>
                                                @endif
                                                @for ($i = 0; $i < $emptyStars; $i++)
                                                    <li class="list-inline-item me-0"><i
                                                            class="fa-solid fa-star text-muted"></i></li>
                                                @endfor
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        @php
                                            $counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                                            if ($reviewCount && $reviewCount > 0) {
                                                foreach ($reviews as $rv) {
                                                    $v = intval($rv->diem);
                                                    if (isset($counts[$v])) {
                                                        $counts[$v]++;
                                                    }
                                                }
                                            }
                                        @endphp

                                        <div class="card-body p-0">
                                            <div class="row gx-3 g-2 align-items-center">
                                                @foreach ([5, 4, 3, 2, 1] as $star)
                                                    <div class="col-9 col-sm-10">
                                                        <div class="progress progress-sm bg-warning bg-opacity-15">
                                                            @php
                                                                $pct = $reviewCount
                                                                    ? round(($counts[$star] / $reviewCount) * 100)
                                                                    : 0;
                                                            @endphp
                                                            <div class="progress-bar bg-warning" role="progressbar"
                                                                style="width: {{ $pct }}%"
                                                                aria-valuenow="{{ $pct }}" aria-valuemin="0"
                                                                aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 col-sm-2 text-end">
                                                        <span class="h6 fw-light mb-0">{{ $pct ?? 0 }}%</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Leave review form (optional) --}}
                            <form class="mb-5" method="POST" action="#">
                                <div class="form-control-bg-light mb-3">
                                    <select class="form-select js-choice" name="rating">
                                        <option selected>★★★★★ (5/5)</option>
                                        <option>★★★★☆ (4/5)</option>
                                        <option>★★★☆☆ (3/5)</option>
                                        <option>★★☆☆☆ (2/5)</option>
                                        <option>★☆☆☆☆ (1/5)</option>
                                    </select>
                                </div>
                                <div class="form-control-bg-light mb-3">
                                    <textarea class="form-control" name="content" placeholder="Your review" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-lg btn-primary mb-0">Post review <i
                                        class="bi fa-fw bi-arrow-right ms-2"></i></button>
                            </form>

                            {{-- List reviews --}}
                            @if ($reviewCount > 0)
                                @foreach ($reviews as $rv)
                                    @php
                                        $author = $rv->nguoiDung ?? null;
                                        $rating = $rv->diem ?? 0;
                                    @endphp
                                    <div class="d-md-flex my-4">
                                        {{-- Avatar --}}
                                        <div class="avatar avatar-lg me-3 flex-shrink-0">
                                            <img class="avatar-img rounded-circle"
                                                src="{{ $author && $author->avatar ? asset('storage/' . $author->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}"
                                                alt="avatar">
                                        </div>
                                        <div>
                                            <div class="d-flex justify-content-between mt-1 mt-md-0">
                                                <div>
                                                    <h6 class="me-3 mb-0">{{ $author->name ?? 'Guest' }}</h6>
                                                    <ul class="nav nav-divider small mb-2">
                                                        <li class="nav-item">Stayed {{ $rv->created_at->format('d M Y') }}
                                                        </li>
                                                    </ul>
                                                </div>
                                                <div class="icon-md rounded text-bg-warning fs-6">
                                                    {{ number_format($rating, 1) }}</div>
                                            </div>

                                            <p class="mb-2">{{ $rv->noi_dung }}</p>

                                            @if (!empty($rv->anh) && is_array($rv->anh))
                                                <div class="row g-4">
                                                    @foreach ($rv->anh as $img)
                                                        <div class="col-4 col-sm-3 col-lg-2">
                                                            <img src="{{ Storage::url($img) }}" class="rounded"
                                                                alt="">
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <hr>
                                @endforeach
                            @else
                                <p class="mb-0">No reviews yet — be the first to review this room.</p>
                            @endif

                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-primary-soft mb-0">Load more</a>
                            </div>
                        </div>
                    </div>

                    <!-- Hotel Policies (kept as-is) -->
                    <div class="card bg-transparent">
                        <div class="card-header border-bottom bg-transparent px-0 pt-0">
                            <h3 class="mb-0">Hotel Policies</h3>
                        </div>
                        <div class="card-body pt-4 p-0">
                            <ul class="list-group list-group-borderless mb-2">
                                <li class="list-group-item d-flex"><i class="bi bi-check-circle-fill me-2"></i>This is a
                                    family farmhouse, hence we request you to not indulge.</li>
                                <li class="list-group-item d-flex"><i class="bi bi-check-circle-fill me-2"></i>Drinking
                                    and smoking within controlled limits are permitted at the farmhouse but please do not
                                    create a mess or ruckus at the house.</li>
                                <li class="list-group-item d-flex"><i class="bi bi-check-circle-fill me-2"></i>Drugs and
                                    intoxicating illegal products are banned and not to be brought to the house or consumed.
                                </li>
                                <li class="list-group-item d-flex"><i class="bi bi-x-circle-fill me-2"></i>For any update,
                                    the customer shall pay applicable cancellation/modification charges.</li>
                            </ul>

                            <ul class="list-group list-group-borderless mb-2">
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>Check-in: 2:00 pm</li>
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>Check out: 12:00 am</li>
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>Self-check-in with building staff</li>
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>No pets</li>
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>No parties or events</li>
                                <li class="list-group-item h6 fw-light d-flex mb-0"><i
                                        class="bi bi-arrow-right me-2"></i>Smoking is allowed</li>
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
                                    <span>Price Start at</span>
                                    <h4 class="card-title mb-0">{{ number_format($phong->gia_cuoi_cung, 0, ',', '.') }}
                                        VND
                                    </h4>
                                </div>

                                <div>
                                    <h6 class="fw-normal mb-0">1 room per night</h6>
                                    <small>+ taxes & fees</small>
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
                                    class="btn btn-lg btn-primary-soft mb-0">Booking now</a>
                            </div>

                            @auth
                                <div class="d-grid mb-2">
                                    <button id="detail-wishlist-btn" type="button" class="btn btn-outline-danger btn-lg"
                                        data-phong-id="{{ $phong->id }}"
                                        aria-pressed="{{ $isWished ? 'true' : 'false' }}"
                                        aria-label="{{ $isWished ? 'Remove from wishlist' : 'Add to wishlist' }}">
                                        <i
                                            class="{{ $isWished ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart' }}"></i>
                                        <span class="wl-label ms-2">{{ $isWished ? 'Saved' : 'Add to wishlist' }}</span>
                                    </button>
                                </div>
                            @else
                                <div class="d-grid">
                                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">Login to
                                        wishlist</a>
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
