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
        $images = Image::where('user_id', auth()->id())
                       ->orderBy('position', 'asc')
                       ->get();

        return view('dashboard', compact('images'));
    }

    /**
     * Store a newly uploaded image.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'caption' => 'nullable|string|max:255',
        ]);

        // Handle the uploaded image
        if ($request->hasFile('photo')) {
            $image = $request->file('photo');
            $filename = time() . '.' . $image->getClientOriginalExtension();

            // Optional: Image Optimization (e.g., resize using Intervention Image)
            // $resizedImage = \Intervention\Image\Facades\Image::make($image)->resize(1200, null, function ($constraint) {
            //     $constraint->aspectRatio();
            //     $constraint->upsize();
            // })->encode();
            // Storage::disk('public')->put('images/' . $filename, $resizedImage);

            // Directly store the image
            Storage::disk('public')->put('images/' . $filename, file_get_contents($image));

            // Assign position (new images first)
            Image::where('user_id', auth()->id())->increment('position');
            $newPosition = 1;

            // Create image record
            Image::create([
                'user_id' => auth()->id(),
                'file_path' => 'images/' . $filename,
                'caption' => $request->input('caption'),
                'position' => $newPosition,
            ]);

            return redirect()->route('dashboard')->with('success', 'Image uploaded successfully!');
        }

        return back()->withErrors('Please select an image to upload.');
    }

    /**
     * Import the last 9 images from Instagram.
     */
    public function importInstagramImages()
    {
        $user = Auth::user();

        // Check if user has connected Instagram
        if (!$user->instagram_access_token) {
            return redirect()->route('instagram.auth')->withErrors('Please connect your Instagram account first.');
        }

        // Fetch media from Instagram
        $response = Http::get('https://graph.instagram.com/me/media', [
            'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp',
            'access_token' => $user->instagram_access_token,
            'limit' => 9,
        ]);

        if ($response->failed()) {
            \Log::error('Instagram API Error: ' . $response->body());
            return back()->withErrors('Failed to fetch Instagram images.');
        }

        $media = $response->json()['data'];

        // Begin transaction
        DB::beginTransaction();

        try {
            foreach ($media as $item) {
                // Only process IMAGE media types
                if ($item['media_type'] !== 'IMAGE') {
                    continue;
                }

                // Check if the image already exists (by Instagram media ID)
                $existingImage = Image::where('instagram_media_id', $item['id'])
                                      ->where('user_id', $user->id)
                                      ->first();

                if ($existingImage) {
                    continue; // Skip already imported images
                }

                // Download the image
                $imageContent = file_get_contents($item['media_url']);
                if ($imageContent === false) {
                    \Log::warning("Failed to download image: {$item['media_url']}");
                    continue; // Skip this image if download fails
                }

                $imageExtension = pathinfo($item['media_url'], PATHINFO_EXTENSION);
                $imageName = 'instagram/' . $item['id'] . '.' . $imageExtension;
                Storage::disk('public')->put($imageName, $imageContent);

                // Assign position (new images first)
                Image::where('user_id', $user->id)->increment('position');
                $newPosition = 1;

                // Create image record
                Image::create([
                    'user_id' => $user->id,
                    'instagram_media_id' => $item['id'], // Track Instagram media
                    'file_path' => $imageName,
                    'caption' => $item['caption'] ?? '',
                    'position' => $newPosition,
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

    /**
     * Reorder images based on the new order received from the frontend.
     */
    public function reorder(Request $request)
    {
        $orderedIds = $request->input('orderedIds');

        if (!is_array($orderedIds)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data format.'], 400);
        }

        $userId = auth()->id();

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
            // Log the error for debugging
            \Log::error('Image Reorder Error: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Failed to reorder images.'], 500);
        }
    }

    /**
     * Show the form for editing the specified image.
     */
    public function edit(Image $image)
    {
        // Authorization: Ensure the user owns the image
        $this->authorize('update', $image);

        return view('images.edit', compact('image'));
    }

    /**
     * Update the specified image in storage.
     */
    public function update(Request $request, Image $image)
    {
        // Authorization: Ensure the user owns the image
        $this->authorize('update', $image);

        // Validate the request
        $request->validate([
            'caption' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle the uploaded image if present
        if ($request->hasFile('photo')) {
            // Delete the old image from storage
            Storage::disk('public')->delete($image->file_path);

            $newImage = $request->file('photo');
            $filename = time() . '.' . $newImage->getClientOriginalExtension();
            $newFilePath = 'images/' . $filename;

            // Store the new image
            Storage::disk('public')->put($newFilePath, file_get_contents($newImage));

            // Update the file path in the database
            $image->file_path = $newFilePath;
        }

        // Update the caption
        $image->caption = $request->input('caption');

        $image->save();

        return redirect()->route('dashboard')->with('success', 'Image updated successfully!');
    }

    /**
     * Remove the specified image from storage.
     */
    public function destroy(Image $image)
    {
        // Authorization: Ensure the user owns the image
        $this->authorize('delete', $image);

        // Delete the image from storage
        Storage::disk('public')->delete($image->file_path);

        // Delete the image record from the database
        $image->delete();

        return redirect()->route('dashboard')->with('success', 'Image deleted successfully!');
    }
}
