<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Phong; 
use App\Models\Tang; 
use App\Models\LoaiPhong;

class PhongSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $loaiStd = LoaiPhong::where('ma', 'STD')->first();
        $loaiDlx = LoaiPhong::where('ma', 'DLX')->first();
        $loaiSui = LoaiPhong::where('ma', 'SUI')->first();

        $tang1 = Tang::where('so_tang', 1)->first();
        $tang2 = Tang::where('so_tang', 2)->first();

        // Thêm phòng Standard
        Phong::create([
            'ma_phong' => 'P101',
            'loai_phong_id' => $loaiStd->id,
            'tang_id' => $tang1->id,
            'suc_chua' => 2,
            'so_giuong' => 1,
            'gia_mac_dinh' => 500000,
            'trang_thai' => 'trong',
        ]);

        // Thêm phòng Deluxe
        Phong::create([
            'ma_phong' => 'P201',
            'loai_phong_id' => $loaiDlx->id,
            'tang_id' => $tang2->id,
            'suc_chua' => 2,
            'so_giuong' => 1,
            'gia_mac_dinh' => 800000,
            'trang_thai' => 'trong',
        ]);

        // Thêm phòng Suite
        Phong::create([
            'ma_phong' => 'P202',
            'loai_phong_id' => $loaiSui->id,
            'tang_id' => $tang2->id,
            'suc_chua' => 4,
            'so_giuong' => 2,
            'gia_mac_dinh' => 1500000,
            'trang_thai' => 'trong',
        ]);
    }
}
