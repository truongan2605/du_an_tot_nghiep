<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LoaiPhong;

class LoaiPhongSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LoaiPhong::create([
            'ma' => 'STD',
            'ten' => 'Phòng tiêu chuẩn',
            'mo_ta' => 'Đầy đủ tiện nghi cơ bản',
            'suc_chua' => 2,
            'so_giuong' => 1,
            'gia_mac_dinh' => 500000,
            'so_luong_thuc_te' => 10,
        ]);

        LoaiPhong::create([
            'ma' => 'DLX',
            'ten' => 'Phòng Deluxe',
            'mo_ta' => 'Cao cấp, view đẹp',
            'suc_chua' => 2,
            'so_giuong' => 1,
            'gia_mac_dinh' => 800000,
            'so_luong_thuc_te' => 8,
        ]);

        LoaiPhong::create([
            'ma' => 'SUI',
            'ten' => 'Phòng Suite',
            'mo_ta' => 'Sang trọng, rộng rãi',
            'suc_chua' => 4,
            'so_giuong' => 2,
            'gia_mac_dinh' => 1500000,
            'so_luong_thuc_te' => 4,
        ]);
    }
}