<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Nguyễn Văn A',
                'email' => 'customer1@example.com',
                'password' => Hash::make('password'),
                'vai_tro' => 'khach_hang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Trần Thị B',
                'email' => 'customer2@example.com',
                'password' => Hash::make('password'),
                'vai_tro' => 'khach_hang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lê Văn C',
                'email' => 'customer3@example.com',
                'password' => Hash::make('password'),
                'vai_tro' => 'khach_hang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Phạm Thị D',
                'email' => 'customer4@example.com',
                'password' => Hash::make('password'),
                'vai_tro' => 'khach_hang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hoàng Văn E',
                'email' => 'customer5@example.com',
                'password' => Hash::make('password'),
                'vai_tro' => 'khach_hang',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($customers as $customer) {
            User::create($customer);
        }

        $this->command->info('Created 5 test customers');
    }
}