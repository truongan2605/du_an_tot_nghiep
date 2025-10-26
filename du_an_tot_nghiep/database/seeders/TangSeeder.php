<?php

namespace Database\Seeders;

use App\Models\Tang;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        Tang::create([
            'so_tang' => 1,
            'ten' => 'Tầng 1',
            'ghi_chu' => 'Khu lễ tân và dịch vụ',
        ]);

        Tang::create([
            'so_tang' => 2,
            'ten' => 'Tầng 2',
            'ghi_chu' => 'Phòng tiêu chuan',
        ]);

        Tang::create([
            'so_tang' => 3,
            'ten' => 'Tầng 3',
            'ghi_chu' => 'Phòng Deluxe & Suite',
        ]);
    }
}