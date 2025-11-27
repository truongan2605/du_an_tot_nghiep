<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Thống Kê Phòng - {{ $selectedDate->locale('vi')->translatedFormat('F Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0; color: #666; }
        .section { margin-bottom: 20px; }
        .section-title { background: #007bff; color: white; padding: 8px; font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 10px; }
        .bg-success { background: #28a745; color: white; }
        .bg-warning { background: #ffc107; color: #000; }
        .bg-danger { background: #dc3545; color: white; }
        .footer { text-align: center; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>BÁO CÁO THỐNG KÊ PHÒNG & BOOKING</h1>
        <p><strong>Tháng:</strong> {{ $selectedDate->locale('vi')->translatedFormat('F Y') }}</p>
        <p><strong>Ngày xuất:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- Summary by Room Type --}}
    <div class="section">
        <div class="section-title">THỐNG KÊ THEO LOẠI PHÒNG</div>
        <table>
            <thead>
                <tr>
                    <th>Loại Phòng</th>
                    <th class="text-center">Tổng Phòng</th>
                    <th class="text-center">Bookings</th>
                    <th class="text-center">Tỷ Lệ (%)</th>
                    <th class="text-end">Doanh Thu (₫)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roomTypeStats as $roomType)
                    <tr>
                        <td><strong>{{ $roomType->ten }}</strong></td>
                        <td class="text-center">{{ $roomType->total_rooms }}</td>
                        <td class="text-center">{{ $roomType->total_bookings }}</td>
                        <td class="text-center">
                            <span class="badge 
                                @if($roomType->occupancy_rate >= 70) bg-success
                                @elseif($roomType->occupancy_rate >= 40) bg-warning
                                @else bg-danger
                                @endif">
                                {{ $roomType->occupancy_rate }}%
                            </span>
                        </td>
                        <td class="text-end">{{ number_format($roomType->total_revenue, 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">Không có dữ liệu</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Detailed Room Stats --}}
    <div class="section">
        <div class="section-title">CHI TIẾT TỪNG PHÒNG</div>
        <table>
            <thead>
                <tr>
                    <th>Phòng</th>
                    <th>Loại</th>
                    <th class="text-center">Bookings</th>
                    <th class="text-center">Tỷ Lệ (%)</th>
                    <th class="text-end">Doanh Thu (₫)</th>
                    <th class="text-center">Trạng Thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roomStats as $room)
                    <tr>
                        <td><strong>{{ $room->ma_phong }}</strong></td>
                        <td>{{ $room->ten }}</td>
                        <td class="text-center">{{ $room->booking_count }}</td>
                        <td class="text-center">{{ $room->occupancy_rate }}%</td>
                        <td class="text-end">{{ number_format($room->revenue, 0) }}</td>
                        <td class="text-center">{{ ucfirst($room->trang_thai) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Không có dữ liệu</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Hệ thống quản lý khách sạn | Được tạo tự động bởi Room Analytics | {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    {{-- Auto-trigger print dialog --}}
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
