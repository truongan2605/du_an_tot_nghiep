@extends('layouts.app')
@section('title', 'The Blog')

@section('content')
<section class="pt-4 pt-md-5">
    <div class="container">
        {{-- Title --}}
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="display-5 fw-bold">The Blog</h1>
            </div>
        </div>

        @php($feature = $posts->first())
        @php($list = $posts->slice(1, 3))
        @php($latest = $posts->slice(4))

        {{-- FEATURE + SIDEBAR --}}
        <div class="row g-4 mb-5">
            {{-- FEATURE POST --}}
            <div class="col-lg-6">
                @if ($feature)
                <div class="card border-0 shadow-sm bg-transparent">
                    <div class="position-relative">
                        <img src="{{ $feature->cover_image ? asset('storage/' . $feature->cover_image) : asset('assets/images/blog/feature.jpg') }}"
                             class="card-img rounded-3" alt="{{ $feature->title }}">
                        @if ($feature->category)
                        <div class="card-img-overlay p-3">
                            <a href="{{ route('blog.index', ['category' => $feature->category->slug]) }}"
                               class="badge bg-primary text-white">{{ $feature->category->name }}</a>
                        </div>
                        @endif
                    </div>
                    <div class="card-body px-3 pb-2">
                        <small class="text-muted d-block mb-2">
                            <i class="bi bi-calendar2-plus me-1"></i>{{ optional($feature->published_at)->format('M d, Y') }}
                        </small>
                        <h4 class="fw-bold">
                            <a href="{{ route('blog.show', $feature->slug) }}" class="text-dark text-decoration-none">
                                {{ $feature->title }}
                            </a>
                        </h4>
                        <p class="text-muted mb-3">{{ Str::limit($feature->excerpt, 120) }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small>By {{ $feature->author->name ?? 'Admin' }}</small>
                            <a href="{{ route('blog.show', $feature->slug) }}" class="text-decoration-none fw-semibold text-primary">
                                Read more →
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- SIDEBAR POSTS --}}
            <div class="col-lg-6 ps-lg-4">
                <div class="vstack gap-4">
                    @foreach ($list as $item)
                    <div class="d-flex border-bottom pb-3">
                        <div class="flex-shrink-0">
                            <img src="{{ $item->cover_image ? asset('storage/' . $item->cover_image) : asset('assets/images/blog/thumb.jpg') }}"
                                 class="rounded-3" width="120" height="85" style="object-fit:cover;" alt="{{ $item->title }}">
                        </div>
                        <div class="ms-3">
                            @if ($item->category)
                                <span class="badge bg-secondary mb-1">{{ $item->category->name }}</span>
                            @endif
                            <h6 class="fw-bold mb-1">
                                <a href="{{ route('blog.show', $item->slug) }}" class="text-dark text-decoration-none">
                                    {{ $item->title }}
                                </a>
                            </h6>
                            <small class="text-muted">
                                <i class="bi bi-calendar me-1"></i>{{ optional($item->published_at)->format('M d, Y') }}
                            </small>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- LATEST ARTICLES --}}
        <section class="pt-0">
            <div class="text-center mb-4">
                <h3 class="fw-bold">Latest Article</h3>
            </div>
            <div class="row g-4">
                @foreach ($latest as $p)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <img src="{{ $p->cover_image ? asset('storage/' . $p->cover_image) : asset('assets/images/blog/11.jpg') }}"
                             class="card-img-top rounded-top-3" alt="{{ $p->title }}">
                        <div class="card-body">
                            @if ($p->category)
                                <a href="{{ route('blog.index', ['category' => $p->category->slug]) }}"
                                   class="badge bg-info text-white mb-2">{{ $p->category->name }}</a>
                            @endif
                            <h5 class="fw-bold">
                                <a href="{{ route('blog.show', $p->slug) }}" class="text-dark text-decoration-none">
                                    {{ $p->title }}
                                </a>
                            </h5>
                            <small class="text-muted d-block">By {{ $p->author->name ?? 'Author' }}</small>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="text-center mt-5">
                {{ $posts->links() }}
            </div>
        </section>
    </div>
</section>
@endsection
