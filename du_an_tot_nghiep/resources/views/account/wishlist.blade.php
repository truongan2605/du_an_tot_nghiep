@extends('layouts.app')

@section('title', 'My Wishlist')

@section('content')
    <section class="pt-3">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-xl-9">
                    <div class="card border bg-transparent">
                        <div class="card-header bg-transparent border-bottom">
                            <h4 class="card-header-title">My Wishlist</h4>
                        </div>

                        <div class="card-body vstack gap-4">
                            <form class="d-flex justify-content-between" method="POST"
                                action="{{ route('account.wishlist.clear') }}">
                                @csrf
                                <div class="col-6 col-xl-3">
                                    <select class="form-select form-select-sm js-choice border-0">
                                        <option value="">Sort by</option>
                                        <option>Recently added</option>
                                    </select>
                                </div>
                                <button class="btn btn-danger-soft mb-0" type="submit"><i
                                        class="fas fa-trash me-2"></i>Remove all</button>
                            </form>

                            @forelse($wishlists as $wl)
                                @php $p = $wl->phong; @endphp
                                @if ($p)
                                    <div class="card shadow p-2">
                                        <div class="row g-0">
                                            <div class="col-md-3">
                                                <img src="{{ $p->firstImageUrl() ?? ($p->thumb_url ?? asset('template/stackbros/assets/images/category/hotel/4by3/10.jpg')) }}"
                                                    class="card-img rounded-2"
                                                    alt="{{ $p->ten_phong ?? ($p->name ?? 'Phong') }}">
                                            </div>
                                            <div class="col-md-9">
                                                <div class="card-body py-md-2 d-flex flex-column h-100">
                                                    <h5 class="card-title mb-1">
                                                        <a href="{{ route('rooms.show', $p->id) }}">{{ $p->ten_phong ?? ($p->name ?? 'Phong') }}
                                                        </a>
                                                    </h5>

                                                    <small><i
                                                            class="bi bi-geo-alt me-2"></i>{{ $p->ma_phong ?? '' }}</small>

                                                    <div
                                                        class="d-sm-flex justify-content-sm-between align-items-center mt-3 mt-md-auto">
                                                        <div class="d-flex align-items-center">
                                                            <h5 class="fw-bold mb-0 me-1">
                                                                {{ number_format($p->gia_cuoi_cung ?? 0) }} VND</h5>
                                                            <span class="mb-0 me-2">/day</span>
                                                        </div>

                                                        <div class="mt-3 mt-sm-0">
                                                            <form method="POST"
                                                                action="{{ route('account.wishlist.destroy', $wl->id) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button
                                                                    class="btn btn-sm btn-dark w-100 mb-0">Remove</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="alert alert-info">You do not have any favorites yet.</div>
                            @endforelse

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.wishlist-toggle').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const phongId = this.dataset.phong;
                    fetch("{{ url('account/wishlist/toggle') }}/" + phongId, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    }).then(r => r.json()).then(data => {
                        if (data.status === 'removed') {
                            window.location.reload();
                        } else if (data.status === 'added') {
                            window.location.reload();
                        }
                    });
                });
            });
        });
    </script>
@endpush
