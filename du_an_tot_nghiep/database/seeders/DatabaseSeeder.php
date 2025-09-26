<?php

namespace Database\Seeders;

use App\Models\Tang;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            HotelSeeder::class,
            TienNghiSeeder::class,
            TangSeeder::class,
            LoaiPhongSeeder::class,
            PhongSeeder::class,
        ]);
    }
}
