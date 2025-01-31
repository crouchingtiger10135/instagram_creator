<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class ImageController extends Controller
{
    /**
     * Display the dashboard with images.
     */
    public function index()
    {
        // Fetch images for the authenticated user, ordered by position ascending
        $images = Image::where('user_id', Auth::id())
                       ->orderBy('position', 'asc')
                       ->get();

        return view('dashboard', compact('images'));
    }

    /**
     * Store newly uploaded images (supports multiple).
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'photos'   => 'required|array', // ensures 'photos' is an array
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:12048',
            'caption'  => 'nullable|string|max:255',
        ]);

        // If 'photos' is missing or empty
        if (!$request->hasFile('photos')) {
            return back()->withErrors('Please select at least one image to upload.');
        }

        // Loop through each file
        foreach ($request->file('photos') as $photo) {
            $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            
            // Store the image under storage/app/public/images/
            Storage::disk('public')->put('images/' . $filename, file_get_contents($photo));

            // Increment all existing positions for the current user
            Image::where('user_id', Auth::id())->increment('position');

            // Then insert the new image at position = 1
            Image::create([
                'user_id'   => Auth::id(),
                'file_path' => 'images/' . $filename,
                'caption'   => $request->input('caption'),
                'position'  => 1,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Images uploaded successfully!');
    }

    /**
     * Reorder images based on the new order received from the frontend.
     */
    public function reorder(Request $request)
    {
        $orderedIds = $request->input('orderedIds');

        if (!is_array($orderedIds)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data format.'], 400);
        }

        $userId = Auth::id();

        // Begin transaction to ensure all updates succeed
        DB::beginTransaction();

        try {
            foreach ($orderedIds as $index => $imageId) {
                // Update only if the image belongs to the authenticated user
                Image::where('id', $imageId)
                     ->where('user_id', $userId)
                     ->update(['position' => $index + 1]);
            }

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Image Reorder Error: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Failed to reorder images.'], 500);
        }
    }

    /**
     * (Optional) Show the form for editing a single image.
     */
       public function edit(Image $image)
    {
        return view('edit', compact('image'));
    }
    

    /**
     * (Optional) Update a single image in storage.
     */
    public function update(Request $request, Image $image)
    {
        // $this->authorize('update', $image);

        $request->validate([
            'caption' => 'nullable|string|max:255',
            'photo'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // If a new photo is provided, replace the old one
        if ($request->hasFile('photo')) {
            Storage::disk('public')->delete($image->file_path);

            $newImage = $request->file('photo');
            $filename = time() . '.' . $newImage->getClientOriginalExtension();
            $newFilePath = 'images/' . $filename;

            Storage::disk('public')->put($newFilePath, file_get_contents($newImage));

            $image->file_path = $newFilePath;
        }

        $image->caption = $request->input('caption');
        $image->save();

        return redirect()->route('dashboard')->with('success', 'Image updated successfully!');
    }

    /**
     * (Optional) Remove a single image from storage.
     */
    public function destroy(Image $image)
    {
        // $this->authorize('delete', $image);

        // Delete the image from storage
        Storage::disk('public')->delete($image->file_path);

        // Delete the image record from the database
        $image->delete();

        return redirect()->route('dashboard')->with('success', 'Image deleted successfully!');
    }

    /**
     * (Optional) Import the last 9 images from Instagram (if you have this feature).
     */
    public function importInstagramImages()
    {
        $user = Auth::user();

        if (!$user->instagram_access_token) {
            return redirect()->route('instagram.auth')
                             ->withErrors('Please connect your Instagram account first.');
        }

        $response = Http::get('https://graph.instagram.com/me/media', [
            'fields'      => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp',
            'access_token'=> $user->instagram_access_token,
            'limit'       => 9,
        ]);

        if ($response->failed()) {
            \Log::error('Instagram API Error: ' . $response->body());
            return back()->withErrors('Failed to fetch Instagram images.');
        }

        $media = $response->json()['data'];
        DB::beginTransaction();

        try {
            foreach ($media as $item) {
                // Only process IMAGE media types
                if ($item['media_type'] !== 'IMAGE') {
                    continue;
                }

                // Check if already imported
                $existingImage = Image::where('instagram_media_id', $item['id'])
                                      ->where('user_id', $user->id)
                                      ->first();
                if ($existingImage) {
                    continue; // Skip
                }

                // Download
                $imageContent = file_get_contents($item['media_url']);
                if ($imageContent === false) {
                    \Log::warning("Failed to download image: {$item['media_url']}");
                    continue;
                }

                $ext = pathinfo($item['media_url'], PATHINFO_EXTENSION);
                $imageName = 'instagram/' . $item['id'] . '.' . $ext;
                Storage::disk('public')->put($imageName, $imageContent);

                // Position logic for new images
                Image::where('user_id', $user->id)->increment('position');

                Image::create([
                    'user_id'           => $user->id,
                    'instagram_media_id'=> $item['id'],
                    'file_path'         => $imageName,
                    'caption'           => $item['caption'] ?? '',
                    'position'          => 1,
                ]);
            }

            DB::commit();
            return back()->with('success', 'Instagram images imported successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Instagram Import Error: ' . $e->getMessage());
            return back()->withErrors('Failed to import Instagram images.');
        }
    }
}
