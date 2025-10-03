<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;
use App\Models\Phong;

class WishlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        $wishlists = Wishlist::with('phong')->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        return view('account.wishlist', compact('wishlists'));
    }

    public function toggle(Request $request, $phongId)
    {
        $user = Auth::user();

        $phong = Phong::find($phongId);
        if (!$phong) {
            return response()->json(['message' => 'Room does not exist'], 404);
        }

        $existing = Wishlist::where('user_id', $user->id)->where('phong_id', $phongId)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['status' => 'removed']);
        }

        $wl = Wishlist::create([
            'user_id' => $user->id,
            'phong_id' => $phongId,
        ]);

        return response()->json(['status' => 'added', 'id' => $wl->id]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $wl = Wishlist::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $wl->delete();

        if (request()->wantsJson()) {
            return response()->json(['status' => 'deleted']);
        }

        return redirect()->route('account.wishlist')->with('success', 'Removed from wishlist.');
    }

    public function clear()
    {
        $user = Auth::user();
        Wishlist::where('user_id', $user->id)->delete();

        return redirect()->route('account.wishlist')->with('success', 'All wishlists deleted.');
    }
}
