<?php

namespace Database\Seeders;

use App\Models\Tang;
use App\Models\User;
use App\Models\Phong;
use App\Models\TienNghi;
use App\Models\LoaiPhong;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class HotelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * SỬA LỖI: Sử dụng kiểm tra tồn tại (exists) hoặc firstOrCreate 
     * để tránh lỗi trùng lặp email/mã khi chạy lại Seeder.
     */
    public function run(): void
    {
        // 1. Tạo User - Sử dụng if (!exists) để chỉ tạo nếu chưa có
        
        // Tạo admin
        if (!User::where('email', 'admin@hotel.com')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@hotel.com',
                'password' => Hash::make('password'),
                'vai_tro' => 'admin',
                'is_active' => true,
            ]);
        }

        // Tạo nhân viên
        if (!User::where('email', 'staff@hotel.com')->exists()) {
            User::create([
                'name' => 'Nhân viên lễ tân',
                'email' => 'staff@hotel.com',
                'password' => Hash::make('password'),
                'vai_tro' => 'nhan_vien',
                'phong_ban' => 'Lễ tân',
                'is_active' => true,
            ]);
        }

        // Tạo khách hàng
        if (!User::where('email', 'customer@example.com')->exists()) {
            User::create([
                'name' => 'Khách hàng mẫu',
                'email' => 'customer@example.com',
                'password' => Hash::make('password'),
                'vai_tro' => 'khach_hang',
                'is_active' => true,
            ]);
        }
        
        // --- Phần tạo Tầng, Loại Phòng, Tiện Nghi, Phòng sử dụng firstOrCreate ---
        
        // Tạo tầng
        $tang1 = Tang::firstOrCreate(
            ['so_tang' => 1],
            [
                'ten' => 'Tầng 1',
                'ghi_chu' => 'Tầng trệt - Lobby và phòng họp',
            ]
        );

        $tang2 = Tang::firstOrCreate(
            ['so_tang' => 2],
            [
                'ten' => 'Tầng 2',
                'ghi_chu' => 'Phòng nghỉ tiêu chuẩn',
            ]
        );

        $tang3 = Tang::firstOrCreate(
            ['so_tang' => 3],
            [
                'ten' => 'Tầng 3',
                'ghi_chu' => 'Phòng nghỉ cao cấp',
            ]
        );

        // Tạo loại phòng
        $loaiPhong1 = LoaiPhong::firstOrCreate(
            ['ma' => 'STD'],
            [
                'ten' => 'Phòng tiêu chuẩn',
                'mo_ta' => 'Phòng nghỉ tiêu chuẩn với đầy đủ tiện nghi cơ bản',
                'suc_chua' => 2,
                'so_giuong' => 1,
                'gia_mac_dinh' => 500000,
                'so_luong_thuc_te' => 10,
            ]
        );

        $loaiPhong2 = LoaiPhong::firstOrCreate(
            ['ma' => 'DLX'],
            [
                'ten' => 'Phòng deluxe',
                'mo_ta' => 'Phòng nghỉ cao cấp với view đẹp',
                'suc_chua' => 2,
                'so_giuong' => 1,
                'gia_mac_dinh' => 800000,
                'so_luong_thuc_te' => 8,
            ]
        );

        $loaiPhong3 = LoaiPhong::firstOrCreate(
            ['ma' => 'SUITE'],
            [
                'ten' => 'Phòng suite',
                'mo_ta' => 'Phòng suite sang trọng với không gian rộng rãi',
                'suc_chua' => 4,
                'so_giuong' => 2,
                'gia_mac_dinh' => 1500000,
                'so_luong_thuc_te' => 4,
            ]
        );

        // Tạo tiện nghi
        $tienNghi1 = TienNghi::firstOrCreate(
            ['ten' => 'WiFi miễn phí'],
            [
                'mo_ta' => 'Kết nối internet tốc độ cao',
                'icon' => 'wifi',
                'active' => true,
            ]
        );

        $tienNghi2 = TienNghi::firstOrCreate(
            ['ten' => 'Điều hòa'],
            [
                'mo_ta' => 'Hệ thống điều hòa hiện đại',
                'icon' => 'ac',
                'active' => true,
            ]
        );

        $tienNghi3 = TienNghi::firstOrCreate(
            ['ten' => 'TV'],
            [
                'mo_ta' => 'Tivi màn hình phẳng 32 inch',
                'icon' => 'tv',
                'active' => true,
            ]
        );

        $tienNghi4 = TienNghi::firstOrCreate(
            ['ten' => 'Tủ lạnh mini'],
            [
                'mo_ta' => 'Tủ lạnh mini trong phòng',
                'icon' => 'fridge',
                'active' => true,
            ]
        );

        // Tạo phòng (Dùng firstOrCreate để đảm bảo mã phòng là duy nhất)
        
        $phongStd = [];
        for ($i = 1; $i <= 5; $i++) {
            $maPhong = '20' . $i;
            $phongStd[] = Phong::firstOrCreate(
                ['ma_phong' => $maPhong],
                [
                    'loai_phong_id' => $loaiPhong1->id,
                    'tang_id' => $tang2->id,
                    'suc_chua' => 2,
                    'so_giuong' => 1,
                    'gia_mac_dinh' => 500000,
                    'trang_thai' => 'trong',
                ]
            );
        }

        $phongDlx = [];
        for ($i = 1; $i <= 4; $i++) {
            $maPhong = '30' . $i;
            $phongDlx[] = Phong::firstOrCreate(
                ['ma_phong' => $maPhong],
                [
                    'loai_phong_id' => $loaiPhong2->id,
                    'tang_id' => $tang3->id,
                    'suc_chua' => 2,
                    'so_giuong' => 1,
                    'gia_mac_dinh' => 800000,
                    'trang_thai' => 'trong',
                ]
            );
        }

        $phongSuite = [];
        for ($i = 1; $i <= 2; $i++) {
            $maPhong = '40' . $i;
            $phongSuite[] = Phong::firstOrCreate(
                ['ma_phong' => $maPhong],
                [
                    'loai_phong_id' => $loaiPhong3->id,
                    'tang_id' => $tang3->id,
                    'suc_chua' => 4,
                    'so_giuong' => 2,
                    'gia_mac_dinh' => 1500000,
                    'trang_thai' => 'trong',
                ]
            );
        }

        // 3. Gán tiện nghi cho phòng
        $wifi = $tienNghi1; 
        $ac = $tienNghi2; 
        $tv = $tienNghi3; 
        $fridge = $tienNghi4; 

        foreach ($phongStd as $phong) {
            $phong->tienNghis()->syncWithoutDetaching([$wifi->id, $ac->id, $tv->id]);
        }

        foreach ($phongDlx as $phong) {
            $phong->tienNghis()->syncWithoutDetaching([$wifi->id, $ac->id, $tv->id, $fridge->id]);
        }

        foreach ($phongSuite as $phong) {
            $phong->tienNghis()->syncWithoutDetaching([$wifi->id, $ac->id, $tv->id, $fridge->id]);
        }
    }
}
