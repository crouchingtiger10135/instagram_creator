<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\InstagramController;
use App\Http\Controllers\InstagramAnalyticsController;


// Redirect to login if user visits the root
Route::get('/', function () {
    return redirect()->route('login');
});

// Require authentication + email verification
Route::middleware(['auth', 'verified'])->group(function () {
    // 1. Main Dashboard
    Route::get('/dashboard', [ImageController::class, 'index'])->name('dashboard');
    Route::post('/dashboard', [ImageController::class, 'store'])->name('dashboard.store');

    // 2. Reordering images (drag-and-drop)
    Route::post('/dashboard/reorder', [ImageController::class, 'reorder'])->name('dashboard.reorder');

    // 3. Edit, Update, Delete specific images
    Route::get('/dashboard/images/{image}/edit', [ImageController::class, 'edit'])->name('dashboard.images.edit');
    Route::patch('/dashboard/images/{image}', [ImageController::class, 'update'])->name('dashboard.images.update');
    Route::delete('/dashboard/images/{image}', [ImageController::class, 'destroy'])->name('dashboard.images.destroy');

    // 4. (NEW) Test Dashboard route
    //    Displays the same images but uses your "dashboard-test.blade.php" view
    Route::get('/dashboard-test', function () {
        // Fetch images for the authenticated user, same as the main dashboard
        $images = \App\Models\Image::where('user_id', auth()->id())
                                  ->orderBy('position', 'asc')
                                  ->get();

        // Return the "test" view file. 
        // (Make sure you have resources/views/dashboard-test.blade.php)
        return view('dashboard-test', compact('images'));
    })->name('dashboard.test');

    // Breeze Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/dashboard/images/bulk-delete', [ImageController::class, 'bulkDelete'])
    ->name('dashboard.images.bulk-delete');

Route::get('/instagram-analytics', [InstagramAnalyticsController::class, 'index'])
    ->name('instagram.analytics');

    
    // Instagram
    Route::middleware(['auth'])->group(function () {
        Route::get('/instagram/auth', [InstagramController::class, 'redirectToInstagram'])->name('instagram.auth');
        Route::get('/instagram/callback', [InstagramController::class, 'handleInstagramCallback'])->name('instagram.callback');
        Route::get('/dashboard/import-instagram', [ImageController::class, 'importInstagramImages'])->name('dashboard.importInstagram');
    });
});

// Breeze auth routes (login, register, etc.)
require __DIR__.'/auth.php';
