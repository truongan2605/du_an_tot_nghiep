<?php

namespace App\Services;

use App\Models\Phong;
use Carbon\Carbon;

class PricingService
{
    const WEEKEND_FACTOR = 1.10; // +10%

    /** T6, T7, CN coi như cuối tuần */
    public function isWeekend(Carbon $date): bool
    {
        // ISO: 1=Mon ... 5=Fri, 6=Sat, 7=Sun
        return in_array($date->dayOfWeekIso, [5, 6, 7], true);
    }

    /**
     * Tính tiền phòng cho 1 khoảng ngày, đã +10% cho T6–T7–CN
     *
     * @return array{
     *   total_room_base: float,               // tổng tiền phòng (mọi phòng * mọi đêm, chưa phụ thu người & addons)
     *   nights: int,
     *   base_per_room: float,                // giá gốc 1 phòng / đêm (ngày thường)
     *   avg_per_night_all_rooms: float,      // trung bình / đêm cho tất cả phòng
     *   per_night: array<int,array{
     *      date:string,is_weekend:bool,factor:float,price_per_room:float
     *   }>
     * }
     */
    public function roomBaseForRange(Phong $phong, Carbon $from, Carbon $to, int $roomsCount = 1): array
    {
        $basePerRoom = (float)($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);

        $cursor   = $from->copy();
        $total    = 0.0;
        $perNight = [];
        $nights   = 0;

        while ($cursor < $to) {
            $nights++;
            $isWeekend = $this->isWeekend($cursor);
            $factor    = $isWeekend ? self::WEEKEND_FACTOR : 1.0;

            $pricePerRoomThisNight = $basePerRoom * $factor;
            $total += $pricePerRoomThisNight * max(1, $roomsCount);

            $perNight[] = [
                'date'          => $cursor->toDateString(),
                'is_weekend'    => $isWeekend,
                'factor'        => $factor,
                'price_per_room'=> $pricePerRoomThisNight,
            ];

            $cursor->addDay();
        }

        return [
            'total_room_base'        => $total,
            'nights'                 => $nights,
            'base_per_room'          => $basePerRoom,
            'avg_per_night_all_rooms'=> $nights ? $total / $nights : 0,
            'per_night'              => $perNight,
        ];
    }
}
