@extends('layouts.app')

@section('title', 'So sánh phòng')

@section('content')
    {{-- Hero Section --}}
    <section class="compare-hero py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="hero-icon me-3">
                            <i class="bi bi-arrows-angle-expand"></i>
                        </div>
                        <div>
                            <h1 class="mb-2 fw-bold">So sánh phòng</h1>
                            <p class="text-muted mb-0">So sánh chi tiết để tìm ra lựa chọn hoàn hảo cho bạn</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="{{ route('list-room.index') }}" class="btn btn-outline-primary rounded-pill px-4 py-2">
                        <i class="bi bi-arrow-left me-2"></i>Quay lại danh sách
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            {{-- Empty State --}}
            <div id="emptyState" class="text-center py-5">
                <div class="empty-state-icon mb-4">
                    <i class="bi bi-inbox"></i>
                </div>
                <h3 class="mb-3">Chưa có phòng nào được chọn</h3>
                <p class="text-muted mb-4">Hãy quay lại danh sách phòng và chọn tối đa 4 phòng để so sánh</p>
                <a href="{{ route('list-room.index') }}" class="btn btn-primary btn-lg rounded-pill px-5">
                    <i class="bi bi-plus-circle me-2"></i>Bắt đầu chọn phòng
                </a>
            </div>

            {{-- Compare Cards Container --}}
            <div id="compareContainer" style="display: none;">
                {{-- Action Bar --}}
                <div class="compare-action-bar mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary-soft text-primary px-3 py-2">
                                <i class="bi bi-check2-square me-1"></i>
                                <span id="roomCount">0</span> phòng đang so sánh
                            </span>
                        </div>
                        <button class="btn btn-outline-danger rounded-pill btn-sm" onclick="clearAll()">
                            <i class="bi bi-trash me-1"></i>Xóa tất cả
                        </button>
                    </div>
                </div>

                {{-- Comparison Grid --}}
                <div id="roomsGrid" class="row g-4">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Compare page loaded');
        
        const STORAGE_KEY = 'compareRooms';
        
        function getComparedRooms() {
            const stored = localStorage.getItem(STORAGE_KEY);
            return stored ? JSON.parse(stored) : [];
        }

        function removeRoom(roomId) {
            let rooms = getComparedRooms();
            rooms = rooms.filter(r => r.id !== roomId);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(rooms));
            loadCompareData();
        }

        function clearAll() {
            if (confirm('Bạn có chắc muốn xóa tất cả phòng đã chọn?')) {
                localStorage.removeItem(STORAGE_KEY);
                loadCompareData();
            }
        }

        async function loadCompareData() {
            const roomIds = getComparedRooms().map(r => r.id);
            console.log('Loading compare data for rooms:', roomIds);
            
            if (roomIds.length === 0) {
                console.log('No rooms to compare, showing empty state');
                document.getElementById('emptyState').style.display = 'block';
                document.getElementById('compareContainer').style.display = 'none';
                return;
            }

            console.log('Showing compare container');
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('compareContainer').style.display = 'block';
            document.getElementById('roomCount').textContent = roomIds.length;

            try {
                const url = `/api/rooms/compare-data?ids=${roomIds.join(',')}`;
                console.log('Fetching from:', url);
                const response = await fetch(url);
                const rooms = await response.json();
                console.log('Received rooms:', rooms);
                renderRoomCards(rooms);
            } catch (error) {
                console.error('Error loading room data:', error);
                alert('Có lỗi khi tải dữ liệu phòng. Vui lòng thử lại.');
            }
        }

        function renderRoomCards(rooms) {
            console.log('Rendering room cards for', rooms.length, 'rooms');
            const grid = document.getElementById('roomsGrid');
            
            // Find best values for highlighting
            const prices = rooms.map(r => r.gia_cuoi_cung || 0);
            const sizes = rooms.map(r => r.loai_phong?.dien_tich || r.dien_tich || 0);
            const lowestPrice = Math.min(...prices.filter(p => p > 0));
            const largestSize = Math.max(...sizes);

            let html = '';
            
            rooms.forEach((room, index) => {
                const firstImage = room.images && room.images.length > 0 
                    ? `/storage/${room.images[0].image_path}` 
                    : '/template/stackbros/assets/images/default-room.jpg';
                    
                const roomName = room.loai_phong ? room.loai_phong.ten : room.name;
                const price = room.gia_cuoi_cung || 0;
                const stars = room.so_sao || 4;
                const size = room.loai_phong?.dien_tich || room.dien_tich || 0;
                const adults = room.loai_phong?.so_nguoi_lon || room.so_nguoi_lon || 2;
                const children = room.loai_phong?.so_tre_em || room.so_tre_em || 1;
                const amenities = room.tien_nghis || [];
                const beds = room.bed_types || [];
                const description = room.mo_ta || room.loai_phong?.mo_ta || '';

                const isBestPrice = price === lowestPrice && price > 0;
                const isBiggest = size === largestSize && size > 0;

                html += `
                    <div class="col-12 col-md-6 col-lg-${rooms.length <= 2 ? '6' : '4'} col-xl-${rooms.length === 4 ? '3' : (rooms.length === 3 ? '4' : '6')}">
                        <div class="compare-card" data-aos="fade-up" data-aos-delay="${index * 100}">
                            ${isBestPrice ? '<div class="best-value-badge"><i class="bi bi-star-fill me-1"></i>Giá tốt nhất</div>' : ''}
                            
                            <button class="remove-btn" onclick="removeRoom(${room.id})" title="Xóa khỏi so sánh">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>

                            <div class="room-image">
                                <img src="${firstImage}" alt="${roomName}">
                                <div class="image-overlay"></div>
                            </div>

                            <div class="card-content">
                                <h4 class="room-title">${roomName}</h4>

                                <div class="stars mb-3">
                                    ${Array(5).fill(0).map((_, i) => 
                                        `<i class="bi bi-star${i < stars ? '-fill' : ''} ${i < stars ? 'text-warning' : 'text-muted'}"></i>`
                                    ).join('')}
                                </div>

                                <div class="price-tag ${isBestPrice ? 'best-price' : ''}">
                                    <div class="price-label">Giá mỗi đêm</div>
                                    <div class="price-value">${new Intl.NumberFormat('vi-VN').format(price)} ₫</div>
                                </div>

                                <div class="features-list">
                                    <div class="feature-item ${isBiggest ? 'highlight' : ''}">
                                        <i class="bi bi-arrows-fullscreen text-primary"></i>
                                        <span>${size} m²</span>
                                        ${isBiggest ? '<span class="badge badge-success-soft ms-2">Rộng nhất</span>' : ''}
                                    </div>
                                    <div class="feature-item">
                                        <i class="bi bi-people-fill text-info"></i>
                                        <span>${adults} người lớn • ${children} trẻ em</span>
                                    </div>
                                    ${beds.length > 0 ? `
                                        <div class="feature-item">
                                            <i class="bi bi-house-door text-success"></i>
                                            <span>${beds.map(b => `${b.quantity}x ${b.name}`).join(', ')}</span>
                                        </div>
                                    ` : ''}
                                </div>

                                ${amenities.length > 0 ? `
                                    <div class="amenities-section">
                                        <h6 class="section-title">Tiện nghi</h6>
                                        <div class="amenities-grid">
                                            ${amenities.slice(0, 6).map(a => `
                                                <div class="amenity-badge">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>${a.ten}</span>
                                                </div>
                                            `).join('')}
                                            ${amenities.length > 6 ? `
                                                <div class="amenity-badge more">
                                                    +${amenities.length - 6} khác
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                ` : ''}

                                ${description ? `
                                    <div class="description-section">
                                        <p class="description-text">${description.substring(0, 120)}${description.length > 120 ? '...' : ''}</p>
                                    </div>
                                ` : ''}

                                <div class="action-buttons">
                                    <a href="/detail-room/${room.id}" class="btn btn-outline-primary btn-block">
                                        <i class="bi bi-eye me-1"></i> Xem chi tiết
                                    </a>
                                    <a href="/account/booking/${room.id}/create" class="btn btn-primary btn-block">
                                        <i class="bi bi-calendar-check me-1"></i> Đặt ngay
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            grid.innerHTML = html;
            
            // Init AOS after rendering cards
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 600,
                    once: true,
                    offset: 50
                });
            }
        }

        window.removeRoom = removeRoom;
        window.clearAll = clearAll;

        loadCompareData();
    });
