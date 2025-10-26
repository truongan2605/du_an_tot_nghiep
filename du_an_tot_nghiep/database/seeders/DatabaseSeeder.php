<?php

namespace Database\Seeders;

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
            DatPhongSeeder::class, 
            TangSeeder::class,
            LoaiPhongSeeder::class,
            PhongSeeder::class,
            DatPhongItemSeeder::class,
        ]);
    }
}
