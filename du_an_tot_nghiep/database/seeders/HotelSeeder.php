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
     */
    public function run(): void
    {
        // Tạo admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@hotel.com',
            'password' => Hash::make('password'),
            'vai_tro' => 'admin',
            'is_active' => true,
        ]);

        // Tạo nhân viên
        User::create([
            'name' => 'Nhân viên lễ tân',
            'email' => 'staff@hotel.com',
            'password' => Hash::make('password'),
            'vai_tro' => 'nhan_vien',
            'phong_ban' => 'Lễ tân',
            'is_active' => true,
        ]);



        // Tạo khách hàng
        User::create([
            'name' => 'Khách hàng mẫu',
            'email' => 'customer@example.com',
            'password' => Hash::make('password'),
            'vai_tro' => 'khach_hang',
            'is_active' => true,
        ]);

        // Tạo tầng
        $tang1 = Tang::create([
            'so_tang' => 1,
            'ten' => 'Tầng 1',
            'ghi_chu' => 'Tầng trệt - Lobby và phòng họp',
        ]);

        $tang2 = Tang::create([
            'so_tang' => 2,
            'ten' => 'Tầng 2',
            'ghi_chu' => 'Phòng nghỉ tiêu chuẩn',
        ]);

        $tang3 = Tang::create([
            'so_tang' => 3,
            'ten' => 'Tầng 3',
            'ghi_chu' => 'Phòng nghỉ cao cấp',
        ]);

        // Tạo loại phòng
        $loaiPhong1 = LoaiPhong::create([
            'ma' => 'STD',
            'ten' => 'Phòng tiêu chuẩn',
            'mo_ta' => 'Phòng nghỉ tiêu chuẩn với đầy đủ tiện nghi cơ bản',
            'suc_chua' => 2,
            'so_giuong' => 1,
            'gia_mac_dinh' => 500000,
            'so_luong_thuc_te' => 10,
        ]);

        $loaiPhong2 = LoaiPhong::create([
            'ma' => 'DLX',
            'ten' => 'Phòng deluxe',
            'mo_ta' => 'Phòng nghỉ cao cấp với view đẹp',
            'suc_chua' => 2,
            'so_giuong' => 1,
            'gia_mac_dinh' => 800000,
            'so_luong_thuc_te' => 8,
        ]);

        $loaiPhong3 = LoaiPhong::create([
            'ma' => 'SUITE',
            'ten' => 'Phòng suite',
            'mo_ta' => 'Phòng suite sang trọng với không gian rộng rãi',
            'suc_chua' => 4,
            'so_giuong' => 2,
            'gia_mac_dinh' => 1500000,
            'so_luong_thuc_te' => 4,
        ]);

        // Tạo tiện nghi
        $tienNghi1 = TienNghi::create([
            'ten' => 'WiFi miễn phí',
            'mo_ta' => 'Kết nối internet tốc độ cao',
            'icon' => 'wifi',
            'active' => true,
        ]);

        $tienNghi2 = TienNghi::create([
            'ten' => 'Điều hòa',
            'mo_ta' => 'Hệ thống điều hòa hiện đại',
            'icon' => 'ac',
            'active' => true,
        ]);

        $tienNghi3 = TienNghi::create([
            'ten' => 'TV',
            'mo_ta' => 'Tivi màn hình phẳng 32 inch',
            'icon' => 'tv',
            'active' => true,
        ]);

        $tienNghi4 = TienNghi::create([
            'ten' => 'Tủ lạnh mini',
            'mo_ta' => 'Tủ lạnh mini trong phòng',
            'icon' => 'fridge',
            'active' => true,
        ]);

        // Tạo phòng
        $phongStd = [];
        for ($i = 1; $i <= 5; $i++) {
            $phongStd[] = Phong::create([
                'ma_phong' => '20' . $i,
                'loai_phong_id' => $loaiPhong1->id,
                'tang_id' => $tang2->id,
                'suc_chua' => 2,
                'so_giuong' => 1,
                'gia_mac_dinh' => 500000,
                'trang_thai' => 'trong',
            ]);
        }

        $phongDlx = [];
        for ($i = 1; $i <= 4; $i++) {
            $phongDlx[] = Phong::create([
                'ma_phong' => '30' . $i,
                'loai_phong_id' => $loaiPhong2->id,
                'tang_id' => $tang3->id,
                'suc_chua' => 2,
                'so_giuong' => 1,
                'gia_mac_dinh' => 800000,
                'trang_thai' => 'trong',
            ]);
        }

        $phongSuite = [];
        for ($i = 1; $i <= 2; $i++) {
            $phongSuite[] = Phong::create([
                'ma_phong' => '40' . $i,
                'loai_phong_id' => $loaiPhong3->id,
                'tang_id' => $tang3->id,
                'suc_chua' => 4,
                'so_giuong' => 2,
                'gia_mac_dinh' => 1500000,
                'trang_thai' => 'trong',
            ]);
        }

        // Gán tiện nghi cho phòng
        $wifi = $tienNghi1; // WiFi miễn phí
        $ac = $tienNghi2;   // Điều hòa
        $tv = $tienNghi3;   // TV
        $fridge = $tienNghi4; // Tủ lạnh mini

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