</script>
@endpush

@push('styles')
<style>
    /* Hero Section */
    .compare-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .hero-icon {
        width: 70px;
        height: 70px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        backdrop-filter: blur(10px);
    }

    .compare-hero h1 {
        color: white;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    /* Empty State */
    .empty-state-icon {
        font-size: 5rem;
        color: #e0e0e0;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }

    /* Action Bar */
    .compare-action-bar {
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .bg-primary-soft {
        background-color: #e7f1ff !important;
    }

    /* Compare Cards */
    .compare-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .compare-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    }

    /* Best Value Badge */
    .best-value-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.875rem;
        z-index: 10;
        box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    /* Remove Button */
    .remove-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.95);
        color: #dc3545;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .remove-btn:hover {
        background: #dc3545;
        color: white;
        transform: rotate(90deg);
    }

    /* Room Image */
    .room-image {
        position: relative;
        height: 250px;
        overflow: hidden;
    }

    .room-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .compare-card:hover .room-image img {
        transform: scale(1.1);
    }

    .image-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60%;
        background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 100%);
    }

    /* Card Content */
    .card-content {
        padding: 1.5rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .room-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #2d3748;
    }

    .stars {
        font-size: 1rem;
    }

    /* Price Tag */
    .price-tag {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .price-tag.best-price {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        animation: glow 2s ease-in-out infinite;
    }

    @keyframes glow {
        0%, 100% { box-shadow: 0 0 20px rgba(245, 87, 108, 0.5); }
        50% { box-shadow: 0 0 30px rgba(245, 87, 108, 0.8); }
    }

    .price-label {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-bottom: 0.25rem;
    }

    .price-value {
        font-size: 1.75rem;
        font-weight: 700;
    }

    /* Features List */
    .features-list {
        margin-bottom: 1.5rem;
    }

    .feature-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
    }

    .feature-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }

    .feature-item.highlight {
        background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
        border-left: 3px solid #00acc1;
    }

    .feature-item i {
        font-size: 1.25rem;
        margin-right: 0.75rem;
        min-width: 24px;
    }

    .badge-success-soft {
        background: #d4edda;
        color: #155724;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
    }

    /* Amenities Section */
    .amenities-section {
        margin-bottom: 1.5rem;
        flex: 1;
    }

    .section-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .amenities-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .amenity-badge {
        display: inline-flex;
        align-items: center;
        background: #f0f4f8;
        padding: 0.375rem 0.75rem;
        border-radius: 20px;
        font-size: 0.813rem;
        color: #4a5568;
        transition: all 0.2s ease;
    }

    .amenity-badge:hover {
        background: #e0e7ef;
        transform: translateY(-2px);
    }

    .amenity-badge i {
        color: #10b981;
        font-size: 0.875rem;
        margin-right: 0.375rem;
    }

    .amenity-badge.more {
        background: #667eea;
        color: white;
        font-weight: 600;
    }

    /* Description */
    .description-section {
        margin-bottom: 1.5rem;
    }

    .description-text {
        color: #718096;
        font-size: 0.875rem;
        line-height: 1.6;
        margin: 0;
    }

    /* Action Buttons */
    .action-buttons {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        margin-top: auto;
    }

    .btn-block {
        width: 100%;
        padding: 0.75rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .room-image {
            height: 200px;
        }

        .room-title {
            font-size: 1.25rem;
        }

        .price-value {
            font-size: 1.5rem;
        }

        .hero-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }

        .compare-hero h1 {
            font-size: 1.75rem;
        }
    }
</style>

<!-- Add AOS Animation Library -->
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
@endpush
