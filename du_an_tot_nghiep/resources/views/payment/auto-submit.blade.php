@extends('layouts.app')

@section('title', 'Redirecting to Payment Gateway')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mb-3">Đang chuyển đến cổng thanh toán {{ $gateway }}</h5>
                    <p class="text-muted">Vui lòng chờ trong giây lát...</p>
                    <p id="errorMessage" class="text-danger" style="display: none;"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-submit form after DOM loads
    document.addEventListener('DOMContentLoaded', function() {
        const formData = new FormData();
        
        // Add CSRF token
        formData.append('_token', '{{ csrf_token() }}');
        
        // Add all data
        @foreach($data as $key => $value)
            @if(is_array($value))
                @foreach($value as $subKey => $subValue)
                    formData.append('{{ $key }}[{{ $subKey }}]', '{{ $subValue }}');
                @endforeach
            @else
                formData.append('{{ $key }}', '{{ $value }}');
            @endif
        @endforeach
        
        // Send POST request
        fetch('{{ $route }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.redirect_url) {
                // Redirect to payment gateway
                window.location.href = data.redirect_url;
            } else if (data.error) {
                document.getElementById('errorMessage').textContent = 'Lỗi: ' + data.error;
                document.getElementById('errorMessage').style.display = 'block';
            }
        })
        .catch(error => {
            document.getElementById('errorMessage').textContent = 'Lỗi kết nối: ' + error.message;
            document.getElementById('errorMessage').style.display = 'block';
        });
    });
</script>
@endsection
