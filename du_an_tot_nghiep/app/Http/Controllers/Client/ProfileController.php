<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }


    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');

            if (!empty($user->avatar) && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $path;
        }

        if (!empty($data['dob'])) {
            try {
                $data['dob'] = Carbon::parse($data['dob'])->format('Y-m-d');
            } catch (\Exception $e) {
                unset($data['dob']);
            }
        }

        $emailChanged = isset($data['email']) && $data['email'] !== $user->email;
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->fill($data);
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification(); 
        }

        return Redirect::route('account.settings')->with('success', 'Cập nhật thông tin thành công.');
    }


    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
