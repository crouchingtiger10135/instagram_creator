<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ImageController extends Controller
{
    /**
     * Display the user's images in a 3-column feed.
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
     * Handle uploads (POST /dashboard).
     */
    public function store(Request $request)
    {
        $request->validate([
            'photo'    => 'required|image|max:10000', // Max size: 10MB
            'caption'  => 'nullable|string|max:255',  // Optional caption
        ]);

        // Begin transaction to ensure data integrity
        DB::beginTransaction();

        try {
            $userId = auth()->id();

            // Increment the position of all existing images by 1
            Image::where('user_id', $userId)->increment('position');

            // Assign position = 1 to the new image
            $newPosition = 1;

            // Store the image in the 'public/photos' directory
            $path = $request->file('photo')->store('photos', 'public');

            // Create the image record
            Image::create([
                'user_id'   => $userId,
                'file_path' => $path,
                'caption'   => $request->input('caption', ''), // Optional caption
                'position'  => $newPosition,
            ]);

            DB::commit();

            return back()->with('success', 'Image uploaded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            \Log::error('Image Upload Error: ' . $e->getMessage());

            return back()->withErrors('Failed to upload image. Please try again.');
        }
    }

    /**
     * Reorder images (via drag-and-drop).
     * Expects JSON: { "orderedIds": [imageId1, imageId2, ...] }
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
     * Show the edit form for a single image (GET /dashboard/images/{image}/edit).
     */
    public function edit(Image $image)
    {
        // Ensure the authenticated user owns this image
        if ($image->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('edit', compact('image'));
    }

    /**
     * Update an image's caption or file (PATCH /dashboard/images/{image}).
     */
    public function update(Request $request, Image $image)
    {
        // Ensure the authenticated user owns this image
        if ($image->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Validate inputs
        $request->validate([
            'caption'    => 'nullable|string|max:255',
            'new_photo'  => 'nullable|image|max:10000', // Max size: 10MB
        ]);

        // Begin transaction
        DB::beginTransaction();

        try {
            // Update caption if provided
            if ($request->filled('caption')) {
                $image->caption = $request->input('caption');
            }

            // If a new photo is uploaded
            if ($request->hasFile('new_photo')) {
                // Delete the old photo if it exists
                if (Storage::disk('public')->exists($image->file_path)) {
                    Storage::disk('public')->delete($image->file_path);
                }

                // Store the new photo
                $newPath = $request->file('new_photo')->store('photos', 'public');
                $image->file_path = $newPath;
            }

            // Save the changes
            $image->save();

            DB::commit();

            return redirect()->route('dashboard')->with('success', 'Image updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            \Log::error('Image Update Error: ' . $e->getMessage());

            return back()->withErrors('Failed to update image. Please try again.');
        }
    }

    /**
     * Delete an image (DELETE /dashboard/images/{image}).
     */
    public function destroy(Image $image)
    {
        // Ensure the authenticated user owns this image
        if ($image->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            $deletedPosition = $image->position;

            // Delete the image file from storage if it exists
            if (Storage::disk('public')->exists($image->file_path)) {
                Storage::disk('public')->delete($image->file_path);
            }

            // Delete the image record
            $image->delete();

            // Decrement the position of images that were after the deleted image
            Image::where('user_id', auth()->id())
                 ->where('position', '>', $deletedPosition)
                 ->decrement('position');

            DB::commit();

            // Redirect to dashboard without a success message
            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            \Log::error('Image Deletion Error: ' . $e->getMessage());

            return back()->withErrors('Failed to delete image. Please try again.');
        }
    }
}
