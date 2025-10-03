<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Phong;

class RoomController extends Controller
{
    public function show($id)
    {
        $phong = Phong::with(['loaiPhong','tang','images','tienNghis'])->findOrFail($id);

        $related = Phong::with('images')
            ->where('loai_phong_id', $phong->loai_phong_id)
            ->where('id', '<>', $phong->id)
            ->orderByDesc('created_at') 
            ->take(5)
            ->get();

        return view('detail-room', compact('phong', 'related'));
    }
}
