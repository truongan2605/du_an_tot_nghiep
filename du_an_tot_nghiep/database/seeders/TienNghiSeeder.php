<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TienNghi;
use Smknstd\FakerPicsumImages\FakerPicsumImagesProvider;

class TienNghiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake();
        $faker->addProvider(new FakerPicsumImagesProvider($faker));

        $tienNghis = [
            [
                'ten' => 'WiFi miễn phí',
                'mo_ta' => 'Kết nối internet tốc độ cao miễn phí trong toàn bộ khách sạn',
                'active' => true,
            ],
            [
                'ten' => 'Điều hòa nhiệt độ',
                'mo_ta' => 'Hệ thống điều hòa hiện đại, có thể điều chỉnh nhiệt độ theo ý muốn',
                'active' => true,
            ],
            [
                'ten' => 'TV màn hình phẳng',
                'mo_ta' => 'Tivi màn hình phẳng với nhiều kênh truyền hình',
                'active' => true,
            ],
            [
                'ten' => 'Tủ lạnh mini',
                'mo_ta' => 'Tủ lạnh mini để bảo quản đồ ăn và thức uống',
                'active' => true,
            ],
            [
                'ten' => 'Bồn tắm',
                'mo_ta' => 'Bồn tắm rộng rãi với vòi sen hiện đại',
                'active' => true,
            ],
            [
                'ten' => 'Bàn làm việc',
                'mo_ta' => 'Bàn làm việc rộng rãi với ổ cắm điện tiện lợi',
                'active' => true,
            ],
            [
                'ten' => 'Dịch vụ phòng 24/7',
                'mo_ta' => 'Dịch vụ phòng 24 giờ mỗi ngày',
                'active' => true,
            ],
            [
                'ten' => 'Két an toàn',
                'mo_ta' => 'Két an toàn điện tử để bảo quản tài sản quý giá',
                'active' => true,
            ],
            [
                'ten' => 'Máy pha cà phê',
                'mo_ta' => 'Máy pha cà phê tự động trong phòng',
                'active' => true,
            ],
            [
                'ten' => 'Ban công/Loggia',
                'mo_ta' => 'Ban công hoặc loggia với view đẹp',
                'active' => true,
            ],
        ];

        $pathImages = storage_path('app/public/tien-nghi');
        if(!file_exists($pathImages)) mkdir($pathImages, 0777, true);

        foreach ($tienNghis as $tienNghi) {
            TienNghi::create(array_merge($tienNghi, [
                'gia' => $faker->numberBetween(10000, 2000000),
                'icon' => basename($pathImages) . '/' . $faker->image(dir: $pathImages, width: 128, height: 128, isFullPath: false, imageExtension: FakerPicsumImagesProvider::WEBP_IMAGE),
            ]));
        }
    }
}






















