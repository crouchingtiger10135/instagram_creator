<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Socialite;
use App\Models\User;

class InstagramController extends Controller
{
    /**
     * Redirect the user to the Instagram authentication page.
     */
 public function redirectToInstagram()
{
    return Socialite::driver('instagram')
                    ->setScopes(['user_profile'])
                    ->redirect();
}




    /**
     * Handle the callback from Instagram.
     */
    public function handleInstagramCallback()
    {
        try {
            // Retrieve the user information from Instagram
            $instagramUser = Socialite::driver('instagram')->user();

            // Find the authenticated user
            $user = Auth::user();

            // Update user's Instagram details
            $user->instagram_user_id = $instagramUser->getId();
            $user->instagram_access_token = $instagramUser->token;
            $user->save();

            return redirect()->route('dashboard')->with('success', 'Instagram account connected successfully!');
        } catch (\Exception $e) {
            \Log::error('Instagram Callback Error: ' . $e->getMessage());
            return redirect()->route('dashboard')->withErrors('Failed to connect Instagram account.');
        }
    }
}
