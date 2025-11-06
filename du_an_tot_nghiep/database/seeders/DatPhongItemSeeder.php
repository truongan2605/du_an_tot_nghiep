<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatPhongItemSeeder extends Seeder
{
    public function run(): void
    {

        $giaTrenDem = 1200000.00;
        $soLuong = 2;
        $soDem = 3;
        $taxesAmount = ($giaTrenDem * $soLuong * $soDem) * 0.1; 

        DB::table('dat_phong_item')->insert([
            [
                'dat_phong_id' => 1,
                'loai_phong_id' => 1, 
                'so_luong' => $soLuong,
                'gia_tren_dem' => $giaTrenDem,
                'so_dem' => $soDem,
                'taxes_amount' => $taxesAmount,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
            [
                'dat_phong_id' => 1,
                'loai_phong_id' => 2, 
                'so_luong' => 1,
                'gia_tren_dem' => 2500000.00,
                'so_dem' => 3,
                'taxes_amount' => (2500000.00 * 1 * 3) * 0.1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
              [
                'dat_phong_id' => 3,
                'loai_phong_id' => 2, 
                'so_luong' => 1,
                'gia_tren_dem' => 2500000.00,
                'so_dem' => 3,
                'taxes_amount' => (2500000.00 * 1 * 3) * 0.1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
              [
                'dat_phong_id' => 4,
                'loai_phong_id' => 2, 
                'so_luong' => 1,
                'gia_tren_dem' => 2500000.00,
                'so_dem' => 3,
                'taxes_amount' => (2500000.00 * 1 * 3) * 0.1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
              [
                'dat_phong_id' => 5,
                'loai_phong_id' => 2, 
                'so_luong' => 1,
                'gia_tren_dem' => 2500000.00,
                'so_dem' => 3,
                'taxes_amount' => (2500000.00 * 1 * 3) * 0.1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
              [
                'dat_phong_id' => 6,
                'loai_phong_id' => 2, 
                'so_luong' => 1,
                'gia_tren_dem' => 2500000.00,
                'so_dem' => 3,
                'taxes_amount' => (2500000.00 * 1 * 3) * 0.1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
           
        ]);
    }
}