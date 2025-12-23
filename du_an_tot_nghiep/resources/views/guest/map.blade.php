@extends('layouts.app')

@section('title', 'Vị trí khách sạn')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map {
        height: 500px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        border: 3px solid #fff;
    }
    
    .map-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 80px 0;
        position: relative;
        overflow: hidden;
    }
    
    .map-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h100v100H0z" fill="none"/><path d="M0 50h100M50 0v100" stroke="white" stroke-width="0.5" opacity="0.1"/></svg>');
        opacity: 0.1;
    }
    
    .map-header {
        text-align: center;
        margin-bottom: 50px;
        position: relative;
        z-index: 1;
    }
    
    .map-header h2 {
        color: white;
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 15px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    
    .map-header p {
        color: rgba(255,255,255,0.9);
        font-size: 1.1rem;
    }
    
    .info-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        margin-top: 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    }
    
    .info-item {
        display: flex;
        align-items: start;
        padding: 20px;
        border-radius: 12px;
        transition: background 0.3s ease;
    }
    
    .info-item:hover {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .info-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
        margin-right: 20px;
    }
    
    .icon-address {
        background: linear-gradient(135deg, #fecaca 0%, #ef4444 100%);
        color: #7f1d1d;
    }
    
    .icon-phone {
        background: linear-gradient(135deg, #bfdbfe 0%, #3b82f6 100%);
        color: #1e3a8a;
    }
    
    .icon-email {
        background: linear-gradient(135deg, #bbf7d0 0%, #22c55e 100%);
        color: #14532d;
    }
    
    .icon-time {
        background: linear-gradient(135deg, #fde68a 0%, #f59e0b 100%);
        color: #78350f;
    }
    
    .info-content h6 {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 5px;
    }
    
    .info-content p {
        color: #6b7280;
        margin: 0;
        font-size: 0.95rem;
    }
    
    .info-content a {
        color: #3b82f6;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .info-content a:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    
    .btn-direction {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 40px;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        border: none;
    }
    
    .btn-direction:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        color: white;
    }
    
    .btn-direction i {
        font-size: 1.2rem;
    }
    
    /* Custom Leaflet Popup */
    .leaflet-popup-content-wrapper {
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }
    
    .leaflet-popup-content {
        text-align: center;
        padding: 15px;
        min-width: 200px;
    }
    
    .popup-hotel-name {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .popup-address {
        color: #6b7280;
        margin-bottom: 12px;
        font-size: 0.9rem;
    }
    
    .popup-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        text-decoration: none;
        display: inline-block;
        font-size: 0.9rem;
        transition: transform 0.3s ease;
    }
    
    .popup-btn:hover {
        transform: scale(1.05);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="map-section">
    <div class="container">
        <!-- Header -->
        <div class="map-header">
            <h2>
                <i class="fas fa-map-marker-alt me-3"></i>
                Tìm đường đến khách sạn
            </h2>
            <p>Chúng tôi luôn sẵn sàng đón tiếp bạn 24/7</p>
        </div>

        <!-- Map -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div id="map"></div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="info-card">
                    <div class="row g-4">
                        <!-- Địa chỉ -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon icon-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <h6>Địa chỉ</h6>
                                    <p>123 Hoàn Kiếm, Hà Nội, Việt Nam</p>
                                </div>
                            </div>
                        </div>

                        <!-- Điện thoại -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon icon-phone">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="info-content">
                                    <h6>Điện thoại</h6>
                                    <p><a href="tel:0123456789">0123 456 789</a></p>
                                </div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon icon-email">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="info-content">
                                    <h6>Email</h6>
                                    <p><a href="mailto:info@hotel.com">info@hotel.com</a></p>
                                </div>
                            </div>
                        </div>

                        <!-- Giờ làm việc -->
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon icon-time">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="info-content">
                                    <h6>Giờ làm việc</h6>
                                    <p>24/7 - Luôn sẵn sàng phục vụ</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Direction Button -->
                    <div class="text-center mt-4">
                        <a href="https://www.google.com/maps/dir//21.0285,105.8542" 
                           target="_blank" 
                           class="btn-direction">
                            <i class="fas fa-directions"></i>
                            Chỉ đường đến khách sạn
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // ✅ TỌA ĐỘ KHÁCH SẠN - THAY ĐỔI THEO VỊ TRÍ THẬT CỦA BẠN
    // Cách lấy tọa độ: Vào Google Maps → Click chuột phải vào vị trí → Copy tọa độ
  const hotelLat = 21.0124;     // Vĩ độ (Latitude)
const hotelLng = 105.5256;    // Kinh độ (Longitude)
const hotelName = 'Khách sạn FPT';  // Tên khách sạn
const hotelAddress = 'Tòa nhà FPT Polytechnic, Trịnh Văn Bô, Nam Từ Liêm, Hà Nội';  // Địa chỉ
    
    // Khởi tạo bản đồ
    const map = L.map('map').setView([hotelLat, hotelLng], 15);
    
    // Thêm tile layer (bản đồ nền) - OpenStreetMap miễn phí
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);
    
    // Tạo icon tùy chỉnh (nếu muốn dùng ảnh riêng)
    const hotelIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',  // Icon khách sạn
        iconSize: [50, 50],        // Kích thước icon
        iconAnchor: [25, 50],      // Điểm neo (giữa dưới icon)
        popupAnchor: [0, -50]      // Vị trí popup xuất hiện
    });
    
    // Thêm marker (điểm đánh dấu)
    const marker = L.marker([hotelLat, hotelLng], { 
        icon: hotelIcon,
        title: hotelName  // Tooltip khi hover
    }).addTo(map);
    
    // Tạo popup khi click vào marker
    const popupContent = `
        <div style="text-align: center; padding: 10px;">
            <div class="popup-hotel-name">🏨 ${hotelName}</div>
            <div class="popup-address">${hotelAddress}</div>
            <a href="https://www.google.com/maps/dir//${hotelLat},${hotelLng}" 
               target="_blank" 
               class="popup-btn">
                <i class="fas fa-directions me-1"></i>Chỉ đường
            </a>
        </div>
    `;
    
    marker.bindPopup(popupContent).openPopup();
    
    // Thêm vùng tròn hiển thị khu vực xung quanh (500m)
    L.circle([hotelLat, hotelLng], {
        color: '#667eea',           // Màu viền
        fillColor: '#667eea',       // Màu nền
        fillOpacity: 0.15,          // Độ trong suốt
        radius: 500                 // Bán kính 500m
    }).addTo(map);
    
    // ✅ BẬT/TẮT CHẾ ĐỘ TOÀN MÀN HÌNH
    map.addControl(new L.Control.Fullscreen());
</script>
@endpush