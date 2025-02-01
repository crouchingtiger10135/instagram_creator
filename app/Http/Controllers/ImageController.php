<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
// Import Intervention Image facade for cropping
use Intervention\Image\Facades\Image as InterventionImage;

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
     * Bulk delete selected images.
     */
    public function bulkDelete(Request $request)
    {
        // Validate the request input
        $data = $request->validate([
            'image_ids'   => 'required|array',
            'image_ids.*' => 'integer|exists:images,id',
        ]);

        $userId = Auth::id();
        $images = Image::whereIn('id', $data['image_ids'])
                        ->where('user_id', $userId)
                        ->get();

        foreach ($images as $image) {
            // Delete the image file from storage if it exists
            if (Storage::disk('public')->exists($image->file_path)) {
                Storage::disk('public')->delete($image->file_path);
            }
            // Delete the image record from the database
            $image->delete();
        }

        return redirect()->route('dashboard')
                         ->with('success', 'Selected images have been deleted successfully.');
    }

    /**
     * Show the form for editing a single image.
     */
    public function edit(Image $image)
    {
        return view('edit', compact('image'));
    }

    /**
     * Update a single image in storage, with optional cropping.
     */
    public function update(Request $request, Image $image)
    {
        // Validate the request including cropping fields
        $request->validate([
            'caption'     => 'nullable|string|max:255',
            'new_photo'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'crop_x'      => 'nullable|numeric',
            'crop_y'      => 'nullable|numeric',
            'crop_width'  => 'nullable|numeric',
            'crop_height' => 'nullable|numeric',
        ]);

        // If a new photo is provided, process it with optional cropping
        if ($request->hasFile('new_photo')) {
            // Delete the old image file
            Storage::disk('public')->delete($image->file_path);

            $newPhoto = $request->file('new_photo');
            $filename = time() . '_' . uniqid() . '.' . $newPhoto->getClientOriginalExtension();
            $newFilePath = 'images/' . $filename;

            // Create an Intervention Image instance
            $img = InterventionImage::make($newPhoto->getPathname());

            // If cropping parameters are provided, apply cropping
            if (
                $request->filled('crop_x') &&
                $request->filled('crop_y') &&
                $request->filled('crop_width') &&
                $request->filled('crop_height')
            ) {
                $cropX = (int)$request->input('crop_x');
                $cropY = (int)$request->input('crop_y');
                $cropWidth = (int)$request->input('crop_width');
                $cropHeight = (int)$request->input('crop_height');
                $img->crop($cropWidth, $cropHeight, $cropX, $cropY);
            }

            // Save the processed image to storage
            Storage::disk('public')->put($newFilePath, (string) $img->encode());

            // Update the file_path in the database
            $image->file_path = $newFilePath;
        }

        // Update caption if provided
        $image->caption = $request->input('caption');
        $image->save();

        return redirect()->route('dashboard')->with('success', 'Image updated successfully!');
    }

    /**
     * Remove a single image from storage.
     */
    public function destroy(Image $image)
    {
        // Delete the image from storage
        Storage::disk('public')->delete($image->file_path);

        // Delete the image record from the database
        $image->delete();

        return redirect()->route('dashboard')->with('success', 'Image deleted successfully!');
    }

    /**
     * Import the last 9 images from Instagram (if available).
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
                    continue; // Skip if already imported
                }

                // Download image content
                $imageContent = file_get_contents($item['media_url']);
                if ($imageContent === false) {
                    \Log::warning("Failed to download image: {$item['media_url']}");
                    continue;
                }

                $ext = pathinfo($item['media_url'], PATHINFO_EXTENSION);
                $imageName = 'instagram/' . $item['id'] . '.' . $ext;
                Storage::disk('public')->put($imageName, $imageContent);

                // Increment existing positions for new image
                Image::where('user_id', $user->id)->increment('position');

                // Create a new image record
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
