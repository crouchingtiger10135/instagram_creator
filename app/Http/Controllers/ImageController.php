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
        $request->validate([
            'photos'   => 'required|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:12048',
            'caption'  => 'nullable|string|max:255',
        ]);

        if (!$request->hasFile('photos')) {
            return back()->withErrors('Please select at least one image to upload.');
        }

        foreach ($request->file('photos') as $photo) {
            $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            Storage::disk('public')->put('images/' . $filename, file_get_contents($photo));
            Image::where('user_id', Auth::id())->increment('position');
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

        DB::beginTransaction();
        try {
            foreach ($orderedIds as $index => $imageId) {
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
        $data = $request->validate([
            'image_ids'   => 'required|array',
            'image_ids.*' => 'integer|exists:images,id',
        ]);
        $userId = Auth::id();
        $images = Image::whereIn('id', $data['image_ids'])
            ->where('user_id', $userId)
            ->get();

        foreach ($images as $image) {
            if (Storage::disk('public')->exists($image->file_path)) {
                Storage::disk('public')->delete($image->file_path);
            }
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
     *
     * - If a new photo is uploaded, it is processed (and cropped if parameters are provided).
     * - If no new file is uploaded but crop parameters exist, the current image is cropped.
     */
    public function update(Request $request, Image $image)
    {
        $request->validate([
            'caption'     => 'nullable|string|max:255',
            'new_photo'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'crop_x'      => 'nullable|numeric',
            'crop_y'      => 'nullable|numeric',
            'crop_width'  => 'nullable|numeric',
            'crop_height' => 'nullable|numeric',
        ]);

        if ($request->hasFile('new_photo')) {
            // New image provided: delete old image, process new image.
            Storage::disk('public')->delete($image->file_path);
            $newPhoto = $request->file('new_photo');
            $filename = time() . '_' . uniqid() . '.' . $newPhoto->getClientOriginalExtension();
            $newFilePath = 'images/' . $filename;
            $img = InterventionImage::make($newPhoto->getPathname());

            if (
                $request->filled('crop_x') &&
                $request->filled('crop_y') &&
                $request->filled('crop_width') &&
                $request->filled('crop_height')
            ) {
                $cropX = (int) $request->input('crop_x');
                $cropY = (int) $request->input('crop_y');
                $cropWidth = (int) $request->input('crop_width');
                $cropHeight = (int) $request->input('crop_height');
                $img->crop($cropWidth, $cropHeight, $cropX, $cropY);
            }
            Storage::disk('public')->put($newFilePath, (string)$img->encode());
            $image->file_path = $newFilePath;
        } else if (
            $request->filled('crop_x') &&
            $request->filled('crop_y') &&
            $request->filled('crop_width') &&
            $request->filled('crop_height')
        ) {
            // No new file but crop parameters exist: crop the current image.
            $img = InterventionImage::make(storage_path('app/public/' . $image->file_path));
            $cropX = (int) $request->input('crop_x');
            $cropY = (int) $request->input('crop_y');
            $cropWidth = (int) $request->input('crop_width');
            $cropHeight = (int) $request->input('crop_height');
            $img->crop($cropWidth, $cropHeight, $cropX, $cropY);
            Storage::disk('public')->put($image->file_path, (string)$img->encode());
        }

        $image->caption = $request->input('caption');
        $image->save();

        return redirect()->route('dashboard')->with('success', 'Image updated successfully!');
    }

    /**
     * Remove a single image from storage.
     */
    public function destroy(Image $image)
    {
        Storage::disk('public')->delete($image->file_path);
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
            return redirect()->route('instagram.auth')->withErrors('Please connect your Instagram account first.');
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
                if ($item['media_type'] !== 'IMAGE') {
                    continue;
                }
                $existingImage = Image::where('instagram_media_id', $item['id'])
                    ->where('user_id', $user->id)
                    ->first();
                if ($existingImage) {
                    continue;
                }
                $imageContent = file_get_contents($item['media_url']);
                if ($imageContent === false) {
                    \Log::warning("Failed to download image: {$item['media_url']}");
                    continue;
                }
                $ext = pathinfo($item['media_url'], PATHINFO_EXTENSION);
                $imageName = 'instagram/' . $item['id'] . '.' . $ext;
                Storage::disk('public')->put($imageName, $imageContent);
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
