<?php

namespace App\Observers;

use App\Models\Phong;
use App\Models\LoaiPhong;

class PhongObserver
{
    public function created(Phong $phong)
    {
        if ($phong->loai_phong_id) {
            LoaiPhong::refreshSoLuongThucTe($phong->loai_phong_id);
        }
    }

    public function updated(Phong $phong)
    {
        if ($phong->wasChanged('loai_phong_id')) {
            $old = $phong->getOriginal('loai_phong_id');
            $new = $phong->loai_phong_id;

            if ($old) LoaiPhong::refreshSoLuongThucTe($old);
            if ($new) LoaiPhong::refreshSoLuongThucTe($new);
        }

    }

    public function deleted(Phong $phong)
    {
        $old = $phong->loai_phong_id;
        if ($old) LoaiPhong::refreshSoLuongThucTe($old);
    }

    public function restored(Phong $phong)
    {
        if ($phong->loai_phong_id) {
            LoaiPhong::refreshSoLuongThucTe($phong->loai_phong_id);
        }
    }

    public function forceDeleted(Phong $phong)
    {
        if ($phong->loai_phong_id) {
            LoaiPhong::refreshSoLuongThucTe($phong->loai_phong_id);
        }
    }
}
