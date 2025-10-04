<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Phong;

class HotelController extends Controller
{
    public function index(){
        $phongs = Phong::with(['loaiPhong', 'tang'])->get();
        return view('client.hotel.index', compact('phongs'));
    }
}