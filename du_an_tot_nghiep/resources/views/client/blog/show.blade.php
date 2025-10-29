@extends('layouts.app')
@section('title', $post->meta_title ?: $post->title)
@section('meta_description', $post->meta_description)

@section('content')
<style>
  /* Trang trí nhẹ giống demo */
  .post-hero {
    position: relative;
    border-radius: 1rem;
    overflow: hidden;
  }
  .post-hero img {
    width: 100%; height: 420px; object-fit: cover;
  }
  .post-overlay-card {
    position: absolute; left: 1rem; right: 1rem; bottom: -1.5rem;
  }
  .dropcap:first-letter{
    float:left; font-size:3rem; line-height:1; padding:.25rem .5rem;
    margin:.25rem .5rem .25rem 0; border-radius:.25rem;
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), .1);
    font-weight:700;
  }
  blockquote.hero-quote{
    background: var(--bs-light);
    border-radius: .75rem;
    padding: 1rem 1.25rem;
    text-align: center;
  }
</style>

<section class="pt-3 pt-md-4">
  <div class="container">

    {{-- HERO + CARD OVERLAY --}}
    <div class="post-hero mb-5">
      <img src="{{ $post->cover_image ? asset('storage/'.$post->cover_image) : asset('assets/images/blog/13.jpg') }}"
           alt="{{ $post->title }}">
      <div class="post-overlay-card">
        <div class="card shadow border-0">
          <div class="card-body p-3 p-md-4">
            @if ($post->category)
              <span class="badge bg-success mb-2">{{ $post->category->name }}</span>
            @endif
            <h1 class="h3 fw-bold mb-2">{{ $post->title }}</h1>
            @if ($post->excerpt)
              <p class="text-muted mb-3">{{ $post->excerpt }}</p>
            @endif
            <div class="d-flex gap-3 small text-muted">
              <span><i class="bi bi-person me-1"></i>{{ $post->author->name ?? 'Author' }}</span>
              <span><i class="bi bi-calendar2 me-1"></i>{{ optional($post->published_at)->format('M d, Y') }}</span>
              <span><i class="bi bi-clock me-1"></i>{{ max(1, round(str_word_count(strip_tags($post->content ?? '')) / 220)) }} min read</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- NỘI DUNG BÀI --}}
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <div class="mt-4 pt-2">
          {{-- Đoạn mở đầu kiểu dropcap (nếu content là HTML thuần, giữ nguyên) --}}
          @if($post->content)
            {!! $post->content !!}
          @else
            <p class="dropcap">— No content —</p>
          @endif
        </div>

        {{-- Blockquote kiểu demo --}}
        <blockquote class="hero-quote my-4">
          <div class="fw-semibold">
            <i class="fa-solid fa-quote-left me-2"></i>
            {{ $post->meta_description ?: 'Thank you for reading.' }}
            <i class="fa-solid fa-quote-right ms-2"></i>
          </div>
        </blockquote>

        {{-- TAGS --}}
        @if ($post->tags->count())
          <div class="border-top pt-3 mt-4">
            <h6 class="fw-bold mb-2">Popular Tags</h6>
            @foreach ($post->tags as $t)
              <a href="{{ route('blog.index', ['tag' => $t->slug]) }}" class="badge bg-light text-dark border me-2 mb-2">#{{ $t->name }}</a>
            @endforeach
          </div>
        @endif
      </div>
    </div>

  </div>
</section>
@endsection
