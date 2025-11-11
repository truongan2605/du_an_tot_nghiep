@extends('layouts.admin')

@section('title', 'Truy cập bị từ chối')

@section('content')
<div class="alert alert-danger text-center">
    <h4><i class="fas fa-ban me-2"></i>Truy cập bị từ chối</h4>
    <p>{{ $message ?? 'Bạn không có quyền truy cập trang này.' }}</p>
    <a href="{{ route('staff.index') }}" class="btn btn-primary">Về Dashboard</a>
</div>
@endsection