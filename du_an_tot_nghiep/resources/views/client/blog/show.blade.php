@extends('layouts.app')
@section('title', $post->meta_title ?: $post->title)
@section('meta_description', $post->meta_description)


@section('content')
    <style>
        .page-title {
            font-weight: 800;
            letter-spacing: .2px
        }

        .meta small {
            color: #6c757d
        }

        .dropcap:first-letter {
            float: left;
            font-size: 3rem;
            line-height: 1;
            padding: .25rem .5rem;
            margin: .25rem .5rem .25rem 0;
            border-radius: .25rem;
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), .1);
            font-weight: 700;
        }

        blockquote.hero-quote {
            background: var(--bs-light);
            border-radius: .75rem;
            padding: 1rem 1.25rem;
            text-align: center;
        }

        .post-gallery img {
            width: 100%;
            height: 420px;
            object-fit: cover
        }

        .carousel-indicators [data-bs-target] {
            width: 10px;
            height: 10px;
            border-radius: 50%
        }
    </style>


    <section class="pt-3 pt-md-4">
        <div class="container">


            {{-- ========== HERO ẢNH CHÍNH ========== --}}
            <div class="post-hero mb-4">
                <img src="{{ $post->cover_image ? asset('storage/' . $post->cover_image) : asset('assets/images/blog/13.jpg') }}"
                    alt="{{ $post->title }}" class="img-fluid rounded-3 shadow-sm w-100"
                    style="max-height:480px;object-fit:cover;">
            </div>


            {{-- ========== TIÊU ĐỀ & THÔNG TIN ========== --}}
            <div class="row mb-3">
                <div class="col-lg-10 mx-auto">
                    @if ($post->category)
                        <span class="badge bg-success mb-2">{{ $post->category->name }}</span>
                    @endif
                    <h1 class="h3 h-lg-2 page-title mb-2">{{ $post->title }}</h1>
                    <div class="meta d-flex gap-3 small">
                        <small><i class="bi bi-person me-1"></i>{{ $post->author->name ?? 'Author' }}</small>
                        <small><i
                                class="bi bi-calendar2 me-1"></i>{{ optional($post->published_at)->format('M d, Y') }}</small>
                        <small><i
                                class="bi bi-clock me-1"></i>{{ max(1, round(str_word_count(strip_tags($post->content ?? '')) / 220)) }}
                            min read</small>
                    </div>
                </div>
            </div>


            {{-- ========== NỘI DUNG BÀI VIẾT ========== --}}
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="mt-3 mt-md-4">
                        @if ($post->content)
                            {!! $post->content !!}
                        @else
                            <p class="dropcap">— No content —</p>
                        @endif
                    </div>


                    {{-- ========== BLOCKQUOTE ========== --}}
                    <blockquote class="hero-quote my-4">
                        <div class="fw-semibold">
                            <i class="fa-solid fa-quote-left me-2"></i>
                            {{ $post->meta_description ?: 'Thank you for reading.' }}
                            <i class="fa-solid fa-quote-right ms-2"></i>
                        </div>
                    </blockquote>


                    {{-- ========== TAGS ========== --}}
                    @if ($post->tags->count())
                        <div class="border-top pt-3 mt-4">
                            <h6 class="fw-bold mb-2">Popular Tags</h6>
                            @foreach ($post->tags as $t)
                                <a href="{{ route('blog.index', ['tag' => $t->slug]) }}"
                                    class="badge bg-light text-dark border me-2 mb-2">#{{ $t->name }}</a>
                            @endforeach
                        </div>
                    @endif


                    {{-- ========== GALLERY Ở DƯỚI – TRÊN FOOTER ========== --}}
                    @php
                        $photos = ($post->photoAlbums ?? collect())->filter(fn($p) => !empty($p->image));
                        $autoSlide = $photos->count() >= 3;
                    @endphp


                    @if ($photos->count() > 0)
                        <hr class="my-5">
                        <div class="post-gallery mb-5">
                            <h5 class="fw-bold mb-3">Hình ảnh {{ $post->category->name ?? 'bổ sung' }}</h5>


                            <div id="carouselRoom" class="carousel slide {{ $autoSlide ? 'carousel-fade' : '' }}"
                                @if ($autoSlide) data-bs-ride="carousel" data-bs-interval="3000" @endif>
                                <div class="carousel-inner rounded-3 shadow-sm">
                                    @foreach ($photos as $idx => $photo)
                                        <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}">
                                            <img src="{{ asset('storage/' . $photo->image) }}"
                                                alt="photo-{{ $idx + 1 }}">
                                        </div>
                                    @endforeach
                                </div>


                                {{-- Chấm tròn điều hướng --}}
                                @if ($photos->count() > 1)
                                    <div class="carousel-indicators">
                                        @foreach ($photos as $idx => $photo)
                                            <button type="button" data-bs-target="#carouselRoom"
                                                data-bs-slide-to="{{ $idx }}"
                                                class="{{ $idx === 0 ? 'active' : '' }}"
                                                aria-current="{{ $idx === 0 ? 'true' : 'false' }}"
                                                aria-label="Slide {{ $idx + 1 }}"></button>
                                        @endforeach
                                    </div>
                                @endif


                                {{-- Nút prev/next --}}
                                @if ($photos->count() > 1)
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselRoom"
                                        data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carouselRoom"
                                        data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                    {{-- ========== HẾT GALLERY ========== --}}


                </div>
            </div>


        </div>
    </section>
@endsection
