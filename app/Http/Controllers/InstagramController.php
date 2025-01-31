<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InstagramController extends Controller
{
    /**
     * Redirect the user to the Instagram authentication page.
     */
    public function redirectToInstagram()
    {
        return Socialite::driver('instagram')->scopes(['user_profile', 'user_media'])->redirect();
    }

    /**
     * Handle the callback from Instagram.
     */
    public function handleInstagramCallback()
    {
        try {
            $instagramUser = Socialite::driver('instagram')->user();

            // Save or update the user's Instagram access token
            $user = Auth::user();
            $user->instagram_access_token = $instagramUser->token;
            $user->instagram_user_id = $instagramUser->id;
            $user->save();

            return redirect()->route('dashboard')->with('success', 'Instagram account connected successfully!');
        } catch (\Exception $e) {
            \Log::error('Instagram OAuth Error: ' . $e->getMessage());
            return redirect()->route('dashboard')->withErrors('Failed to connect Instagram account.');
        }
    }
}
