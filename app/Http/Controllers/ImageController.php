<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Display the user's images in a 3-column feed.
     */
    public function index()
    {
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
            'photo' => 'required|image|max:2048',
        ]);

        // store() returns something like "public/photos/xyz.jpg"
        $path = $request->file('photo')->store('public/photos');

        // place new image at the end => position = max + 1
        $maxPosition = Image::where('user_id', auth()->id())->max('position') ?? 0;

        Image::create([
            'user_id'   => auth()->id(),
            'file_path' => $path,
            'caption'   => '',         // or null, if you prefer
            'position'  => $maxPosition + 1,
        ]);

        return back()->with('success', 'Image uploaded successfully!');
    }

    /**
     * Reorder images (via drag-and-drop).
     * Expects JSON: { "orderedIds": [imageId1, imageId2, ...] }
     */
    public function reorder(Request $request)
    {
        $orderedIds = $request->input('orderedIds');
        if (!is_array($orderedIds)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        $userId = auth()->id();
        $position = 1;

        foreach ($orderedIds as $imageId) {
            // only update if it belongs to this user
            Image::where('id', $imageId)
                 ->where('user_id', $userId)
                 ->update(['position' => $position]);

            $position++;
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Show the edit form for a single image (GET /dashboard/images/{image}/edit).
     */
    public function edit(Image $image)
    {
        // Ensure user owns this image
        if ($image->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        return view('edit', compact('image'));
    }

    /**
     * Update an image's caption or file (PATCH /dashboard/images/{image}).
     */
    public function update(Request $request, Image $image)
    {
        // Ensure user is owner
        if ($image->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Validate: caption is optional, file is optional
        $request->validate([
            'caption' => 'nullable|string|max:255',
            'new_photo' => 'nullable|image|max:2048',
        ]);

        // If user uploaded a new file
        if ($request->hasFile('new_photo')) {
            // Optionally delete old file, if desired:
            // Storage::delete($image->file_path);

            $path = $request->file('new_photo')->store('public/photos');
            $image->file_path = $path;
        }

        // Update caption
        $image->caption = $request->input('caption', '');
        $image->save();

        return redirect()->route('dashboard')->with('success', 'Image updated successfully!');
    }

    /**
     * Delete an image (DELETE /dashboard/images/{image}).
     */
    public function destroy(Image $image)
    {
        // Ensure user is owner
        if ($image->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Optionally delete the file from storage
        // Storage::delete($image->file_path);

        $image->delete();

        return back()->with('success', 'Image deleted successfully!');
    }
}
